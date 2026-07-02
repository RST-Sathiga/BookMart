<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['ban', 'activate'], true)) {
    header('Location: ../users.php');
    exit();
}

$user_check = $conn->prepare('SELECT role FROM users WHERE user_id = ?');
$user_check->bind_param('i', $id);
$user_check->execute();
$user = $user_check->get_result()->fetch_assoc();

if (!$user || $user['role'] === 'admin') {
    header('Location: ../users.php');
    exit();
}

$status = $action === 'ban' ? 'banned' : 'active';
$stmt = $conn->prepare('UPDATE users SET status = ? WHERE user_id = ?');
$stmt->bind_param('si', $status, $id);
$stmt->execute();

header('Location: ../users.php');
exit();
