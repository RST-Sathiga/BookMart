<?php

require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$product_id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare('
    SELECT products.*, users.fullname AS seller_name, users.user_id AS seller_id, users.username, users.profile_image AS seller_photo,
           universities.name AS university_name, campuses.name AS campus_name, campuses.pickup_point
    FROM products
    JOIN users ON products.user_id = users.user_id
    JOIN universities ON products.university_id = universities.id
    JOIN campuses ON products.campus_id = campuses.id
    WHERE products.id = ? AND products.status = "approved"
');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: marketplace.php');
    exit();
}

$page_title = $product['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="feature-card overflow-hidden">
                <?php if ($product['image']): ?>
                    <img src="uploads/<?= htmlspecialchars($product['image']) ?>" class="w-100" alt="<?= htmlspecialchars($product['title']) ?>">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:400px">
                        <i class="bi bi-book display-1 text-muted"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="feature-card p-4">
                <span class="badge badge-campus mb-2">Campus Pickup</span>
                <h1 class="h3 mb-2"><?= htmlspecialchars($product['title']) ?></h1>
                <p class="text-muted">by <?= htmlspecialchars($product['author'] ?: 'Unknown author') ?></p>
                <p class="price-tag"><?= format_currency((float) $product['price']) ?></p>

                <ul class="list-unstyled mb-4">
                    <li><strong>Condition:</strong> <?= book_condition_label($product['book_condition']) ?></li>
                    <?php if ($product['course_code']): ?><li><strong>Course:</strong> <?= htmlspecialchars($product['course_code']) ?></li><?php endif; ?>
                    <?php if ($product['isbn']): ?><li><strong>ISBN:</strong> <?= htmlspecialchars($product['isbn']) ?></li><?php endif; ?>
                    <li><strong>University:</strong> <?= htmlspecialchars($product['university_name']) ?></li>
                    <li><strong>Campus:</strong> <?= htmlspecialchars($product['campus_name']) ?></li>
                    <li><strong>Pickup point:</strong> <?= htmlspecialchars($product['pickup_point']) ?></li>
                    <li>
                        <strong>Seller:</strong>
                        <span class="d-inline-flex align-items-center gap-2 mt-1">
                            <img src="<?= profile_image_url($product['seller_photo'] ?? null) ?>" alt="" class="rounded-circle" width="36" height="36" style="object-fit:cover">
                            <?= htmlspecialchars($product['seller_name']) ?> (@<?= htmlspecialchars($product['username']) ?>)
                        </span>
                    </li>
                </ul>

                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

                <?php if (current_user_id() && current_user_id() !== (int) $product['seller_id']): ?>
                    <form method="POST" action="add_to_cart.php" class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                        <a href="chat.php?user_id=<?= (int) $product['seller_id'] ?>&product_id=<?= (int) $product['id'] ?>" class="btn btn-outline-primary">Message Seller</a>
                    </form>
                <?php elseif (!current_user_id()): ?>
                    <a href="login.php" class="btn btn-primary">Login to Purchase</a>
                <?php else: ?>
                    <div class="alert alert-info mb-0">This is your listing.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
