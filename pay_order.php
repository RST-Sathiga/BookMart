<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$order_id = (int) ($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: ' . site_url('orders.php'));
    exit();
}

$stmt = $conn->prepare('
    SELECT id, product_id
    FROM orders
    WHERE id = ? AND buyer_id = ? AND payment_status = "pending"
    LIMIT 1
');
$user_id = current_user_id();
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['flash_message'] = 'That pending order cannot be paid.';
    header('Location: ' . site_url('orders.php'));
    exit();
}

$_SESSION['checkout_item'] = [
    'product_id' => (int) $order['product_id'],
    'qty' => 1,
];
$_SESSION['checkout_token'] = bin2hex(random_bytes(16));
$_SESSION['pending_order_id'] = $order_id;

header('Location: ' . site_url('checkout.php?token=' . $_SESSION['checkout_token']));
exit();
