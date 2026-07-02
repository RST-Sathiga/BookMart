<?php
$current_page = 'viewers';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../db.php';

$users = mysqli_query(
    $conn,
    "SELECT * FROM users WHERE verification_status='pending'"
);
?>

<div class="card shadow-sm">
    <div class="card-body">

        <h3>Approve Viewers</h3>

        <table class="table">

            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>

            <?php while($u = mysqli_fetch_assoc($users)): ?>

            <tr>

                <td><?= htmlspecialchars($u['fullname']) ?></td>

                <td><?= htmlspecialchars($u['email']) ?></td>

                <td>

                    <a href="handlers/user_handler.php?action=approve&id=<?= $u['id'] ?>"
                       class="btn btn-success btn-sm">
                        Approve
                    </a>

                    <a href="handlers/user_handler.php?action=reject&id=<?= $u['id'] ?>"
                       class="btn btn-danger btn-sm">
                        Reject
                    </a>

                </td>

            </tr>

            <?php endwhile; ?>

        </table>

    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>