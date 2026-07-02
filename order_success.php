<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$order_id = (int) ($_GET['order_id'] ?? 0);
$order = null;

if ($order_id > 0) {
    $stmt = $conn->prepare('
        SELECT orders.*, products.title, users.fullname AS seller_name
        FROM orders
        JOIN products ON products.id = orders.product_id
        JOIN users ON users.user_id = orders.seller_id
        WHERE orders.id = ? AND orders.buyer_id = ?
        LIMIT 1
    ');
    $user_id = current_user_id();
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

$page_title = 'Order Status';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="feature-card p-5 text-center">
        <?php if ($order && $order['payment_status'] === 'paid'): ?>
            <?php unset($_SESSION['cart'][(int) $order['product_id']]); ?>
            <i class="bi bi-check-circle-fill text-success display-4"></i>
            <h1 class="h3 mt-3">Payment Successful</h1>
            <p class="text-muted mb-1">Order #<?= (int) $order['id'] ?> for <?= htmlspecialchars($order['title']) ?> is paid.</p>
            <p class="mb-4">Use the textbook chat to arrange your campus pickup with the seller.</p>
            <a href="<?= site_url('chat.php?user_id=' . (int) $order['seller_id'] . '&order_id=' . (int) $order['id'] . '#textbook-reference') ?>" class="btn btn-primary">
                Contact <?= htmlspecialchars($order['seller_name']) ?> About Pickup
            </a>
        <?php elseif ($order): ?>
            <i class="bi bi-hourglass-split text-warning display-4"></i>
            <h1 class="h3 mt-3">Payment Pending</h1>
            <p class="text-muted mb-4">We are waiting for PayFast confirmation. If payment was completed, this page will update after the secure payment notification is received.</p>
            <a href="<?= site_url('pay_order.php?order_id=' . (int) $order['id']) ?>" class="btn btn-success">Pay Now</a>
        <?php else: ?>
            <i class="bi bi-exclamation-circle text-warning display-4"></i>
            <h1 class="h3 mt-3">Order Not Found</h1>
            <p class="text-muted mb-4">We could not find that order for your account.</p>
        <?php endif; ?>

        <a href="<?= site_url('orders.php') ?>" class="btn btn-outline-primary">View Orders</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
