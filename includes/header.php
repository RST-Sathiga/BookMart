<?php

require_once __DIR__ . '/auth.php';

$current_script = basename($_SERVER['PHP_SELF']);

$is_marketplace = $current_script === 'marketplace.php';

$is_account_area = in_array($current_script, [
    'account.php',
    'personal_info.php',
    'profile.php',
    'orders.php',
    'wallet.php',
    'messages.php',
    'seller_dashboard.php',
    'cart.php'
], true);

$page_title = $page_title ?? 'BookMart';
$body_class = $body_class ?? '';

$unread_count = 0;
$notification_count = 0;
$cart_count = 0;

if (function_exists('current_user_id') && current_user_id()) {
    $unread_count = get_unread_message_count($conn, current_user_id());
    $notification_count = get_unread_notification_count($conn, current_user_id());
    $cart_count = get_session_cart_count();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($page_title) ?> | BookMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= site_url('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        .navbar-bookmart {
            background: #1e1e2f;
        }

        .navbar-bookmart .nav-link,
        .navbar-bookmart .navbar-brand {
            color: #fff !important;
        }

        .navbar-bookmart .nav-link:hover {
            color: #ffc107 !important;
        }

        .active-link {
            font-weight: 600;
            color: #ffc107 !important;
        }
    </style>
</head>

<body class="<?= htmlspecialchars($body_class) ?>" data-site-url="<?= SITE_URL ?>">

<nav class="navbar navbar-expand-lg navbar-bookmart shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-book-half me-2"></i>
            BookMart
        </a>

        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link <?= $current_script === 'index.php' ? 'active-link' : '' ?>"
                       href="index.php">
                        <i class="bi bi-house-door me-1"></i>
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $is_marketplace ? 'active-link' : '' ?>"
                       href="marketplace.php">
                        <i class="bi bi-shop me-1"></i>
                        Marketplace
                    </a>
                </li>

                <?php if (function_exists('current_user_id') && current_user_id() && !is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_script === 'user_dashboard.php' ? 'active-link' : '' ?>"
                           href="user_dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (function_exists('current_user_id') && current_user_id()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="sell.php">
                            <i class="bi bi-plus-circle me-1"></i>
                            Sell Textbook
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

            <form class="d-flex me-3"
                  action="marketplace.php"
                  method="GET">

                <input class="form-control me-2"
                       type="search"
                       name="search"
                       placeholder="Search textbooks...">

                <button class="btn btn-warning" type="submit">
                    <i class="bi bi-search"></i>
                </button>

            </form>

            <ul class="navbar-nav align-items-lg-center">

                <?php if (function_exists('current_user_id') && current_user_id()): ?>

                    <li class="nav-item">
                        <a class="nav-link position-relative" href="messages.php">
                            <i class="bi bi-chat-dots fs-5"></i>

                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unread_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link position-relative" href="notifications.php">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $notification_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="bi bi-cart3 fs-5"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cart_count ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $is_account_area ? 'active-link' : '' ?>"
                           href="account.php">
                            <i class="bi bi-person-circle me-1"></i>
                            Account
                        </a>
                    </li>

                    <?php if (function_exists('is_admin') && is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/index.php">
                                <i class="bi bi-shield-lock me-1"></i>
                                Admin
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>
                            Logout
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-warning ms-lg-2" href="register.php">
                            Register
                        </a>
                    </li>

                <?php endif; ?>

            </ul>

        </div>
    </div>
</nav>

<main>
