<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/payfast.php';
$config = require __DIR__ . '/payfast_config.php';

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    die("Cart is empty");
}

// 1. Collect form data
$fullname = $_POST['fullname'];
$address  = $_POST['address'];
$phone    = $_POST['phone'];
$total    = $_POST['total'];

// 2. Create unique order reference
$orderRef = 'ORD-' . time() . rand(100,999);
$user_id = $_SESSION['user_id'] ?? null;
// 3. Store order in DB (PENDING)
$stmt = $conn->prepare("
    INSERT INTO orders (user_id,order_ref, amount, status)
    VALUES (?, ?,?, 'pending')
");
$stmt->bind_param("isd", $orderRef, $total, $user_id);
$stmt->execute();

// 4. PayFast data
$data = [
    'merchant_id'   => $config['merchant_id'],
    'merchant_key'  => $config['merchant_key'],

    'return_url'    => "http://localhost/return.php?order_ref=$orderRef",
    'cancel_url'    => "http://localhost/cancel.php?order_ref=$orderRef",
    'notify_url'    => "http://localhost/itn.php",

    'm_payment_id'  => $orderRef,
    'amount'        => number_format($total, 2, '.', ''),
    'item_name'     => "Order $orderRef"
];

// 5. Signature
$signature = generatePayFastSignature($data, $config['passphrase']);
$data['signature'] = $signature;

// 6. Redirect to PayFast
$url = $config['sandbox']
    ? "https://sandbox.payfast.co.za/eng/process"
    : "https://www.payfast.co.za/eng/process";

header("Location: $url?" . http_build_query($data));
exit;