<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$user_id = current_user_id();

$sql = '
    SELECT
        IF(m.sender_id = ?, m.receiver_id, m.sender_id) AS partner_id,
        u.fullname,
        u.username,
        u.profile_image,
        MAX(m.created_at) AS last_message_at,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    JOIN users u ON u.user_id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY partner_id, u.fullname, u.username, u.profile_image
    ORDER BY last_message_at DESC
';
$conversations = $conn->prepare($sql);
$conversations->bind_param('iiiii', $user_id, $user_id, $user_id, $user_id, $user_id);
$conversations->execute();
$list = $conversations->get_result();

$page_title = 'Chats';
$account_page = 'chats';
$back_url = site_url('account.php');
$back_label = 'Account Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<h1 class="section-title mb-1"><i class="bi bi-chat-dots me-2"></i>Chats</h1>
<p class="text-muted mb-4">Coordinate campus pickup times with buyers and sellers.</p>

<?php if ($list->num_rows === 0): ?>
    <div class="empty-state feature-card">
        <i class="bi bi-chat-dots display-4 text-muted"></i>
        <h4 class="mt-3">No conversations yet</h4>
        <p>Message a seller from a textbook listing or order to get started.</p>
        <a href="marketplace.php" class="btn btn-primary">Browse Textbooks</a>
    </div>
<?php else: ?>
    <div class="feature-card">
        <?php while ($row = $list->fetch_assoc()): ?>
            <?php
            $preview = $conn->prepare('
                SELECT message, order_id FROM messages
                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
                ORDER BY created_at DESC LIMIT 1
            ');
            $pid = (int) $row['partner_id'];
            $preview->bind_param('iiii', $user_id, $pid, $pid, $user_id);
            $preview->execute();
            $preview_row = $preview->get_result()->fetch_assoc();
            $chat_href = 'chat.php?user_id=' . $pid;
            if (!empty($preview_row['order_id'])) {
                $chat_href .= '&order_id=' . (int) $preview_row['order_id'] . '#textbook-reference';
            }
            ?>
            <a href="<?= htmlspecialchars($chat_href) ?>" class="d-block text-decoration-none text-dark border-bottom p-3 chat-list-item">
                <div class="d-flex align-items-start gap-3">
                    <img src="<?= profile_image_url($row['profile_image'] ?? null) ?>" alt="" class="rounded-circle flex-shrink-0" width="44" height="44" style="object-fit:cover">
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($row['fullname']) ?></strong>
                                <span class="text-muted small">@<?= htmlspecialchars($row['username']) ?></span>
                            </div>
                            <div class="text-end flex-shrink-0 ms-2">
                                <small class="text-muted d-block"><?= date('M j, H:i', strtotime($row['last_message_at'])) ?></small>
                                <?php if ((int) $row['unread_count'] > 0): ?>
                                    <span class="badge bg-danger"><?= (int) $row['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="mb-0 text-muted small text-truncate"><?= htmlspecialchars($preview_row['message'] ?? '') ?></p>
                    </div>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
