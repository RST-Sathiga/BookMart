<?php

require_once __DIR__ . '/includes/auth.php';

require_admin();

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($id > 0 && in_array($action, ['approve', 'reject', 'delete'], true)) {
        if ($action === 'delete') {
            $stmt = $conn->prepare('DELETE FROM products WHERE id = ? AND status <> "sold"');
            $stmt->bind_param('i', $id);
            $stmt->execute();
        } else {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $conn->prepare('UPDATE products SET status = ? WHERE id = ? AND status <> "sold"');
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
        }
    }

    header('Location: ' . site_url('manage_listings.php'));
    exit();
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$university_id = (int) ($_GET['university_id'] ?? 0);

$where = ['1=1'];
$types = '';
$params = [];

if ($search !== '') {
    $term = '%' . $search . '%';
    $where[] = '(products.title LIKE ? OR products.author LIKE ? OR products.course_code LIKE ? OR products.isbn LIKE ? OR users.fullname LIKE ?)';
    $types .= 'sssss';
    array_push($params, $term, $term, $term, $term, $term);
}

if (in_array($status, ['pending', 'approved', 'rejected', 'sold'], true)) {
    $where[] = 'products.status = ?';
    $types .= 's';
    $params[] = $status;
}

if ($university_id > 0) {
    $where[] = 'products.university_id = ?';
    $types .= 'i';
    $params[] = $university_id;
}

$sql = '
    SELECT products.*, users.fullname AS seller_name, universities.name AS university_name, campuses.name AS campus_name
    FROM products
    JOIN users ON users.user_id = products.user_id
    LEFT JOIN universities ON universities.id = products.university_id
    LEFT JOIN campuses ON campuses.id = products.campus_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY FIELD(products.status, "pending", "approved", "sold", "rejected"), products.created_at DESC
';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$listings = $stmt->get_result();

$universities = get_universities($conn);

$total = (int) $conn->query('SELECT COUNT(*) AS c FROM products')->fetch_assoc()['c'];
$approved = (int) $conn->query('SELECT COUNT(*) AS c FROM products WHERE status = "approved"')->fetch_assoc()['c'];
$pending = (int) $conn->query('SELECT COUNT(*) AS c FROM products WHERE status = "pending"')->fetch_assoc()['c'];
$sold = (int) $conn->query('SELECT COUNT(*) AS c FROM products WHERE status = "sold"')->fetch_assoc()['c'];

$page_title = 'Manage Listings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Listings</h1>
            <p class="text-muted mb-0">All textbooks on the platform with search and filter controls.</p>
        </div>
        <a href="<?= site_url('admin/index.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Admin
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="feature-card p-3">Total: <strong><?= $total ?></strong></div></div>
        <div class="col-md-3"><div class="feature-card p-3">Approved: <strong><?= $approved ?></strong></div></div>
        <div class="col-md-3"><div class="feature-card p-3">Pending: <strong><?= $pending ?></strong></div></div>
        <div class="col-md-3"><div class="feature-card p-3">Sold: <strong><?= $sold ?></strong></div></div>
    </div>

    <form method="GET" class="feature-card p-3 mb-4">
        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Title, author, ISBN, module, seller">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending', 'approved', 'rejected', 'sold'] as $option): ?>
                        <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">University</label>
                <select name="university_id" class="form-select">
                    <option value="0">All Universities</option>
                    <?php foreach ($universities as $uni): ?>
                        <option value="<?= (int) $uni['id'] ?>" <?= $university_id === (int) $uni['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($uni['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="feature-card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Textbook</th>
                        <th>Seller</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listings->num_rows === 0): ?>
                        <tr><td colspan="7" class="text-muted text-center py-4">No listings found.</td></tr>
                    <?php endif; ?>

                    <?php while ($row = $listings->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['title']) ?></strong>
                                <div class="small text-muted">
                                    <?= htmlspecialchars($row['author'] ?: 'Unknown author') ?>
                                    <?php if (!empty($row['course_code'])): ?> · <?= htmlspecialchars($row['course_code']) ?><?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['seller_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['university_name'] ?? 'Unassigned') ?><br>
                                <small><?= htmlspecialchars($row['campus_name'] ?? '') ?></small>
                            </td>
                            <td><?= format_currency((float) $row['price']) ?></td>
                            <td>
                                <span class="badge bg-<?= $row['status'] === 'approved' ? 'success' : ($row['status'] === 'pending' ? 'warning text-dark' : ($row['status'] === 'sold' ? 'secondary' : 'danger')) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending' || $row['status'] === 'rejected'): ?>
                                    <a href="?action=approve&id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                                <?php endif; ?>
                                <?php if ($row['status'] === 'pending' || $row['status'] === 'approved'): ?>
                                    <a href="?action=reject&id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-warning">Reject</a>
                                <?php endif; ?>
                                <?php if ($row['status'] !== 'sold'): ?>
                                    <a href="?action=delete&id=<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this listing?')">Delete</a>
                                <?php else: ?>
                                    <span class="text-muted small">Sold</span>
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
