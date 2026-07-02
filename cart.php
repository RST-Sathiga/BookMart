<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function cart_item_quantity($item): int
{
    return empty($item) ? 0 : 1;
}

$_SESSION['cart'] = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid request. Please refresh and try again.');
    }

    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][(int) $_POST['product_id']]);
        header('Location: ' . site_url('cart.php'));
        exit();
    }

    if (isset($_POST['checkout'])) {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $qty = cart_item_quantity($_SESSION['cart'][$product_id] ?? 0);

        if ($product_id <= 0 || $qty <= 0) {
            $_SESSION['flash_message'] = 'Choose a valid cart item before checkout.';
            header('Location: ' . site_url('cart.php'));
            exit();
        }

        $_SESSION['checkout_item'] = [
            'product_id' => $product_id,
            'qty' => $qty,
        ];
        $_SESSION['checkout_token'] = bin2hex(random_bytes(16));

        header('Location: ' . site_url('checkout.php?token=' . $_SESSION['checkout_token']));
        exit();
    }
}

$cart = array_filter($_SESSION['cart'], fn($item) => cart_item_quantity($item) > 0);
$_SESSION['cart'] = $cart;

$products = [];
$total = 0.0;

if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("
        SELECT products.id, products.title, products.price, products.image, products.status,
               users.fullname AS seller_name
        FROM products
        JOIN users ON users.user_id = products.user_id
        WHERE products.id IN ($placeholders)
    ");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $qty = cart_item_quantity($cart[(int) $row['id']] ?? 0);
        $row['qty'] = $qty;
        $row['subtotal'] = (float) $row['price'] * $qty;
        $products[] = $row;
        $total += $row['subtotal'];
    }
}

$page_title = 'Cart';
$account_page = 'cart';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<h1 class="section-title">Shopping Cart</h1>

<?php if (!empty($_SESSION['flash_message'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<?php if (!$products): ?>
    <div class="feature-card p-4 text-center">
        <i class="bi bi-cart-x display-4 text-muted"></i>
        <h2 class="h5 mt-3">Your cart is empty</h2>
        <a href="<?= site_url('marketplace.php') ?>" class="btn btn-primary mt-2">Browse Marketplace</a>
    </div>
<?php else: ?>
    <form method="POST" class="feature-card p-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Textbook</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['title']) ?></strong>
                                <?php if ($p['status'] !== 'approved'): ?>
                                    <span class="badge bg-warning text-dark ms-1">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['seller_name']) ?></td>
                            <td><?= format_currency((float) $p['price']) ?></td>
                            <td>
                                <span class="badge bg-secondary">1</span>
                            </td>
                            <td><?= format_currency((float) $p['subtotal']) ?></td>
                            <td class="text-end">
                                <button type="submit" name="remove" value="1" class="btn btn-outline-danger btn-sm"
                                        formaction="<?= site_url('cart.php') ?>"
                                        onclick="this.form.product_id.value='<?= (int) $p['id'] ?>'">
                                    Remove
                                </button>
                                <button type="submit" name="checkout" value="1" class="btn btn-success btn-sm"
                                        <?= $p['status'] !== 'approved' ? 'disabled' : '' ?>
                                        onclick="this.form.product_id.value='<?= (int) $p['id'] ?>'">
                                    Checkout
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <input type="hidden" name="product_id" value="">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <a href="<?= site_url('marketplace.php') ?>" class="btn btn-outline-primary">Continue Shopping</a>
            <div class="d-flex align-items-center gap-3">
                <strong class="fs-5">Total: <?= format_currency($total) ?></strong>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
?>
