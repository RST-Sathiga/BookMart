<?php

require_once __DIR__ . '/../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_columns = [
    'student_card_image' => "ADD COLUMN student_card_image VARCHAR(255) NULL AFTER profile_image",
    'course' => "ADD COLUMN course VARCHAR(150) NULL AFTER campus_id",
];

foreach ($user_columns as $name => $alter) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$name'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE users $alter");
    }
}

$conn->query("
    CREATE TABLE IF NOT EXISTS user_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT NOT NULL,
        reported_id INT NOT NULL,
        reason VARCHAR(500) NOT NULL,
        context_type VARCHAR(50) NULL,
        context_id INT NULL,
        status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS call_signals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_key VARCHAR(64) NOT NULL,
        sender_id INT NOT NULL,
        signal_type VARCHAR(20) NOT NULL,
        payload TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chat_key (chat_key),
        INDEX idx_created (created_at)
    )
");

echo 'Account features migration complete. <a href="' . PLATFORM_URL . '">Open BookMart</a>';
