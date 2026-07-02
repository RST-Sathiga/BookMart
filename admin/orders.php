<?php

require_once __DIR__ . '/includes/bootstrap.php';

$result = $conn->query('
    SELECT orders.*, products.title,
           buyer.fullname AS buyer_name, seller.fullname AS seller_name
    FROM orders
    JOIN products ON orders.product_id = products.id
    JOIN users buyer ON orders.buyer_id = buyer.user_id
    JOIN users seller ON orders.seller_id = seller.user_id
    ORDER BY orders.created_at DESC
');

$page_title = 'Orders';
require_once __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title h4 mb-4">All Orders</h2>

<div class="admin-card p-3">
    <div class="table-responsive">
        <table class="table table-bookmart align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Textbook</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= (int) $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['buyer_name']) ?></td>
                        <td><?= htmlspecialchars($row['seller_name']) ?></td>
                        <td><?= format_currency((float) $row['amount']) ?></td>
                        <td><?= ucfirst($row['payment_status']) ?></td>
                        <td><?= order_status_badge($row['order_status']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
