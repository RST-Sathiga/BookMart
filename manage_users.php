<?php

require_once __DIR__ . '/includes/auth.php';

require_admin();

$message = '';
$message_type = 'info';

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($id > 0 && in_array($action, ['activate', 'ban', 'pending'], true)) {
        $status = match ($action) {
            'activate' => 'active',
            'ban' => 'banned',
            default => 'pending',
        };

        $stmt = $conn->prepare('UPDATE users SET status = ? WHERE user_id = ? AND role <> "admin"');
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();

        create_bookmart_notification($conn, $id, 'system', 'Your account status is now ' . $status . '.', 'medium', null);

        header('Location: ' . site_url('manage_users.php'));
        exit();
    }
}

if (isset($_POST['send_notification'])) {
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $notice = trim($_POST['message'] ?? '');

    if ($user_id > 0 && $notice !== '') {
        create_bookmart_notification($conn, $user_id, 'system', $notice, 'medium', null);
        $message = 'Notification sent.';
        $message_type = 'success';
    }
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ['1=1'];
$types = '';
$params = [];

if ($search !== '') {
    $term = '%' . $search . '%';
    $where[] = '(fullname LIKE ? OR username LIKE ? OR email LIKE ? OR student_number LIKE ? OR id_passport_number LIKE ?)';
    $types .= 'sssss';
    array_push($params, $term, $term, $term, $term, $term);
}

if (in_array($status, ['active', 'pending', 'banned'], true)) {
    $where[] = 'status = ?';
    $types .= 's';
    $params[] = $status;
}

$sql = '
    SELECT user_id, fullname, username, email, student_number, id_passport_number, profile_image, role, status, created_at
    FROM users
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY created_at DESC
';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

$page_title = 'Manage Users';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Manage Users</h1>
            <p class="text-muted mb-0">Search, activate, ban, and send no-reply notices to users.</p>
        </div>
        <a href="<?= site_url('admin/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="GET" class="feature-card p-3 mb-4">
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Name, username, email, student number, ID">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
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
                        <th>User</th>
                        <th>Email</th>
                        <th>Student No</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php endif; ?>

                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= profile_image_url($u['profile_image'] ?? null) ?>" alt="" class="rounded-circle" width="42" height="42" style="object-fit:cover">
                                    <div>
                                        <strong><?= htmlspecialchars($u['fullname']) ?></strong><br>
                                        <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['student_number'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : ($u['status'] === 'banned' ? 'danger' : 'warning text-dark') ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['role']) ?></td>
                            <td style="min-width:300px">
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                        <a href="?action=activate&id=<?= (int) $u['user_id'] ?>" class="btn btn-sm btn-success">Activate</a>
                                        <a href="?action=pending&id=<?= (int) $u['user_id'] ?>" class="btn btn-sm btn-outline-warning">Pending</a>
                                        <a href="?action=ban&id=<?= (int) $u['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Ban this user?')">Ban</a>
                                    </div>
                                    <form method="POST" class="input-group input-group-sm">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                                        <input type="text" name="message" class="form-control" placeholder="No-reply notice" required>
                                        <button name="send_notification" class="btn btn-outline-primary">Send</button>
                                    </form>
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
