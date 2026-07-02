<?php

$account_user = $account_user ?? get_user_by_id($conn, current_user_id());
$account_page = $account_page ?? '';
$account_unread = get_unread_message_count($conn, current_user_id());
$missing = get_user_missing_fields($conn, $account_user);
?>

<aside class="account-sidebar">
    <div class="account-profile-header text-center mb-4">
        <div class="account-avatar-wrap mx-auto mb-2">
            <img src="<?= profile_image_url($account_user['profile_image'] ?? null) ?>"
                 alt="Profile"
                 class="account-avatar"
                 id="accountSidebarAvatar"
                 width="96" height="96">
        </div>
        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($account_user['fullname']) ?></h6>
        <small class="text-muted">@<?= htmlspecialchars($account_user['username']) ?></small>
    </div>

    <?php if (!empty($missing)): ?>
        <div class="account-warnings mb-3">
            <?php foreach ($missing as $warning): ?>
                <div class="alert alert-warning py-2 px-2 small mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($warning['message']) ?>
                    <?php if (!empty($warning['link'])): ?>
                        <a href="<?= site_url($warning['link']) ?>" class="alert-link d-block mt-1"><?= htmlspecialchars($warning['action']) ?></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <nav class="account-nav nav flex-column">
        <a class="nav-link <?= $account_page === 'dashboard' ? 'active' : '' ?>" href="<?= site_url('account.php') ?>">
            <i class="bi bi-grid me-2"></i>Account Home
        </a>
        <a class="nav-link <?= $account_page === 'personal' ? 'active' : '' ?>" href="<?= site_url('personal_info.php') ?>">
            <i class="bi bi-person-lines-fill me-2"></i>Personal Information
        </a>
        <a class="nav-link <?= $account_page === 'photo' ? 'active' : '' ?>" href="<?= site_url('profile.php') ?>">
            <i class="bi bi-camera me-2"></i>Profile Photo
        </a>
        <a class="nav-link <?= $account_page === 'orders' ? 'active' : '' ?>" href="<?= site_url('orders.php') ?>">
            <i class="bi bi-bag-check me-2"></i>My Orders
        </a>
        <a class="nav-link <?= $account_page === 'wallet' ? 'active' : '' ?>" href="<?= site_url('wallet.php') ?>">
            <i class="bi bi-wallet2 me-2"></i>Wallet
        </a>
        <a class="nav-link <?= $account_page === 'chats' ? 'active' : '' ?>" href="<?= site_url('messages.php') ?>">
            <i class="bi bi-chat-dots me-2"></i>Chats
            <?php if ($account_unread > 0): ?>
                <span class="badge bg-danger ms-1"><?= $account_unread ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-link <?= $account_page === 'cart' ? 'active' : '' ?>" href="<?= site_url('cart.php') ?>">
            <i class="bi bi-cart3 me-2"></i>Cart
        </a>
    </nav>

    <div class="account-sidebar-footer mt-4 pt-3 border-top">
        <a class="btn btn-gold w-100 <?= ($account_page ?? '') === 'seller' ? 'active' : '' ?>" href="<?= site_url('seller_dashboard.php') ?>">
            <i class="bi bi-graph-up me-1"></i>Seller Dashboard
        </a>
    </div>
</aside>
