<?php

require_once __DIR__ . '/../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$column_check = $conn->query("SHOW COLUMNS FROM universities LIKE 'institution_type'");

if ($column_check->num_rows === 0) {
    $conn->query("ALTER TABLE universities ADD COLUMN institution_type ENUM('public', 'private', 'other') NOT NULL DEFAULT 'public' AFTER city");
    $conn->query("UPDATE universities SET institution_type = 'public' WHERE institution_type = 'public'");
}

$sql = file_get_contents(__DIR__ . '/add_institutions.sql');
$statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));

foreach ($statements as $statement) {
    if ($statement === '' || stripos($statement, 'USE ') === 0 || stripos($statement, 'ALTER TABLE') === 0) {
        continue;
    }

    if (!$conn->query($statement)) {
        echo 'Warning: ' . $conn->error . '<br>';
    }
}

echo 'Institutions migration complete. <a href="../register.php">Back to site</a>';
