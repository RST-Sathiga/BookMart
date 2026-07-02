USE bookmart;

CREATE TABLE IF NOT EXISTS universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT '',
    institution_type ENUM('public', 'private', 'other') NOT NULL DEFAULT 'public',
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    UNIQUE KEY unique_university_city (name, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    university_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    pickup_point VARCHAR(255) NOT NULL DEFAULT '',
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    KEY idx_campuses_university (university_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE universities ADD COLUMN IF NOT EXISTS institution_type ENUM('public', 'private', 'other') NOT NULL DEFAULT 'public' AFTER city;
ALTER TABLE universities ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 7) NULL AFTER institution_type;
ALTER TABLE universities ADD COLUMN IF NOT EXISTS longitude DECIMAL(10, 7) NULL AFTER latitude;

ALTER TABLE campuses ADD COLUMN IF NOT EXISTS pickup_point VARCHAR(255) NOT NULL DEFAULT '' AFTER name;
ALTER TABLE campuses ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 7) NULL AFTER pickup_point;
ALTER TABLE campuses ADD COLUMN IF NOT EXISTS longitude DECIMAL(10, 7) NULL AFTER latitude;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'banned', 'pending') DEFAULT 'active',
    university_id INT NULL,
    campus_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users ADD COLUMN IF NOT EXISTS id_passport_number VARCHAR(30) NULL UNIQUE AFTER phone;
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_number VARCHAR(50) NULL UNIQUE AFTER id_passport_number;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) NULL AFTER student_number;
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_card_image VARCHAR(255) NULL AFTER profile_image;
ALTER TABLE users ADD COLUMN IF NOT EXISTS course VARCHAR(150) NULL AFTER campus_id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER course;
ALTER TABLE users ADD COLUMN IF NOT EXISTS security_question VARCHAR(255) NULL AFTER wallet_balance;
ALTER TABLE users ADD COLUMN IF NOT EXISTS security_answer VARCHAR(255) NULL AFTER security_question;
ALTER TABLE users ADD COLUMN IF NOT EXISTS payout_account_holder VARCHAR(150) NULL AFTER profile_image;
ALTER TABLE users ADD COLUMN IF NOT EXISTS payout_bank_name VARCHAR(100) NULL AFTER payout_account_holder;
ALTER TABLE users ADD COLUMN IF NOT EXISTS payout_account_number VARCHAR(30) NULL AFTER payout_bank_name;
ALTER TABLE users ADD COLUMN IF NOT EXISTS payout_branch_code VARCHAR(10) NULL AFTER payout_account_number;
ALTER TABLE users ADD COLUMN IF NOT EXISTS payout_account_type ENUM('cheque', 'savings') NULL AFTER payout_branch_code;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(150) DEFAULT NULL,
    course_code VARCHAR(50) DEFAULT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    description TEXT,
    book_condition ENUM('new', 'like_new', 'good', 'fair') DEFAULT 'good',
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    university_id INT NOT NULL,
    campus_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'sold') DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_products_user (user_id),
    KEY idx_products_university (university_id),
    KEY idx_products_campus (campus_id),
    KEY idx_products_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE products ADD COLUMN IF NOT EXISTS author VARCHAR(150) NULL AFTER title;
ALTER TABLE products ADD COLUMN IF NOT EXISTS course_code VARCHAR(50) NULL AFTER author;
ALTER TABLE products ADD COLUMN IF NOT EXISTS module_code VARCHAR(50) NULL AFTER course_code;
ALTER TABLE products ADD COLUMN IF NOT EXISTS isbn VARCHAR(20) NULL AFTER module_code;
ALTER TABLE products ADD COLUMN IF NOT EXISTS edition VARCHAR(80) NULL AFTER isbn;
ALTER TABLE products ADD COLUMN IF NOT EXISTS publisher VARCHAR(150) NULL AFTER edition;
ALTER TABLE products ADD COLUMN IF NOT EXISTS faculty VARCHAR(150) NULL AFTER publisher;
ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL AFTER faculty;
ALTER TABLE products ADD COLUMN IF NOT EXISTS book_condition ENUM('new', 'like_new', 'good', 'fair') DEFAULT 'good' AFTER description;
ALTER TABLE products ADD COLUMN IF NOT EXISTS negotiable TINYINT(1) NOT NULL DEFAULT 0 AFTER price;
ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL AFTER negotiable;
ALTER TABLE products ADD COLUMN IF NOT EXISTS quantity INT NOT NULL DEFAULT 1 AFTER image;
ALTER TABLE products ADD COLUMN IF NOT EXISTS views INT NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE products ADD COLUMN IF NOT EXISTS university_id INT NULL AFTER image;
ALTER TABLE products ADD COLUMN IF NOT EXISTS campus_id INT NULL AFTER university_id;

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    KEY idx_cart_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE cart DROP INDEX IF EXISTS unique_cart_user;
ALTER TABLE cart ADD UNIQUE KEY IF NOT EXISTS unique_cart_item (user_id, product_id);

CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist_item (user_id, product_id),
    KEY idx_wishlist_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    product_id INT NOT NULL,
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
    KEY idx_orders_buyer (buyer_id),
    KEY idx_orders_seller (seller_id),
    KEY idx_orders_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_messages_sender (sender_id),
    KEY idx_messages_receiver (receiver_id),
    KEY idx_messages_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    type ENUM('sale', 'withdrawal', 'commission') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wallet_user (user_id),
    KEY idx_wallet_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    account_holder VARCHAR(150) NULL,
    bank_name VARCHAR(100) NULL,
    account_number VARCHAR(30) NULL,
    branch_code VARCHAR(10) NULL,
    account_type ENUM('cheque', 'savings') NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_withdrawals_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_revenue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_revenue_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    seller_id INT NOT NULL,
    product_id INT NULL,
    order_id INT NULL,
    rating INT NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reviews_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    product_id INT NULL,
    reported_user_id INT NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(500) NOT NULL,
    type ENUM('chat','system','fraud','order') NOT NULL DEFAULT 'system',
    severity ENUM('low','medium','high') NOT NULL DEFAULT 'low',
    related_id INT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE notifications ADD COLUMN IF NOT EXISTS title VARCHAR(150) NULL AFTER user_id;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS type ENUM('chat','system','fraud','order') NOT NULL DEFAULT 'system' AFTER message;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS severity ENUM('low','medium','high') NOT NULL DEFAULT 'low' AFTER type;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS related_id INT NULL AFTER severity;

CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    action VARCHAR(255) NOT NULL,
    context VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO universities (id, name, city, institution_type) VALUES
(1, 'University of Pretoria', 'Pretoria', 'public'),
(2, 'University of Johannesburg', 'Johannesburg', 'public'),
(3, 'University of the Witwatersrand', 'Johannesburg', 'public'),
(4, 'University of Cape Town', 'Cape Town', 'public'),
(5, 'Stellenbosch University', 'Stellenbosch', 'public'),
(6, 'University of KwaZulu-Natal', 'Durban', 'public'),
(7, 'North-West University', 'Potchefstroom', 'public'),
(8, 'University of the Free State', 'Bloemfontein', 'public');

INSERT IGNORE INTO campuses (id, university_id, name, pickup_point) VALUES
(1, 1, 'Hatfield Campus', 'Student Centre, Hatfield'),
(2, 1, 'Groenkloof Campus', 'Main Reception, Groenkloof'),
(3, 2, 'Auckland Park Kingsway', 'Library Entrance, APK'),
(4, 2, 'Doornfontein Campus', 'Student Hub, Doornfontein'),
(5, 3, 'East Campus', 'Matrix Building, East Campus'),
(6, 3, 'West Campus', 'Wits Art Museum Foyer'),
(7, 4, 'Upper Campus', 'Jammie Plaza, Upper Campus'),
(8, 4, 'Lower Campus', 'Leslie Social Science Building'),
(9, 5, 'Stellenbosch Main', 'Neelsie Student Centre'),
(10, 6, 'Howard College', 'Howard College Library'),
(11, 6, 'Westville Campus', 'Student Union, Westville'),
(12, 7, 'Potchefstroom Campus', 'Building F1 Foyer'),
(13, 8, 'Bloemfontein Campus', 'Student Centre, Bloemfontein');
