<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

if (is_admin()) {
    header('Location: ' . site_url('admin/index.php'));
    exit();
}

$user_id = current_user_id();
$user = get_user_by_id($conn, $user_id);

$stats = [
    'listings' => 0,
    'sold' => 0,
    'paid_orders' => 0,
    'wishlist' => 0,
    'cart' => get_session_cart_count(),
    'messages' => get_unread_message_count($conn, $user_id),
    'notifications' => get_unread_notification_count($conn, $user_id),
    'wallet' => (float) ($user['wallet_balance'] ?? 0),
];

$stmt = $conn->prepare('
    SELECT
        COUNT(*) AS listings,
        SUM(status = "sold") AS sold
    FROM products
    WHERE user_id = ?
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stats['listings'] = (int) ($row['listings'] ?? 0);
$stats['sold'] = (int) ($row['sold'] ?? 0);

$stmt = $conn->prepare('
    SELECT COUNT(*) AS total
    FROM orders
    WHERE (buyer_id = ? OR seller_id = ?) AND payment_status = "paid"
');
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$stats['paid_orders'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);

if (table_exists($conn, 'wishlist')) {
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM wishlist WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stats['wishlist'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
}

$recent_listings = $conn->prepare('
    SELECT id, title, price, status, created_at
    FROM products
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
');
$recent_listings->bind_param('i', $user_id);
$recent_listings->execute();
$recent_listings = $recent_listings->get_result();

$recent_orders = $conn->prepare('
    SELECT orders.id, orders.amount, orders.payment_status, orders.order_status, orders.created_at, products.title
    FROM orders
    JOIN products ON products.id = orders.product_id
    WHERE (orders.buyer_id = ? OR orders.seller_id = ?) AND orders.payment_status = "paid"
    ORDER BY orders.created_at DESC
    LIMIT 5
');
$recent_orders->bind_param('ii', $user_id, $user_id);
$recent_orders->execute();
$recent_orders = $recent_orders->get_result();

$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="section-title h4 mb-1">Student Dashboard</h1>
            <p class="text-muted mb-0 small">Listings, paid orders, wallet, messages, and campus pickup activity.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('sell.php') ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Upload Textbook
            </a>
            <a href="<?= site_url('marketplace.php') ?>" class="btn btn-outline-primary btn-sm">
                Browse Marketplace
            </a>
        </div>
    </div>

    <?php if ($user): ?>
        <div class="admin-card p-4 mb-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <img src="<?= profile_image_url($user['profile_image'] ?? null) ?>" alt="" class="rounded-circle" width="80" height="80" style="object-fit:cover">
                </div>
                <div class="col">
                    <h2 class="h5 mb-1"><?= htmlspecialchars($user['fullname']) ?></h2>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($user['email']) ?> · Student</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2"><div class="admin-card p-3 text-center"><p class="text-muted small mb-1">Books Listed</p><h3 class="mb-0 text-primary"><?= $stats['listings'] ?></h3></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="admin-card p-3 text-center"><p class="text-muted small mb-1">Books Sold</p><h3 class="mb-0 text-primary"><?= $stats['sold'] ?></h3></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="admin-card p-3 text-center"><p class="text-muted small mb-1">Paid Orders</p><h3 class="mb-0 text-primary"><?= $stats['paid_orders'] ?></h3></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="admin-card p-3 text-center"><p class="text-muted small mb-1">Cart</p><h3 class="mb-0 text-primary"><?= $stats['cart'] ?></h3></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="admin-card p-3 text-center"><p class="text-muted small mb-1">Messages</p><h3 class="mb-0 text-primary"><?= $stats['messages'] ?></h3></div></div>
        <div class="col-6 col-md-4 col-xl-2"><div class="admin-card p-3 text-center"><p class="text-muted small mb-1">Wallet</p><h3 class="mb-0 text-primary"><?= format_currency($stats['wallet']) ?></h3></div></div>
    </div>

    <div class="admin-card p-3 mb-4">
        <h2 class="h5 text-primary mb-3">Quick Actions</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('sell.php') ?>" class="btn btn-outline-primary btn-sm">Upload Textbook</a>
            <a href="<?= site_url('orders.php') ?>" class="btn btn-outline-primary btn-sm">Orders</a>
            <a href="<?= site_url('messages.php') ?>" class="btn btn-outline-primary btn-sm">Messages</a>
            <a href="<?= site_url('notifications.php') ?>" class="btn btn-outline-primary btn-sm">Notifications</a>
            <a href="<?= site_url('wallet.php') ?>" class="btn btn-outline-primary btn-sm">Wallet</a>
            <a href="<?= site_url('account.php') ?>" class="btn btn-outline-secondary btn-sm">Profile</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="admin-card p-3 h-100">
                <h2 class="h5 text-primary mb-3">Recent Listings</h2>
                <?php if ($recent_listings->num_rows === 0): ?>
                    <p class="text-muted mb-0">No listings yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Book</th><th>Price</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php while ($listing = $recent_listings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($listing['title']) ?></td>
                                        <td><?= format_currency((float) $listing['price']) ?></td>
                                        <td><span class="badge bg-secondary"><?= ucfirst($listing['status']) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="admin-card p-3 h-100">
                <h2 class="h5 text-primary mb-3">Recent Paid Orders</h2>
                <?php if ($recent_orders->num_rows === 0): ?>
                    <p class="text-muted mb-0">No paid orders yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Book</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['title']) ?></td>
                                        <td><?= format_currency((float) $order['amount']) ?></td>
                                        <td><?= order_status_badge($order['order_status']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
