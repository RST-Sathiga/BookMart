<?php

require_once __DIR__ . '/includes/bootstrap.php';

$result = $conn->query('
    SELECT products.*, users.fullname AS seller_name, users.username,
           universities.name AS university_name, campuses.name AS campus_name
    FROM products
    JOIN users ON products.user_id = users.user_id
    JOIN universities ON products.university_id = universities.id
    JOIN campuses ON products.campus_id = campuses.id
    ORDER BY FIELD(products.status, "pending", "approved", "rejected", "sold"), products.created_at DESC
');

$page_title = 'Textbooks';
require_once __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title h4 mb-4">Textbook Listings</h2>

<div class="admin-card p-3">
    <div class="table-responsive">
        <table class="table table-bookmart align-middle mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Seller</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                            <?php if ($row['course_code']): ?><br><small><?= htmlspecialchars($row['course_code']) ?></small><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['seller_name']) ?></td>
                        <td><?= htmlspecialchars($row['university_name']) ?><br><small><?= htmlspecialchars($row['campus_name']) ?></small></td>
                        <td><?= format_currency((float) $row['price']) ?></td>
                        <td><span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'pending' ? 'warning text-dark' : 'secondary') ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <a href="handlers/product_handler.php?action=approve&id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                <a href="handlers/product_handler.php?action=reject&id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger">Reject</a>
                            <?php elseif ($row['status'] === 'approved'): ?>
                                <a href="handlers/product_handler.php?action=reject&id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger">Remove</a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
