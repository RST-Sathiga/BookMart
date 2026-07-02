<?php
require_once __DIR__ . '/includes/db.php';

$orderRef = $_GET['order_ref'] ?? null;

if ($orderRef) {
    $stmt = $conn->prepare("UPDATE orders SET status='failed' WHERE order_ref=?");
    $stmt->bind_param("s", $orderRef);
    $stmt->execute();
}

echo "<h2>Payment cancelled</h2>";