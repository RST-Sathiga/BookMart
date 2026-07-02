<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$user_id = current_user_id();
$partner_id = (int) ($_GET['user_id'] ?? 0);
$order_id = (int) ($_GET['order_id'] ?? 0);
$product_id = (int) ($_GET['product_id'] ?? 0);
$report_message = '';
$report_type = 'info';

if ($partner_id <= 0 || $partner_id === $user_id) {
    header('Location: messages.php');
    exit();
}

$partner = get_user_by_id($conn, $partner_id);
if (!$partner) {
    header('Location: messages.php');
    exit();
}

$referenced_book = null;
if ($order_id > 0) {
    $book_stmt = $conn->prepare('
        SELECT orders.id AS order_id, orders.amount, orders.pickup_location,
               products.id AS product_id, products.title, products.author, products.course_code,
               products.image, products.book_condition
        FROM orders
        JOIN products ON products.id = orders.product_id
        WHERE orders.id = ?
          AND (orders.buyer_id = ? OR orders.seller_id = ?)
        LIMIT 1
    ');
    $book_stmt->bind_param('iii', $order_id, $user_id, $user_id);
    $book_stmt->execute();
    $referenced_book = $book_stmt->get_result()->fetch_assoc();
} elseif ($product_id > 0) {
    $book_stmt = $conn->prepare('
        SELECT id AS product_id, title, author, course_code, image, book_condition, price AS amount
        FROM products
        WHERE id = ?
        LIMIT 1
    ');
    $book_stmt->bind_param('i', $product_id);
    $book_stmt->execute();
    $referenced_book = $book_stmt->get_result()->fetch_assoc();
}

if (isset($_POST['send_message'])) {
    $message_text = trim($_POST['message']);
    $linked_order = (int) ($_POST['order_id'] ?? 0);

    if ($message_text !== '') {
        $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, order_id, message) VALUES (?, ?, ?, ?)');
        $order_param = $linked_order > 0 ? $linked_order : null;
        $stmt->bind_param('iiis', $user_id, $partner_id, $order_param, $message_text);
        $stmt->execute();
    }

    $redirect = 'chat.php?user_id=' . $partner_id;
    if ($linked_order > 0) {
        $redirect .= '&order_id=' . $linked_order;
    }
    header('Location: ' . $redirect);
    exit();
}

if (isset($_POST['report_user'])) {
    $reason = trim($_POST['report_reason'] ?? '');
    if ($reason === '') {
        $report_message = 'Describe why you are reporting this user.';
        $report_type = 'danger';
    } elseif (table_exists($conn, 'user_reports')) {
        $context_type = $order_id > 0 ? 'order' : ($product_id > 0 ? 'product' : 'chat');
        $context_id = $order_id > 0 ? $order_id : ($product_id > 0 ? $product_id : 0);
        $stmt = $conn->prepare('INSERT INTO user_reports (reporter_id, reported_id, reason, context_type, context_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iissi', $user_id, $partner_id, $reason, $context_type, $context_id);
        $stmt->execute();
        $report_message = 'Report submitted to the disputes department. The expected turnaround time is 1 week.';
        $report_type = 'success';
    }
}

$mark_read = $conn->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?');
$mark_read->bind_param('ii', $user_id, $partner_id);
$mark_read->execute();

if ($order_id > 0) {
    $msg_stmt = $conn->prepare('
        SELECT messages.*, sender.fullname AS sender_name
        FROM messages
        JOIN users sender ON messages.sender_id = sender.user_id
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
          AND (order_id = ? OR order_id IS NULL)
        ORDER BY messages.created_at ASC
    ');
    $msg_stmt->bind_param('iiiii', $user_id, $partner_id, $partner_id, $user_id, $order_id);
} else {
    $msg_stmt = $conn->prepare('
        SELECT messages.*, sender.fullname AS sender_name
        FROM messages
        JOIN users sender ON messages.sender_id = sender.user_id
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY messages.created_at ASC
    ');
    $msg_stmt->bind_param('iiii', $user_id, $partner_id, $partner_id, $user_id);
}

$msg_stmt->execute();
$conversation = $msg_stmt->get_result();

$chat_key = chat_room_key($user_id, $partner_id);
$page_title = 'Chat';
$back_url = site_url('messages.php');
$back_label = 'All Chats';
$load_chat_call = true;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($back_label) ?>
    </a>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="feature-card p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= profile_image_url($partner['profile_image'] ?? null) ?>" alt="" class="rounded-circle" width="48" height="48" style="object-fit:cover">
                        <div>
                            <h4 class="mb-0"><?= htmlspecialchars($partner['fullname']) ?></h4>
                            <small class="text-muted">@<?= htmlspecialchars($partner['username']) ?></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="startCallBtn" data-partner-id="<?= $partner_id ?>" data-chat-key="<?= htmlspecialchars($chat_key) ?>" title="Voice call (in-chat only)">
                            <i class="bi bi-telephone-fill"></i> Call
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="bi bi-flag"></i> Report
                        </button>
                    </div>
                </div>

                <?php if ($referenced_book): ?>
                    <?php
                    $image = trim($referenced_book['image'] ?? '');
                    $image_url = $image !== '' ? site_url('uploads/' . ltrim($image, '/')) : site_url('assets/img/marketplace-banner.jpg');
                    ?>
                    <div id="textbook-reference" class="alert alert-bookmart p-3 mb-3">
                        <div class="d-flex gap-3 align-items-center">
                            <img src="<?= htmlspecialchars($image_url) ?>" alt="" width="72" height="72" class="rounded" style="object-fit:cover">
                            <div class="flex-grow-1">
                                <div class="small text-uppercase text-muted fw-semibold">Referenced textbook</div>
                                <strong><?= htmlspecialchars($referenced_book['title']) ?></strong>
                                <div class="small text-muted">
                                    <?php if (!empty($referenced_book['author'])): ?>
                                        <?= htmlspecialchars($referenced_book['author']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($referenced_book['course_code'])): ?>
                                        <?= !empty($referenced_book['author']) ? ' · ' : '' ?><?= htmlspecialchars($referenced_book['course_code']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="small">
                                    <?= format_currency((float) $referenced_book['amount']) ?>
                                    <?php if (!empty($referenced_book['pickup_location'])): ?>
                                        · Pickup: <?= htmlspecialchars($referenced_book['pickup_location']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($order_id > 0): ?>
                    <div class="alert alert-bookmart small">
                        This chat is linked to order #<?= $order_id ?>. Use it to agree on a campus pickup time and location.
                    </div>
                <?php elseif ($product_id > 0): ?>
                    <div class="alert alert-bookmart small">
                        Discuss this textbook listing before purchase. After payment, use order chat to schedule pickup.
                    </div>
                <?php endif; ?>

                <?php if ($report_message): ?>
                    <div class="alert alert-<?= htmlspecialchars($report_type) ?>"><?= htmlspecialchars($report_message) ?></div>
                <?php endif; ?>

                <div class="chat-call-panel d-none mb-3 p-3 border rounded bg-light" id="chatCallPanel">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong id="callStatusText">Connecting…</strong>
                            <div class="small text-muted">Voice calls are only available inside this chat.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger" id="endCallBtn"><i class="bi bi-telephone-x"></i> End</button>
                    </div>
                    <audio id="remoteAudio" autoplay playsinline class="d-none"></audio>
                </div>

                <div class="chat-box mb-3" id="chatBox">
                    <?php if ($conversation->num_rows === 0): ?>
                        <p class="text-muted text-center mt-5">No messages yet. Say hello and suggest a pickup time.</p>
                    <?php else: ?>
                        <?php while ($msg = $conversation->fetch_assoc()): ?>
                            <div class="chat-message <?= (int) $msg['sender_id'] === $user_id ? 'sent' : 'received' ?>">
                                <div class="small fw-semibold mb-1"><?= htmlspecialchars($msg['sender_name']) ?></div>
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                <div class="small opacity-75 mt-1"><?= date('M j, H:i', strtotime($msg['created_at'])) ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="order_id" value="<?= $order_id ?>">
                    <div class="input-group">
                        <input type="text" name="message" class="form-control" placeholder="Suggest a pickup time or ask a question..." required autofocus>
                        <button type="submit" name="send_message" class="btn btn-primary"><i class="bi bi-send"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Report User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Report <?= htmlspecialchars($partner['fullname']) ?> for inappropriate behaviour. Reports are sent to the disputes department with a 1 week turnaround time.</p>
                    <textarea name="report_reason" class="form-control" rows="4" required placeholder="Describe what happened…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="report_user" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.BOOKMART_CHAT = {
        userId: <?= (int) $user_id ?>,
        partnerId: <?= (int) $partner_id ?>,
        chatKey: <?= json_encode($chat_key) ?>,
        signalUrl: <?= json_encode(site_url('api/call_signal.php')) ?>
    };
    const chatBox = document.getElementById('chatBox');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
