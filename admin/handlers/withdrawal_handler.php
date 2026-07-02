<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header('Location: ../withdrawals.php');
    exit();
}

$stmt = $conn->prepare('SELECT seller_id, amount, status FROM withdrawals WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$withdrawal = $stmt->get_result()->fetch_assoc();

if (!$withdrawal || $withdrawal['status'] !== 'pending') {
    header('Location: ../withdrawals.php');
    exit();
}

$seller_id = (int) $withdrawal['seller_id'];
$amount = (float) $withdrawal['amount'];

if ($action === 'approve') {
    $user = get_user_by_id($conn, $seller_id);
    if ((float) $user['wallet_balance'] < $amount) {
        header('Location: ../withdrawals.php');
        exit();
    }

    $conn->begin_transaction();

    try {
        $deduct = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE user_id = ?');
        $deduct->bind_param('di', $amount, $seller_id);
        $deduct->execute();

        $tx = $conn->prepare('
            INSERT INTO wallet_transactions (user_id, type, amount, description)
            VALUES (?, "withdrawal", ?, "Withdrawal processed by admin")
        ');
        $tx->bind_param('id', $seller_id, $amount);
        $tx->execute();

        $status = 'completed';
        $update = $conn->prepare('UPDATE withdrawals SET status = ? WHERE id = ?');
        $update->bind_param('si', $status, $id);
        $update->execute();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
    }
} else {
    $status = 'rejected';
    $update = $conn->prepare('UPDATE withdrawals SET status = ? WHERE id = ?');
    $update->bind_param('si', $status, $id);
    $update->execute();
}

header('Location: ../withdrawals.php');
exit();
