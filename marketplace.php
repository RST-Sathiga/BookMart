<?php

require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = 'Marketplace';

$search = trim($_GET['search'] ?? '');
$selected_university = trim($_GET['university'] ?? '');
$selected_campus = trim($_GET['campus'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$universities = get_universities($conn);
$campuses = [];
if ($selected_university !== '' && ctype_digit($selected_university)) {
    $campuses = get_campuses_by_university($conn, (int) $selected_university);
} else {
    $result = $conn->query('SELECT id, name, university_id FROM campuses ORDER BY name');
    $campuses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$where = ['products.status = "approved"'];
$types = '';
$params = [];

if ($search !== '') {
    $term = '%' . $search . '%';
    $where[] = '(products.title LIKE ? OR products.author LIKE ? OR products.course_code LIKE ? OR products.isbn LIKE ? OR products.description LIKE ?)';
    $types .= 'sssss';
    array_push($params, $term, $term, $term, $term, $term);
}

if ($selected_university !== '' && ctype_digit($selected_university)) {
    $where[] = 'products.university_id = ?';
    $types .= 'i';
    $params[] = (int) $selected_university;
}

if ($selected_campus !== '' && ctype_digit($selected_campus)) {
    $where[] = 'products.campus_id = ?';
    $types .= 'i';
    $params[] = (int) $selected_campus;
}

if (in_array($condition, ['new', 'like_new', 'good', 'fair'], true)) {
    $where[] = 'products.book_condition = ?';
    $types .= 's';
    $params[] = $condition;
}

$order_by = match ($sort) {
    'oldest' => 'products.created_at ASC',
    'price_low' => 'products.price ASC',
    'price_high' => 'products.price DESC',
    'alpha' => 'products.title ASC',
    default => 'products.created_at DESC',
};

$where_sql = implode(' AND ', $where);

$count_sql = "
    SELECT COUNT(*) AS total
    FROM products
    JOIN users ON users.user_id = products.user_id
    LEFT JOIN universities ON universities.id = products.university_id
    LEFT JOIN campuses ON campuses.id = products.campus_id
    WHERE $where_sql
";
$count_stmt = $conn->prepare($count_sql);
if ($types !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_products = (int) ($count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$total_pages = max(1, (int) ceil($total_products / $per_page));

$list_sql = "
    SELECT products.*, users.fullname AS seller_name, users.username,
           universities.name AS university_name, campuses.name AS campus_name
    FROM products
    JOIN users ON users.user_id = products.user_id
    LEFT JOIN universities ON universities.id = products.university_id
    LEFT JOIN campuses ON campuses.id = products.campus_id
    WHERE $where_sql
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";
$list_types = $types . 'ii';
$list_params = array_merge($params, [$per_page, $offset]);
$stmt = $conn->prepare($list_sql);
$stmt->bind_param($list_types, ...$list_params);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function marketplace_query(array $overrides = []): string
{
    $query = array_merge($_GET, $overrides);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    return http_build_query($query);
}

function marketplace_image(?string $image): ?string
{
    if (!$image) {
        return null;
    }

    $path = ltrim($image, '/');
    if (is_file(__DIR__ . '/uploads/' . $path)) {
        return site_url('uploads/' . $path);
    }

    if (is_file(__DIR__ . '/' . $path)) {
        return site_url($path);
    }

    return null;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <section class="marketplace-hero mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <h1 class="mb-2">BookMart Marketplace</h1>
                <p class="mb-0">Find approved textbooks by university, campus, module code, author, and condition.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?php if (current_user_id()): ?>
                    <a href="<?= site_url('sell.php') ?>" class="btn btn-warning btn-lg">
                        <i class="bi bi-plus-circle me-1"></i>Sell Textbook
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-info"><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <form method="GET" class="feature-card p-4 mb-4">
        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Search</label>
                <input type="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Title, author, ISBN, module code">
            </div>
            <div class="col-lg-3">
                <label class="form-label">University</label>
                <select name="university" class="form-select">
                    <option value="">All Universities</option>
                    <?php foreach ($universities as $uni): ?>
                        <option value="<?= (int) $uni['id'] ?>" <?= (string) $selected_university === (string) $uni['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($uni['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Campus</label>
                <select name="campus" class="form-select">
                    <option value="">All Campuses</option>
                    <?php foreach ($campuses as $campus): ?>
                        <option value="<?= (int) $campus['id'] ?>" <?= (string) $selected_campus === (string) $campus['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($campus['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Condition</label>
                <select name="condition" class="form-select">
                    <option value="">Any</option>
                    <?php foreach (['new' => 'New', 'like_new' => 'Like New', 'good' => 'Good', 'fair' => 'Fair'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $condition === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label">Sort By</label>
                <select name="sort" class="form-select">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price Low-High</option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price High-Low</option>
                    <option value="alpha" <?= $sort === 'alpha' ? 'selected' : '' ?>>Alphabetical</option>
                </select>
            </div>
            <div class="col-lg-9 d-flex align-items-end gap-2">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search me-1"></i>Filter Results
                </button>
                <a href="<?= site_url('marketplace.php') ?>" class="btn btn-outline-secondary">Clear</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0"><?= $total_products ?> textbook<?= $total_products === 1 ? '' : 's' ?> found</h2>
        <span class="text-muted small">Page <?= $page ?> of <?= $total_pages ?></span>
    </div>

    <?php if (!$listings): ?>
        <div class="feature-card p-5 text-center">
            <i class="bi bi-book display-3 text-muted"></i>
            <h3 class="h4 mt-3">No textbooks found</h3>
            <p class="text-muted">Try changing your filters or check back after new listings are approved.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($listings as $book): ?>
                <?php $image = marketplace_image($book['image'] ?? null); ?>
                <div class="col-md-6 col-xl-4">
                    <article class="feature-card h-100 overflow-hidden d-flex flex-column">
                        <?php if ($image): ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="w-100" style="height:230px;object-fit:cover">
                        <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:230px">
                                <i class="bi bi-book display-3 text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <span class="badge bg-primary"><?= htmlspecialchars($book['campus_name'] ?? 'Campus') ?></span>
                                <span class="badge bg-success"><?= book_condition_label($book['book_condition']) ?></span>
                            </div>

                            <h3 class="h5 mb-1"><?= htmlspecialchars($book['title']) ?></h3>
                            <p class="text-muted mb-2"><?= htmlspecialchars($book['author'] ?: 'Unknown author') ?></p>

                            <?php if (!empty($book['course_code'])): ?>
                                <p class="small mb-1"><strong>Module:</strong> <?= htmlspecialchars($book['course_code']) ?></p>
                            <?php endif; ?>
                            <p class="small mb-2"><strong>University:</strong> <?= htmlspecialchars($book['university_name'] ?? 'Unknown') ?></p>
                            <p class="small text-muted mb-3">Seller: <?= htmlspecialchars($book['seller_name']) ?></p>

                            <div class="mt-auto">
                                <div class="price-tag mb-3"><?= format_currency((float) $book['price']) ?></div>
                                <div class="d-grid gap-2">
                                    <a href="<?= site_url('product.php?id=' . (int) $book['id']) ?>" class="btn btn-primary">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>
                                    <?php if (current_user_id() && current_user_id() !== (int) $book['user_id']): ?>
                                        <form method="POST" action="<?= site_url('add_to_cart.php') ?>">
                                            <input type="hidden" name="product_id" value="<?= (int) $book['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <button class="btn btn-success w-100" type="submit">
                                                <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                            </button>
                                        </form>
                                    <?php elseif (!current_user_id()): ?>
                                        <a href="<?= site_url('login.php?redirect=marketplace.php') ?>" class="btn btn-outline-primary w-100">Login to Buy</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="mt-4" aria-label="Marketplace pages">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= marketplace_query(['page' => $page - 1]) ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= marketplace_query(['page' => $i]) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= marketplace_query(['page' => $page + 1]) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
