<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$seller_id = current_user_id();

$stmt = $conn->prepare("
    SELECT o.*, p.title, u.fullname AS buyer_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE o.seller_id = ?
    ORDER BY o.id DESC
");

$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result();

$page_title = "Seller Orders";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

<h2>Sold Items</h2>

<?php while ($order = $orders->fetch_assoc()): ?>

<div class="feature-card p-3 mb-3">

    <h5><?= htmlspecialchars($order['title']) ?></h5>

    <p><strong>Buyer:</strong> <?= htmlspecialchars($order['buyer_name']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['order_status']) ?></p>
    <a href="chat.php?user_id=<?= (int) $order['buyer_id'] ?>&order_id=<?= (int) $order['id'] ?>#textbook-reference" class="btn btn-sm btn-outline-primary">
        Message Buyer
    </a>

</div>

<?php endwhile; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
