CREATE DATABASE IF NOT EXISTS bookmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookmart;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS wallet_transactions;
DROP TABLE IF EXISTS platform_revenue;
DROP TABLE IF EXISTS withdrawals;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS campuses;
DROP TABLE IF EXISTS universities;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL
);

CREATE TABLE campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    pickup_point VARCHAR(255) NOT NULL,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    id_passport_number VARCHAR(30) NULL UNIQUE,
    profile_image VARCHAR(255) NULL,
    payout_account_holder VARCHAR(150) NULL,
    payout_bank_name VARCHAR(100) NULL,
    payout_account_number VARCHAR(30) NULL,
    payout_branch_code VARCHAR(10) NULL,
    payout_account_type ENUM('cheque', 'savings') NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'banned', 'pending') DEFAULT 'active',
    university_id INT NULL,
    campus_id INT NULL,
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00,
    security_question VARCHAR(255) NULL,
    security_answer VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id),
    FOREIGN KEY (campus_id) REFERENCES campuses(id)
);

CREATE TABLE products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) DEFAULT NULL,
    course_code VARCHAR(50) DEFAULT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    description TEXT,
    book_condition ENUM('new', 'like_new', 'good', 'fair') DEFAULT 'good',
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    university_id INT(11) NOT NULL,
    campus_id INT(11) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'sold') DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_products_user (user_id),
    KEY idx_products_university (university_id),
    KEY idx_products_campus (campus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE cart (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY unique_cart_user (user_id),
    KEY idx_cart_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE wishlist (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    buyer_id INT(11) NOT NULL,
    seller_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    seller_payout DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(50) DEFAULT 'PayFast',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    transaction_reference VARCHAR(100) DEFAULT NULL,
    order_status ENUM('processing', 'awaiting_pickup', 'completed', 'cancelled') DEFAULT 'processing',
    pickup_code VARCHAR(6) DEFAULT NULL,
    pickup_location VARCHAR(255) DEFAULT NULL,
    pickup_confirmed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_buyer (buyer_id),
    KEY idx_orders_seller (seller_id),
    KEY idx_orders_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE withdrawals (
    id INT(11) NOT NULL AUTO_INCREMENT,
    seller_id INT(11) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    account_holder VARCHAR(150) NULL,
    bank_name VARCHAR(100) NULL,
    account_number VARCHAR(30) NULL,
    branch_code VARCHAR(10) NULL,
    account_type ENUM('cheque', 'savings') NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_withdrawals_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE wallet_transactions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    order_id INT(11) DEFAULT NULL,
    type ENUM('sale', 'withdrawal', 'commission') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_wallet_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE platform_revenue (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE messages (
    id INT(11) NOT NULL AUTO_INCREMENT,
    sender_id INT(11) NOT NULL,
    receiver_id INT(11) NOT NULL,
    order_id INT(11) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_sender (sender_id),
    KEY idx_messages_receiver (receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO universities (name, city) VALUES
('University of Pretoria', 'Pretoria'),
('University of Johannesburg', 'Johannesburg'),
('University of the Witwatersrand', 'Johannesburg'),
('University of Cape Town', 'Cape Town'),
('Stellenbosch University', 'Stellenbosch'),
('University of KwaZulu-Natal', 'Durban'),
('North-West University', 'Potchefstroom'),
('University of the Free State', 'Bloemfontein');

INSERT INTO campuses (university_id, name, pickup_point) VALUES
(1, 'Hatfield Campus', 'Student Centre, Hatfield'),
(1, 'Groenkloof Campus', 'Main Reception, Groenkloof'),
(2, 'Auckland Park Kingsway', 'Library Entrance, APK'),
(2, 'Doornfontein Campus', 'Student Hub, Doornfontein'),
(3, 'East Campus', 'Matrix Building, East Campus'),
(3, 'West Campus', 'Wits Art Museum Foyer'),
(4, 'Upper Campus', 'Jammie Plaza, Upper Campus'),
(4, 'Lower Campus', 'Leslie Social Science Building'),
(5, 'Stellenbosch Main', 'Neelsie Student Centre'),
(6, 'Howard College', 'Howard College Library'),
(6, 'Westville Campus', 'Student Union, Westville'),
(7, 'Potchefstroom Campus', 'Building F1 Foyer'),
(8, 'Bloemfontein Campus', 'Student Centre, Bloemfontein');

INSERT INTO users (fullname, username, email, phone, password, role, status, university_id, campus_id, security_question, security_answer)
VALUES (
    'System Administrator',
    'admin',
    'admin@bookmart.com',
    '0000000000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    1,
    1,
    'What city is the platform based in?',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);
