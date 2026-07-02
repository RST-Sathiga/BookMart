<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Invalid request");
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0) {
    exit("Invalid withdrawal");
}

/*
────────────────────────────
FETCH WITHDRAWAL
────────────────────────────
*/
$stmt = $conn->prepare("
    SELECT * FROM withdrawals
    WHERE id = ? AND status = 'pending'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$withdrawal = $stmt->get_result()->fetch_assoc();

if (!$withdrawal) {
    exit("Withdrawal not found or already processed");
}

$conn->begin_transaction();

try {

    if ($action === 'approve') {

        /*
        Mark approved
        */
        $update = $conn->prepare("
            UPDATE withdrawals
            SET status = 'approved',
                processed_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param("i", $id);
        $update->execute();

        /*
        Mark completed (simulating bank payout success)
        */
        $complete = $conn->prepare("
            UPDATE withdrawals
            SET status = 'completed'
            WHERE id = ?
        ");
        $complete->bind_param("i", $id);
        $complete->execute();

    }

    elseif ($action === 'reject') {

        /*
        Refund wallet (IMPORTANT)
        */
        $refund = $conn->prepare("
            UPDATE wallets
            SET balance = balance + ?
            WHERE user_id = ?
        ");
        $refund->bind_param("di", $withdrawal['amount'], $withdrawal['seller_id']);
        $refund->execute();

        /*
        Mark rejected
        */
        $update = $conn->prepare("
            UPDATE withdrawals
            SET status = 'rejected',
                processed_at = NOW()
            WHERE id = ?
        ");
        $update->bind_param("i", $id);
        $update->execute();

        /*
        Log refund
        */
        $log = $conn->prepare("
            INSERT INTO wallet_transactions
            (user_id, amount, type, description)
            VALUES (?, ?, 'credit', 'Withdrawal rejected refund')
        ");
        $log->bind_param("id", $withdrawal['seller_id'], $withdrawal['amount']);
        $log->execute();
    }

    $conn->commit();

    header("Location: admin_withdrawals.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    exit("Error: " . $e->getMessage());
}