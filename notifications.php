<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$user_id = current_user_id();

$stmt = $conn->prepare('
    SELECT id, message, type, severity, related_id, created_at, is_read
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

$mark_read = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
$mark_read->bind_param('i', $user_id);
$mark_read->execute();

$page_title = 'Notifications';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="section-title">Notifications</h1>

    <?php if ($notifications->num_rows === 0): ?>
        <div class="feature-card p-4 text-center">
            <i class="bi bi-bell display-4 text-muted"></i>
            <h2 class="h5 mt-3">No notifications</h2>
        </div>
    <?php else: ?>
        <div class="feature-card p-3">
            <?php while ($row = $notifications->fetch_assoc()): ?>
                <?php
                $chat_link = '';
                if (($row['type'] ?? '') === 'order' && !empty($row['related_id'])) {
                    $order_id = (int) $row['related_id'];
                    $order_stmt = $conn->prepare('
                        SELECT buyer_id, seller_id
                        FROM orders
                        WHERE id = ? AND (buyer_id = ? OR seller_id = ?)
                        LIMIT 1
                    ');
                    $order_stmt->bind_param('iii', $order_id, $user_id, $user_id);
                    $order_stmt->execute();
                    $order = $order_stmt->get_result()->fetch_assoc();

                    if ($order) {
                        $partner_id = ((int) $order['buyer_id'] === $user_id)
                            ? (int) $order['seller_id']
                            : (int) $order['buyer_id'];
                        $chat_link = site_url('chat.php?user_id=' . $partner_id . '&order_id=' . $order_id . '#textbook-reference');
                    }
                }
                ?>
                <div class="border-bottom py-3">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <span class="badge bg-<?= $row['type'] === 'order' ? 'primary' : 'secondary' ?>">
                                <?= htmlspecialchars(ucfirst($row['type'] ?? 'system')) ?>
                            </span>
                            <?php if (!$row['is_read']): ?>
                                <span class="badge bg-danger">New</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?= htmlspecialchars($row['created_at']) ?></small>
                    </div>
                    <p class="mb-2 mt-2"><?= htmlspecialchars($row['message']) ?></p>
                    <?php if ($chat_link): ?>
                        <a href="<?= htmlspecialchars($chat_link) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-chat-dots me-1"></i>Open textbook chat
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
