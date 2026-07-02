<?php

require_once __DIR__ . '/includes/auth.php';

require_admin();

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = (int) $_POST['id'];
    $action = $_POST['action'];
    $reason = trim($_POST['reason'] ?? '');

    $stmt = $conn->prepare('
        SELECT withdrawals.*, users.fullname, users.email
        FROM withdrawals
        JOIN users ON users.user_id = withdrawals.seller_id
        WHERE withdrawals.id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $withdrawal = $stmt->get_result()->fetch_assoc();

    if (!$withdrawal) {
        $message = 'Withdrawal request not found.';
        $message_type = 'danger';
    } elseif ($withdrawal['status'] !== 'pending') {
        $message = 'Only pending withdrawals can be processed.';
        $message_type = 'warning';
    } elseif ($action === 'complete') {
        $update = $conn->prepare('UPDATE withdrawals SET status = "completed" WHERE id = ?');
        $update->bind_param('i', $id);
        $update->execute();

        create_bookmart_notification(
            $conn,
            (int) $withdrawal['seller_id'],
            'system',
            'Your withdrawal of ' . format_currency((float) $withdrawal['amount']) . ' has been completed.',
            'medium',
            $id
        );

        $message = 'Withdrawal marked as completed.';
        $message_type = 'success';
    } elseif ($action === 'reject') {
        if ($reason === '') {
            $message = 'Enter a rejection reason.';
            $message_type = 'danger';
        } else {
            $conn->begin_transaction();

            try {
                $update = $conn->prepare('UPDATE withdrawals SET status = "rejected" WHERE id = ? AND status = "pending"');
                $update->bind_param('i', $id);
                $update->execute();

                if ($update->affected_rows === 0) {
                    throw new RuntimeException('Withdrawal has already been processed.');
                }

                $amount = (float) $withdrawal['amount'];
                $seller_id = (int) $withdrawal['seller_id'];

                $refund_user = $conn->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE user_id = ?');
                $refund_user->bind_param('di', $amount, $seller_id);
                $refund_user->execute();

                if (table_exists($conn, 'wallets')) {
                    $wallet = $conn->prepare('UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE user_id = ?');
                    $wallet->bind_param('di', $amount, $seller_id);
                    $wallet->execute();
                }

                $log = $conn->prepare('
                    INSERT INTO wallet_transactions (user_id, type, amount, description)
                    VALUES (?, "withdrawal", ?, ?)
                ');
                $description = 'Withdrawal rejected and refunded: ' . $reason;
                $log->bind_param('ids', $seller_id, $amount, $description);
                $log->execute();

                $conn->commit();

                create_bookmart_notification(
                    $conn,
                    $seller_id,
                    'system',
                    'Your withdrawal of ' . format_currency($amount) . ' was rejected and refunded. Reason: ' . $reason,
                    'medium',
                    $id
                );

                $message = 'Withdrawal rejected and refunded.';
                $message_type = 'success';
            } catch (Throwable $e) {
                $conn->rollback();
                $message = 'Withdrawal could not be rejected: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = ['1=1'];
$types = '';
$params = [];

if (in_array($status, ['pending', 'completed', 'rejected'], true)) {
    $where[] = 'withdrawals.status = ?';
    $types .= 's';
    $params[] = $status;
}

if ($search !== '') {
    $term = '%' . $search . '%';
    $where[] = '(users.fullname LIKE ? OR users.email LIKE ? OR withdrawals.bank_name LIKE ? OR withdrawals.account_number LIKE ?)';
    $types .= 'ssss';
    array_push($params, $term, $term, $term, $term);
}

$sql = '
    SELECT withdrawals.*, users.fullname, users.email
    FROM withdrawals
    JOIN users ON users.user_id = withdrawals.seller_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY FIELD(withdrawals.status, "pending", "completed", "rejected"), withdrawals.created_at DESC
';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$withdrawals = $stmt->get_result();

$totals = [
    'pending' => 0,
    'completed' => 0,
    'rejected' => 0,
    'pending_amount' => 0.0,
];

$summary = $conn->query('
    SELECT status, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS amount
    FROM withdrawals
    GROUP BY status
');

if ($summary) {
    while ($row = $summary->fetch_assoc()) {
        if (isset($totals[$row['status']])) {
            $totals[$row['status']] = (int) $row['count'];
        }
        if ($row['status'] === 'pending') {
            $totals['pending_amount'] = (float) $row['amount'];
        }
    }
}

$page_title = 'Manage Withdrawals';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Withdrawals</h1>
            <p class="text-muted mb-0">Review seller payout requests and mark bank transfers as completed.</p>
        </div>
        <a href="<?= site_url('admin/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="feature-card p-3">Pending: <strong><?= $totals['pending'] ?></strong></div></div>
        <div class="col-md-3"><div class="feature-card p-3">Pending Value: <strong><?= format_currency($totals['pending_amount']) ?></strong></div></div>
        <div class="col-md-3"><div class="feature-card p-3">Completed: <strong><?= $totals['completed'] ?></strong></div></div>
        <div class="col-md-3"><div class="feature-card p-3">Rejected: <strong><?= $totals['rejected'] ?></strong></div></div>
    </div>

    <form method="GET" class="feature-card p-3 mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Seller, email, bank, account number">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="feature-card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Amount</th>
                        <th>Bank Details</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($withdrawals->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No withdrawals found.</td></tr>
                    <?php endif; ?>

                    <?php while ($row = $withdrawals->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                            </td>
                            <td><?= format_currency((float) $row['amount']) ?></td>
                            <td>
                                <?php if (!empty($row['bank_name'])): ?>
                                    <strong><?= htmlspecialchars($row['account_holder'] ?? '') ?></strong><br>
                                    <small>
                                        <?= htmlspecialchars($row['bank_name']) ?> · <?= payout_account_type_label($row['account_type'] ?? 'cheque') ?><br>
                                        Acc: <code><?= htmlspecialchars(mask_account_number($row['account_number'] ?? '')) ?></code><br>
                                        Branch: <code><?= htmlspecialchars($row['branch_code'] ?? '') ?></code>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted small">No bank details recorded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $row['status'] === 'completed' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning text-dark') ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td style="min-width:260px">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button class="btn btn-sm btn-success" onclick="return confirm('Mark this withdrawal as completed after bank transfer?')">
                                            Complete
                                        </button>
                                    </form>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="reason" class="form-control" placeholder="Reject reason" required>
                                            <button class="btn btn-outline-danger">Reject</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted small">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
