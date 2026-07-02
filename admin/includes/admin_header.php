<?php

require_once __DIR__ . '/bootstrap.php';

$page_title = 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= site_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body data-admin-logout-url="<?= site_url('admin/logout_beacon.php') ?>">
<nav class="navbar navbar-bookmart shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="<?= site_url('admin/index.php') ?>">
            <i class="bi bi-shield-lock me-2"></i><?= SITE_NAME ?> Admin
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white small"><?= htmlspecialchars($_SESSION['fullname']) ?></span>
            <a href="<?= site_url('logout.php') ?>" class="btn btn-sm btn-gold">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 admin-sidebar p-3">
            <nav class="nav flex-column">
                <a class="nav-link admin-stay" href="index.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a class="nav-link admin-stay" href="registered_users.php">
                    <i class="bi bi-people me-2"></i>Registered Users
                </a>
                <a class="nav-link admin-stay" href="<?= site_url('manage_users.php') ?>">
                    <i class="bi bi-person-gear me-2"></i>Manage Users
                </a>
                <a class="nav-link admin-stay" href="<?= site_url('manage_listings.php') ?>">
                    <i class="bi bi-book me-2"></i>Listings
                </a>
                <a class="nav-link admin-stay" href="<?= site_url('manage_withdrawals.php') ?>">
                    <i class="bi bi-cash-stack me-2"></i>Withdrawals
                </a>
            </nav>
        </div>
        <div class="col-md-9 col-lg-10 p-4">
