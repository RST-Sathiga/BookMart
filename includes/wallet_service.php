<?php

function credit_seller_wallet($conn, $order_id)
{
    $stmt = $conn->prepare("
        SELECT id, seller_id, seller_payout, order_status
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order || $order['order_status'] !== 'completed') {
        return false;
    }

    $seller_id = (int)$order['seller_id'];
    $amount = (float)$order['seller_payout'];

    $conn->begin_transaction();

    try {

        // prevent double credit
        $check = $conn->prepare("
            SELECT id FROM wallet_transactions
            WHERE order_id = ? AND type = 'credit'
            LIMIT 1
        ");
        $check->bind_param("i", $order_id);
        $check->execute();

        if ($check->get_result()->fetch_assoc()) {
            $conn->rollback();
            return false;
        }

        // update wallet
        $wallet = $conn->prepare("
            INSERT INTO wallets (user_id, balance)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)
        ");
        $wallet->bind_param("id", $seller_id, $amount);
        $wallet->execute();

        // ledger entry
        $log = $conn->prepare("
            INSERT INTO wallet_transactions
            (user_id, order_id, amount, type, description)
            VALUES (?, ?, ?, 'credit', 'Order payout')
        ");
        $log->bind_param("iid", $seller_id, $order_id, $amount);
        $log->execute();

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>