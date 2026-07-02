<?php

session_start();

require_once __DIR__ . '/includes/auth.php';

$reference = $_GET['m_payment_id'] ?? $_POST['m_payment_id'] ?? null;
$status = $_GET['status'] ?? $_POST['payment_status'] ?? null;

if (!$reference && isset($_SESSION['pending_order_id'])) {
    $reference = $_SESSION['pending_order_id'];
}

if (!$reference) {
    die('Invalid order reference.');
}

if (preg_match('/(\d+)$/', (string) $reference, $matches)) {
    $order_id = (int) $matches[1];
} else {
    $order_id = (int) $reference;
}

if ($order_id <= 0) {
    die('Invalid order reference.');
}

if ($status === 'success' || strcasecmp((string) $status, 'COMPLETE') === 0) {
    $user_id = current_user_id();
    $account_holder = trim($_POST['payfast_account_holder'] ?? '');
    $account_email = trim($_POST['payfast_account_email'] ?? '');

    if ($user_id && $account_holder !== '' && filter_var($account_email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare('
            SELECT id
            FROM orders
            WHERE id = ? AND buyer_id = ?
            LIMIT 1
        ');
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
            mark_order_paid($conn, $order_id);
        }
    }

    unset($_SESSION['pending_order_id']);
    header('Location: ' . site_url('order_success.php?order_id=' . $order_id));
    exit();
}

if ($status === 'cancel') {
    $_SESSION['flash_message'] = 'Payment was cancelled. You can pay again from your orders page.';
    header('Location: ' . site_url('orders.php'));
    exit();
}

die('Invalid payment status received.');
