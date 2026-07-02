<?php

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ['role = "user"'];
$types = '';
$params = [];

if ($search !== '') {
    $term = '%' . $search . '%';
    $where[] = '(fullname LIKE ? OR username LIKE ? OR email LIKE ? OR id_passport_number LIKE ? OR student_number LIKE ?)';
    $types .= 'sssss';
    array_push($params, $term, $term, $term, $term, $term);
}

if (in_array($status, ['active', 'banned', 'pending'], true)) {
    $where[] = 'status = ?';
    $types .= 's';
    $params[] = $status;
}

$sql = '
    SELECT user_id, fullname, username, email, student_number, id_passport_number, profile_image, status, created_at
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

$page_title = 'Registered Users';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="section-title h4 mb-1">Registered Users</h2>
        <p class="text-muted mb-0 small">View all student accounts registered on BookMart.</p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
    </a>
</div>

<div class="admin-card p-3 mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-7">
            <label class="form-label">Search</label>
            <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Name, username, email, ID, student number">
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
    </form>
</div>

<div class="admin-card p-3">
    <?php if ($users->num_rows === 0): ?>
        <p class="text-muted mb-0">No registered users found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student No</th>
                        <th>ID / Passport</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?= profile_image_url($u['profile_image'] ?? null) ?>" width="44" height="44" class="rounded-circle" style="object-fit:cover" alt="">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($u['fullname']) ?></strong><br>
                                <small class="text-muted">@<?= htmlspecialchars($u['username']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['student_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($u['id_passport_number'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : ($u['status'] === 'banned' ? 'danger' : 'warning text-dark') ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
