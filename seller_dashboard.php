<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$page_title = 'Seller Dashboard';
$account_page = 'seller';
$analytics = get_seller_analytics($conn, current_user_id());

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<h1 class="section-title">Seller Dashboard</h1>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="feature-card p-3">
            <small class="text-muted">Total Listings</small>
            <div class="h3 mb-0"><?= (int) $analytics['total_listings'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="feature-card p-3">
            <small class="text-muted">Active Listings</small>
            <div class="h3 mb-0"><?= (int) $analytics['active_listings'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="feature-card p-3">
            <small class="text-muted">Sales</small>
            <div class="h3 mb-0"><?= (int) $analytics['total_sales'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="feature-card p-3">
            <small class="text-muted">Earnings</small>
            <div class="h3 mb-0"><?= format_currency((float) $analytics['total_earnings']) ?></div>
        </div>
    </div>
</div>

<div class="feature-card p-4">
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= site_url('sell.php') ?>" class="btn btn-primary">Upload Textbook</a>
        <a href="<?= site_url('seller_orders.php') ?>" class="btn btn-outline-primary">Seller Orders</a>
        <a href="<?= site_url('wallet.php') ?>" class="btn btn-outline-secondary">Wallet</a>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
?>
