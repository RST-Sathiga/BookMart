<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$config = require "payfast_config.php";

if (empty($_SESSION['cart'])) {
    die("Cart is empty");
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'];

$total = 0;

/**
 * Calculate secure total from DB
 */
$ids = implode(',', array_map('intval', array_keys($cart)));

$result = $conn->query("
    SELECT id, price
    FROM products
    WHERE id IN ($ids)
");

while ($row = $result->fetch_assoc()) {
    $qty = $cart[$row['id']];
    $total += $row['price'] * $qty;
}

/**
 * Create order BEFORE redirect
 */
$stmt = $conn->prepare("
    INSERT INTO orders (user_id, total_amount, status)
    VALUES (?, ?, 'pending')
");
$stmt->bind_param("id", $user_id, $total);
$stmt->execute();

$order_id = $stmt->insert_id;

/**
 * Sandbox PayFast URL
 */
$payfast_url = "https://sandbox.payfast.co.za/eng/process";

$data = [
    "merchant_id" => $config['merchant_id'],
    "merchant_key" => $config['merchant_key'],
    "return_url" => "http://localhost/Bookmart/payment_success.php?order_id=$order_id",
    "cancel_url" => "http://localhost/Bookmart/payment_cancel.php?order_id=$order_id",
    "notify_url" => "http://localhost/Bookmart/payfast_notify.php",
    "m_payment_id" => $order_id,
    "amount" => number_format($total, 2, '.', ''),
    "item_name" => "Test Order #$order_id"
];

header("Location: $payfast_url?" . http_build_query($data));
exit();