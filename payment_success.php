<?php
require_once __DIR__ . '/includes/auth.php';

require_login();

$page_title = "Payment Success";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center">

    <i class="bi bi-check-circle-fill text-success display-4"></i>

    <h2 class="mt-3">Payment Successful</h2>

    <p class="text-muted">
        Your order is confirmed. Use the textbook chat to arrange campus pickup.
    </p>

    <a href="orders.php" class="btn btn-primary me-2">View Orders</a>
    <a href="marketplace.php" class="btn btn-outline-primary">Continue Shopping</a>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
