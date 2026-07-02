<?php

function calculate_risk_score($conn, $user_id, $event_type) {

    $risk = 0;

    /*
    ─────────────────────────────
    RULE 1: Too many withdrawals (24h)
    ─────────────────────────────
    */
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM withdrawals
        WHERE seller_id = ?
        AND created_at > (NOW() - INTERVAL 1 DAY)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($cnt > 3) $risk += 40;

    /*
    ─────────────────────────────
    RULE 2: Frequent bank changes
    ─────────────────────────────
    */
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM user_payout_details_log
        WHERE user_id = ?
        AND updated_at > (NOW() - INTERVAL 7 DAY)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($cnt > 2) $risk += 35;

    /*
    ─────────────────────────────
    RULE 3: High-value withdrawal spike
    ─────────────────────────────
    */
    $stmt = $conn->prepare("
        SELECT AVG(amount) AS avg_amount
        FROM withdrawals
        WHERE seller_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $avg = (float)$stmt->get_result()->fetch_assoc()['avg_amount'];

    if ($avg > 0) {
        $stmt = $conn->prepare("
            SELECT amount FROM withdrawals
            WHERE seller_id = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $latest = (float)$stmt->get_result()->fetch_assoc()['amount'];

        if ($latest > $avg * 3) {
            $risk += 30;
        }
    }

    /*
    ─────────────────────────────
    RULE 4: New account risk
    ─────────────────────────────
    */
    $stmt = $conn->prepare("
        SELECT created_at FROM users WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $created = $stmt->get_result()->fetch_assoc()['created_at'];

    if (strtotime($created) > strtotime("-7 days")) {
        $risk += 20;
    }

    return min($risk, 100);
}
if ($risk >= 50) {
    $stmt = $conn->prepare("
        UPDATE users SET risk_score = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ii", $risk, $withdrawal['seller_id']);
    $stmt->execute();
}