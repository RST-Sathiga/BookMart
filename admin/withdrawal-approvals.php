<?php
$current_page = 'approvals';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../db.php';

$listings = mysqli_query(
    $conn,
    "SELECT * FROM products WHERE status='pending'"
);
?>

<div class="card shadow-sm">
    <div class="card-header">
        <h4>Pending Listings</h4>
    </div>

    <div class="card-body">

        <table class="table">
            <thead>
            <tr>
                <th>Book</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
            </thead>

            <tbody>

            <?php while($row = mysqli_fetch_assoc($listings)): ?>

            <tr>

                <td><?= htmlspecialchars($row['title']) ?></td>

                <td>R<?= number_format($row['price'],2) ?></td>

                <td>

                    <a href="handlers/product_handler.php?action=approve&id=<?= $row['id'] ?>"
                       class="btn btn-success btn-sm">
                        Approve
                    </a>

                    <a href="handlers/product_handler.php?action=reject&id=<?= $row['id'] ?>"
                       class="btn btn-danger btn-sm">
                        Reject
                    </a>

                </td>

            </tr>

            <?php endwhile; ?>

            </tbody>
        </table>

    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>