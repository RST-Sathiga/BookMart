<?php

$account_user = get_user_by_id($conn, current_user_id());
$account_page = $account_page ?? '';
$back_url = $back_url ?? null;
$back_label = $back_label ?? 'Back';
?>

<div class="container py-4 account-layout">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require __DIR__ . '/account_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <?php if ($back_url): ?>
                <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($back_label) ?>
                </a>
            <?php endif; ?>
