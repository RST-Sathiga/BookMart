<?php

require_once __DIR__ . '/includes/auth.php';

require_login();
require_profile_photo_for_pickup($conn);

$user_id = current_user_id();
$message = '';
$success = false;

if (isset($_POST['confirm_pickup'])) {
    $order_id = (int) ($_POST['order_id'] ?? 0);

    if ($order_id <= 0) {
        $message = 'Choose a pickup to confirm.';
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare('
                SELECT id, buyer_id, seller_id
                FROM orders
                WHERE seller_id = ?
                  AND id = ?
                  AND payment_status = "paid"
                  AND order_status = "awaiting_pickup"
                LIMIT 1
                FOR UPDATE
            ');
            $stmt->bind_param('ii', $user_id, $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new RuntimeException('Invalid or already processed pickup.');
            }

            $update = $conn->prepare('
                UPDATE orders
                SET order_status = "completed", pickup_confirmed_at = NOW()
                WHERE id = ?
            ');
            $update->bind_param('i', $order['id']);
            $update->execute();

            $conn->commit();

            create_bookmart_notification($conn, (int) $order['buyer_id'], 'order', 'Pickup confirmed. Thank you for using BookMart.', 'low', (int) $order['id']);
            create_bookmart_notification($conn, (int) $order['seller_id'], 'order', 'Pickup confirmed. Your sale was already credited after payment.', 'low', (int) $order['id']);

            $success = true;
            $message = 'Pickup confirmed.';
        } catch (Throwable $e) {
            $conn->rollback();
            $message = 'Pickup failed: ' . $e->getMessage();
        }
    }
}

$awaiting = $conn->prepare('
    SELECT orders.*, products.title, buyer.fullname AS buyer_name, buyer.profile_image AS buyer_photo
    FROM orders
    JOIN products ON orders.product_id = products.id
    JOIN users buyer ON orders.buyer_id = buyer.user_id
    WHERE orders.seller_id = ?
      AND orders.order_status = "awaiting_pickup"
      AND orders.payment_status = "paid"
    ORDER BY orders.created_at DESC
');
$awaiting->bind_param('i', $user_id);
$awaiting->execute();
$pending_pickups = $awaiting->get_result();

$page_title = 'Confirm Pickup';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="section-title">Confirm Campus Pickup</h1>
    <p class="text-muted">Verify buyer identity before marking collection complete.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12">
            <div class="feature-card p-4">
                <h2 class="h5 mb-3">Ready for Pickup Confirmation</h2>

                <?php if ($pending_pickups->num_rows === 0): ?>
                    <p class="text-muted mb-0">No pending pickups.</p>
                <?php else: ?>
                    <?php while ($row = $pending_pickups->fetch_assoc()): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex gap-3">
                                <img src="<?= profile_image_url($row['buyer_photo'] ?? null) ?>" width="56" height="56" class="rounded-circle" style="object-fit:cover" alt="">
                                <div>
                                    <strong><?= htmlspecialchars($row['title']) ?></strong>
                                    <div class="small text-muted">Buyer: <?= htmlspecialchars($row['buyer_name']) ?></div>
                                    <div class="small">Pickup: <?= htmlspecialchars($row['pickup_location']) ?></div>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <a href="chat.php?user_id=<?= (int) $row['buyer_id'] ?>&order_id=<?= (int) $row['id'] ?>#textbook-reference" class="btn btn-sm btn-outline-primary">
                                            Message Buyer
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" name="confirm_pickup" class="btn btn-sm btn-gold" onclick="return confirm('Mark this pickup as completed?')">
                                                Confirm Pickup
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
