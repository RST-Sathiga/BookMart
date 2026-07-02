<?php

require_once __DIR__ . '/includes/bootstrap.php';

$result = $conn->query('
    SELECT user_id, fullname, username, email, id_passport_number, profile_image, role, status, wallet_balance, created_at
    FROM users
    ORDER BY created_at DESC
');

$page_title = 'Users';
require_once __DIR__ . '/includes/admin_header.php';
?>

<h2 class="section-title h4 mb-4">User Management</h2>

<div class="admin-card p-3">
    <div class="table-responsive">
        <table class="table table-bookmart align-middle mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>ID / Passport</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Wallet</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= profile_image_url($row['profile_image'] ?? null) ?>" alt="" class="rounded-circle" width="40" height="40" style="object-fit:cover">
                                <div>
                                    <?= htmlspecialchars($row['fullname']) ?><br>
                                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><code><?= htmlspecialchars($row['id_passport_number'] ?? '—') ?></code></td>
                        <td><?= htmlspecialchars($row['role']) ?></td>
                        <td><span class="badge bg-<?= $row['status'] === 'active' ? 'success' : ($row['status'] === 'banned' ? 'danger' : 'warning text-dark') ?>"><?= ucfirst($row['status']) ?></span></td>
                        <td><?= format_currency((float) $row['wallet_balance']) ?></td>
                        <td>
                            <?php if ($row['role'] !== 'admin'): ?>
                                <?php if ($row['status'] === 'banned'): ?>
                                    <a href="handlers/user_handler.php?action=activate&id=<?= (int) $row['user_id'] ?>" class="btn btn-sm btn-success">Activate</a>
                                <?php else: ?>
                                    <a href="handlers/user_handler.php?action=ban&id=<?= (int) $row['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ban this user?')">Ban</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
