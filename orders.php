<?php

require_once __DIR__ . '/includes/auth.php';

require_login();
require_profile_photo_for_pickup($conn);

$user_id = current_user_id();

$stmt = $conn->prepare('
    SELECT orders.*, products.title, products.image, products.author,
           buyer.fullname AS buyer_name,
           seller.fullname AS seller_name,
           campuses.name AS campus_name
    FROM orders
    JOIN products ON orders.product_id = products.id
    JOIN users buyer ON orders.buyer_id = buyer.user_id
    JOIN users seller ON orders.seller_id = seller.user_id
    JOIN campuses ON products.campus_id = campuses.id
    WHERE orders.buyer_id = ? OR orders.seller_id = ?
    ORDER BY orders.created_at DESC
');

$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

/*
────────────────────────────
FETCH + PREPROCESS DATA
────────────────────────────
*/
$orders = [];
$has_seller_pickups = false;

while ($row = $result->fetch_assoc()) {

    $row['is_buyer'] = ((int)$row['buyer_id'] === $user_id);

    // detect seller pickup cases in one pass
    if (!$row['is_buyer'] && $row['order_status'] === 'awaiting_pickup') {
        $has_seller_pickups = true;
    }

    $orders[] = $row;
}

/*
────────────────────────────
PAGE SETUP
────────────────────────────
*/
$page_title = 'My Orders';
$account_page = 'orders';
$back_url = site_url('account.php');
$back_label = 'Account Home';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="section-title mb-1">My Orders</h1>
        <p class="text-muted mb-0">Track payments, pickup arrangements, and completed trades.</p>
    </div>

    <?php if ($has_seller_pickups): ?>
        <a href="pickup.php" class="btn btn-gold">
            <i class="bi bi-check-circle me-1"></i>Confirm Pickup
        </a>
    <?php endif; ?>
</div>

<?php if (empty($orders)): ?>

    <div class="empty-state feature-card">
        <h4>No orders yet</h4>
        <a href="marketplace.php" class="btn btn-primary">Browse Textbooks</a>
    </div>

<?php else: ?>
    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="table-responsive feature-card p-3">
        <table class="table table-bookmart align-middle mb-0">
            <thead>
                <tr>
                    <th>Textbook</th>
                    <th>Role</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Pickup</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($orders as $order): ?>

                    <?php $is_buyer = $order['is_buyer']; ?>

                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($order['title']) ?></strong>
                            <div class="small text-muted">
                                <?= htmlspecialchars($order['campus_name']) ?>
                            </div>
                        </td>

                        <td><?= $is_buyer ? 'Buyer' : 'Seller' ?></td>

                        <td><?= format_currency((float)$order['amount']) ?></td>

                        <td>
                            <span class="badge bg-<?= 
                                $order['payment_status'] === 'paid' ? 'success' :
                                ($order['payment_status'] === 'failed' ? 'danger' : 'secondary')
                            ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= order_status_badge($order['order_status']) ?>
                        </td>

                        <td>
                            <?php if ($is_buyer && $order['payment_status'] === 'paid' && $order['order_status'] === 'awaiting_pickup'): ?>

                                <small class="text-muted">
                                    Arrange pickup with the seller in chat.
                                </small>

                            <?php elseif ($order['order_status'] === 'completed'): ?>

                                <small class="text-success">
                                    Confirmed <?= htmlspecialchars($order['pickup_confirmed_at'] ?? '') ?>
                                </small>

                            <?php else: ?>

                                <small>
                                    <?= htmlspecialchars($order['pickup_location'] ?? '') ?>
                                </small>

                            <?php endif; ?>
                        </td>

                        <td>
                            <?php
                            $chat_user = $is_buyer
                                ? (int)$order['seller_id']
                                : (int)$order['buyer_id'];
                            ?>

                            <a href="chat.php?user_id=<?= $chat_user ?>&order_id=<?= (int)$order['id'] ?>#textbook-reference"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-chat-dots"></i>
                            </a>
                            <?php if ($is_buyer && $order['payment_status'] === 'pending'): ?>
                                <a href="pay_order.php?order_id=<?= (int) $order['id'] ?>" class="btn btn-sm btn-success">
                                    Pay Now
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

<?php endif; ?>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
?>
