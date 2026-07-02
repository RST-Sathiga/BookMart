<?php

require_once __DIR__ . '/../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$columns = [
    'id_passport_number' => "ADD COLUMN id_passport_number VARCHAR(30) NULL AFTER phone",
    'profile_image' => "ADD COLUMN profile_image VARCHAR(255) NULL AFTER id_passport_number",
];

foreach ($columns as $name => $alter) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$name'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE users $alter");
    }
}

$index_check = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_id_passport'");
if ($index_check->num_rows === 0) {
    $conn->query('CREATE UNIQUE INDEX idx_users_id_passport ON users (id_passport_number)');
}

echo 'Profile fields migration complete. <a href="' . PLATFORM_URL . '">Open BookMart</a>';
