<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$user_id = current_user_id();
$user = get_user_by_id($conn, $user_id);

$listing_count = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE user_id = ?');
$listing_count->bind_param('i', $user_id);
$listing_count->execute();
$listings = (int) $listing_count->get_result()->fetch_assoc()['total'];

$order_count = $conn->prepare('SELECT COUNT(*) AS total FROM orders WHERE (buyer_id = ? OR seller_id = ?) AND payment_status = "paid"');
$order_count->bind_param('ii', $user_id, $user_id);
$order_count->execute();
$orders = (int) $order_count->get_result()->fetch_assoc()['total'];

$uni_name = '';
$campus_name = '';
if ($user['university']) {
    $uni = $conn->query('SELECT name FROM universities WHERE id = ' . (int) $user['university']);
    $uni_name = $uni->fetch_assoc()['name'] ?? '';
}
if ($user['campus']) {
    $camp = $conn->query('SELECT name FROM campuses WHERE id = ' . (int) $user['campus']);
    $campus_name = $camp->fetch_assoc()['name'] ?? '';
}

$page_title = 'Account';
$account_page = 'dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<h1 class="section-title mb-1">Welcome, <?= htmlspecialchars($user['fullname']) ?></h1>
<p class="text-muted mb-4">
    <?= htmlspecialchars($uni_name) ?>
    <?php if ($campus_name): ?> · <?= htmlspecialchars($campus_name) ?><?php endif; ?>
    <?php if (!empty($user['course'])): ?> · <?= htmlspecialchars($user['course']) ?><?php endif; ?>
</p>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="dashboard-card p-4 h-100">
            <div class="stat-icon mb-3"><i class="bi bi-wallet2"></i></div>
            <p class="text-muted mb-1">Wallet Balance</p>
            <div class="wallet-balance"><?= format_currency((float) $user['wallet_balance']) ?></div>
            <a href="wallet.php" class="btn btn-sm btn-outline-primary mt-3">Manage Wallet</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card p-4 h-100">
            <div class="stat-icon mb-3"><i class="bi bi-book"></i></div>
            <p class="text-muted mb-1">Your Listings</p>
            <h2 class="text-primary"><?= $listings ?></h2>
            <a href="sell.php" class="btn btn-sm btn-gold mt-3">Sell Textbook</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="dashboard-card p-4 h-100">
            <div class="stat-icon mb-3"><i class="bi bi-bag-check"></i></div>
            <p class="text-muted mb-1">Total Orders</p>
            <h2 class="text-primary"><?= $orders ?></h2>
            <a href="orders.php" class="btn btn-sm btn-outline-primary mt-3">View Orders</a>
        </div>
    </div>
</div>

<div class="feature-card p-4">
    <h5 class="mb-3">How Campus Pickup Works</h5>
    <ol class="mb-0 ps-3">
        <li class="mb-2">Buy a textbook and pay via PayFast</li>
        <li class="mb-2">Open the textbook chat created for you and the seller</li>
        <li class="mb-2">Chat with the seller to set a pickup time</li>
        <li class="mb-2">Meet on campus at the listed pickup point</li>
        <li>The seller confirms pickup after checking your identity</li>
    </ol>
</div>

<div class="mt-4 text-center">
    <a href="marketplace.php" class="btn btn-success btn-lg px-5">
        Go to Marketplace
    </a>
</div>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
