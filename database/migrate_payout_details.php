<?php

require_once __DIR__ . '/../config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_columns = [
    'payout_account_holder' => "ADD COLUMN payout_account_holder VARCHAR(150) NULL AFTER profile_image",
    'payout_bank_name' => "ADD COLUMN payout_bank_name VARCHAR(100) NULL AFTER payout_account_holder",
    'payout_account_number' => "ADD COLUMN payout_account_number VARCHAR(30) NULL AFTER payout_bank_name",
    'payout_branch_code' => "ADD COLUMN payout_branch_code VARCHAR(10) NULL AFTER payout_account_number",
    'payout_account_type' => "ADD COLUMN payout_account_type ENUM('cheque', 'savings') NULL AFTER payout_branch_code",
];

foreach ($user_columns as $name => $alter) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$name'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE users $alter");
    }
}

$withdrawal_columns = [
    'account_holder' => "ADD COLUMN account_holder VARCHAR(150) NULL AFTER amount",
    'bank_name' => "ADD COLUMN bank_name VARCHAR(100) NULL AFTER account_holder",
    'account_number' => "ADD COLUMN account_number VARCHAR(30) NULL AFTER bank_name",
    'branch_code' => "ADD COLUMN branch_code VARCHAR(10) NULL AFTER account_number",
    'account_type' => "ADD COLUMN account_type ENUM('cheque', 'savings') NULL AFTER branch_code",
];

foreach ($withdrawal_columns as $name => $alter) {
    $check = $conn->query("SHOW COLUMNS FROM withdrawals LIKE '$name'");
    if ($check->num_rows === 0) {
        $conn->query("ALTER TABLE withdrawals $alter");
    }
}

echo 'Payout details migration complete. <a href="' . PLATFORM_URL . '/wallet.php">Go to Wallet</a>';
