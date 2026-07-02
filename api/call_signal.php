<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

header('Content-Type: application/json');

$user_id = current_user_id();
$chat_key = trim($_POST['chat_key'] ?? $_GET['chat_key'] ?? '');
$signal_type = trim($_POST['type'] ?? '');
$payload = trim($_POST['payload'] ?? '');
$since_id = (int) ($_GET['since_id'] ?? 0);

if ($chat_key === '' || !preg_match('/^chat_\d+_\d+$/', $chat_key)) {
    echo json_encode(['error' => 'Invalid chat room.']);
    exit();
}

if (!table_exists($conn, 'call_signals')) {
    echo json_encode(['error' => 'Call service unavailable.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($signal_type === '' || $payload === '') {
        echo json_encode(['error' => 'Missing signal data.']);
        exit();
    }

    $allowed = ['offer', 'answer', 'ice', 'hangup', 'ringing'];
    if (!in_array($signal_type, $allowed, true)) {
        echo json_encode(['error' => 'Invalid signal type.']);
        exit();
    }

    $stmt = $conn->prepare('INSERT INTO call_signals (chat_key, sender_id, signal_type, payload) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('siss', $chat_key, $user_id, $signal_type, $payload);
    $stmt->execute();

    echo json_encode(['ok' => true, 'id' => $conn->insert_id]);
    exit();
}

$stmt = $conn->prepare('
    SELECT id, sender_id, signal_type, payload, created_at
    FROM call_signals
    WHERE chat_key = ? AND id > ? AND sender_id != ?
    ORDER BY id ASC
    LIMIT 20
');
$stmt->bind_param('sii', $chat_key, $since_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$signals = [];
while ($row = $result->fetch_assoc()) {
    $signals[] = $row;
}

echo json_encode(['signals' => $signals]);
