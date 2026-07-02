<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_admin(); // you must have admin check

$page_title = "Withdrawal Requests";

$stmt = $conn->prepare("
    SELECT w.*, u.fullname, u.email
    FROM withdrawals w
    JOIN users u ON w.seller_id = u.user_id
    ORDER BY w.created_at DESC
");
$stmt->execute();
$withdrawals = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

<h2>Withdrawal Requests</h2>

<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>User</th>
            <th>Amount</th>
            <th>Bank</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
    <?php while ($w = $withdrawals->fetch_assoc()): ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($w['fullname']) ?></strong><br>
                <small><?= htmlspecialchars($w['email']) ?></small>
            </td>

            <td>R <?= number_format($w['amount'], 2) ?></td>

            <td>
                <?= htmlspecialchars($w['bank_name']) ?><br>
                <?= htmlspecialchars($w['account_number']) ?>
            </td>

            <td>
                <span class="badge bg-<?= 
                    $w['status'] === 'approved' ? 'success' : 
                    ($w['status'] === 'rejected' ? 'danger' : 'warning') 
                ?>">
                    <?= ucfirst($w['status']) ?>
                </span>
            </td>

            <td><?= htmlspecialchars($w['created_at']) ?></td>

            <td>
                <?php if ($w['status'] === 'pending'): ?>
                    <form method="POST" action="process_withdrawal.php" class="d-flex gap-2">
                        <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">

                        <button name="action" value="approve" class="btn btn-success btn-sm">
                            Approve
                        </button>

                        <button name="action" value="reject" class="btn btn-danger btn-sm">
                            Reject
                        </button>
                    </form>
                <?php else: ?>
                    <small>No action</small>
                <?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>

</table>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>