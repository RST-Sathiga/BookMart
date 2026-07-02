<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header('Location: ../products.php');
    exit();
}

$status = $action === 'approve' ? 'approved' : 'rejected';
$stmt = $conn->prepare('UPDATE products SET status = ? WHERE id = ?');
$stmt->bind_param('si', $status, $id);
$stmt->execute();

header('Location: ../products.php');
exit();
