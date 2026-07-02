<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$order_id = (int) ($_POST['order_id'] ?? ($_SESSION['pending_order_id'] ?? 0));
$user_id = current_user_id();
$account_holder = trim($_POST['payfast_account_holder'] ?? '');
$account_email = trim($_POST['payfast_account_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $account_holder === '' || !filter_var($account_email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_message'] = 'Enter your PayFast account details before paying.';
    header('Location: ' . site_url('orders.php'));
    exit();
}

if ($order_id <= 0) {
    $_SESSION['flash_message'] = 'No pending payment could be found.';
    header('Location: ' . site_url('orders.php'));
    exit();
}

$stmt = $conn->prepare('
    SELECT id
    FROM orders
    WHERE id = ? AND buyer_id = ? AND payment_status = "pending"
    LIMIT 1
');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['flash_message'] = 'That order is not available for payment.';
    header('Location: ' . site_url('orders.php'));
    exit();
}

if (mark_order_paid($conn, $order_id)) {
    unset($_SESSION['pending_order_id']);
    $_SESSION['flash_message'] = 'Payment completed successfully. Seller earnings are now available for withdrawal.';
    header('Location: ' . site_url('order_success.php?order_id=' . $order_id));
    exit();
}

$_SESSION['flash_message'] = 'Payment could not be completed. Please try again or contact support.';
header('Location: ' . site_url('orders.php'));
exit();
