<?php

require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin();

$action = $_POST['action'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    exit('Invalid request');
}

/*
────────────────────────────
FETCH WITHDRAWAL
────────────────────────────
*/

$stmt = $conn->prepare("
    SELECT * FROM withdrawals WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$withdrawal = $stmt->get_result()->fetch_assoc();

if (!$withdrawal || $withdrawal['status'] !== 'pending') {
    exit('Already processed or invalid');
}

/*
────────────────────────────
APPROVE
────────────────────────────
*/
if ($action === 'approve') {

    $conn->begin_transaction();

    try {

        // update withdrawal
        $update = $conn->prepare("
            UPDATE withdrawals 
            SET status = 'completed'
            WHERE id = ?
        ");
        $update->bind_param("i", $id);
        $update->execute();

        // audit log (VERY IMPORTANT)
        $log = $conn->prepare("
            INSERT INTO audit_logs (admin_id, action)
            VALUES (?, ?)
        ");

        $admin_id = $_SESSION['user_id'];
        $msg = "Approved withdrawal ID $id";

        $log->bind_param("is", $admin_id, $msg);
        $log->execute();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        exit("Error processing request");
    }
}

/*
────────────────────────────
REJECT
────────────────────────────
*/
if ($action === 'reject') {

    $conn->begin_transaction();

    try {

        $update = $conn->prepare("
            UPDATE withdrawals 
            SET status = 'rejected'
            WHERE id = ?
        ");
        $update->bind_param("i", $id);
        $update->execute();

        $log = $conn->prepare("
            INSERT INTO audit_logs (admin_id, action)
            VALUES (?, ?)
        ");

        $admin_id = $_SESSION['user_id'];
        $msg = "Rejected withdrawal ID $id";

        $log->bind_param("is", $admin_id, $msg);
        $log->execute();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        exit("Error processing request");
    }
}

header("Location: ../withdrawals.php");
exit();