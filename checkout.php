<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/payfast_helper.php';
require_once __DIR__ . '/includes/payfast_config.php';

require_login();

if (!isset($_SESSION['checkout_item'])) {
    header('Location: ' . site_url('cart.php'));
    exit();
}

if (!isset($_SESSION['checkout_token'], $_GET['token']) || !hash_equals($_SESSION['checkout_token'], $_GET['token'])) {
    die('Invalid checkout session.');
}

$user_id = current_user_id();
$product_id = (int) ($_SESSION['checkout_item']['product_id'] ?? 0);

if ($product_id <= 0) {
    die('Invalid product reference.');
}

$stmt = $conn->prepare('
    SELECT p.id, p.title, p.price, p.user_id AS seller_id, p.status, c.pickup_point
    FROM products p
    JOIN campuses c ON p.campus_id = c.id
    WHERE p.id = ?
    LIMIT 1
');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product || $product['status'] !== 'approved') {
    die('This textbook is not available for checkout.');
}

if ((int) $product['seller_id'] === $user_id) {
    die('You cannot buy your own listing.');
}

$total = (float) $product['price'];
if ($total <= 0) {
    die('Invalid product price.');
}

$fees = calculate_commission($total);
$transaction_reference = 'TXN' . strtoupper(uniqid());

$existing = $conn->prepare('
    SELECT id
    FROM orders
    WHERE buyer_id = ? AND product_id = ? AND payment_status = "pending"
    LIMIT 1
');
$existing->bind_param('ii', $user_id, $product_id);
$existing->execute();
$pending = $existing->get_result()->fetch_assoc();

if ($pending) {
    $order_id = (int) $pending['id'];
} else {
    $insert = $conn->prepare('
        INSERT INTO orders
            (buyer_id, seller_id, product_id, amount, commission, seller_payout,
             payment_method, payment_status, transaction_reference, order_status, pickup_location)
        VALUES
            (?, ?, ?, ?, ?, ?, "PayFast", "pending", ?, "processing", ?)
    ');
    $insert->bind_param(
        'iiidddss',
        $user_id,
        $product['seller_id'],
        $product_id,
        $total,
        $fees['commission'],
        $fees['seller_payout'],
        $transaction_reference,
        $product['pickup_point']
    );
    $insert->execute();
    $order_id = (int) $conn->insert_id;
}

$_SESSION['pending_order_id'] = $order_id;
unset($_SESSION['checkout_item'], $_SESSION['checkout_token']);

if (!isset($config) || !is_array($config)) {
    die('Payment configuration missing or invalid.');
}

$data = [
    'merchant_id' => $config['merchant_id'],
    'merchant_key' => $config['merchant_key'],
    'return_url' => site_url('order_success.php?order_id=' . $order_id),
    'cancel_url' => site_url('payment_handler.php?status=cancel'),
    'notify_url' => site_url('itn.php'),
    'm_payment_id' => (string) $order_id,
    'amount' => number_format($total, 2, '.', ''),
    'item_name' => $product['title'],
];

ksort($data);
$signature_string = '';
foreach ($data as $key => $value) {
    if ($value !== '') {
        $signature_string .= $key . '=' . urlencode($value) . '&';
    }
}
$signature_string = rtrim($signature_string, '&');

if (!empty($config['passphrase'])) {
    $signature_string .= '&passphrase=' . urlencode($config['passphrase']);
}

$signature = md5($signature_string);
$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="feature-card p-4">
                <h1 class="section-title h4">Secure Checkout</h1>
                <p class="text-muted">You are about to purchase:</p>
                <h2 class="h5"><?= htmlspecialchars($product['title']) ?></h2>
                <p class="price-tag"><?= format_currency($total) ?></p>

                <form method="post" action="<?= site_url('process_payment.php') ?>" class="mt-4">
                    <input type="hidden" name="order_id" value="<?= (int) $order_id ?>">
                    <div class="mb-3">
                        <label class="form-label">PayFast Account Holder</label>
                        <input type="text" name="payfast_account_holder" class="form-control" value="<?= htmlspecialchars($_SESSION['fullname'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PayFast Account Email</label>
                        <input type="email" name="payfast_account_email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-lg">Pay with PayFast</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
