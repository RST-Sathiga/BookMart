<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . site_url('marketplace.php'));
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    die('Invalid request. Please refresh the page and try again.');
}

$product_id = (int) ($_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    header('Location: ' . site_url('marketplace.php'));
    exit();
}

$stmt = $conn->prepare('
    SELECT id, title, price, user_id, status
    FROM products
    WHERE id = ? AND status = "approved"
    LIMIT 1
');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    $_SESSION['flash_message'] = 'That textbook is no longer available.';
    header('Location: ' . site_url('marketplace.php'));
    exit();
}

if ((int) $product['user_id'] === current_user_id()) {
    $_SESSION['flash_message'] = 'You cannot add your own listing to cart.';
    header('Location: ' . site_url('product.php?id=' . $product_id));
    exit();
}

$_SESSION['cart'][$product_id] = [
    'quantity' => 1,
    'price' => (float) $product['price'],
    'title' => $product['title'],
];

$_SESSION['flash_message'] = 'Textbook added to cart.';
header('Location: ' . site_url('cart.php'));
exit();
