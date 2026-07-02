<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$message = '';
$success = '';
$old = [
    'title' => '',
    'author' => '',
    'course_code' => '',
    'isbn' => '',
    'book_condition' => 'good',
    'description' => '',
    'price' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_merge($old, [
        'title' => trim($_POST['title'] ?? ''),
        'author' => trim($_POST['author'] ?? ''),
        'course_code' => trim($_POST['course_code'] ?? ''),
        'isbn' => trim($_POST['isbn'] ?? ''),
        'book_condition' => trim($_POST['book_condition'] ?? 'good'),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
    ]);

    $price = (float) $old['price'];
    $allowed_conditions = ['new', 'like_new', 'good', 'fair'];
    $location = resolve_location_from_request($conn, $_POST);

    if ($old['title'] === '' || $old['description'] === '' || $price <= 0) {
        $message = 'Enter a title, description, and valid price.';
    } elseif (!in_array($old['book_condition'], $allowed_conditions, true)) {
        $message = 'Choose a valid textbook condition.';
    } elseif (isset($location['error'])) {
        $message = $location['error'];
    } else {
        $image = null;
        if (!empty($_FILES['book_image']['name'])) {
            $image = upload_product_image($_FILES['book_image']);
            if (!$image) {
                $message = 'Upload a JPG, PNG, or WEBP image for the textbook.';
            }
        }

        if ($message === '') {
            $user_id = current_user_id();
            $university_id = (int) $location['university_id'];
            $campus_id = (int) $location['campus_id'];
            $stmt = $conn->prepare('
                INSERT INTO products
                    (user_id, title, author, course_code, isbn, book_condition, description, price, image, university_id, campus_id, status)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")
            ');
            $stmt->bind_param(
                'issssssdsii',
                $user_id,
                $old['title'],
                $old['author'],
                $old['course_code'],
                $old['isbn'],
                $old['book_condition'],
                $old['description'],
                $price,
                $image,
                $university_id,
                $campus_id
            );

            if ($stmt->execute()) {
                $success = 'Listing submitted successfully. It will appear in the marketplace after admin approval.';
                $old = [
                    'title' => '',
                    'author' => '',
                    'course_code' => '',
                    'isbn' => '',
                    'book_condition' => 'good',
                    'description' => '',
                    'price' => '',
                ];
            } else {
                $message = 'Listing could not be saved. Please try again.';
            }
        }
    }
}

$page_title = 'Sell Textbook';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="section-title mb-1">Sell a Textbook</h1>
            <p class="text-muted mb-0">Create a campus pickup listing for admin approval.</p>
        </div>
        <a href="<?= site_url('marketplace.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Marketplace
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="feature-card p-4">
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label">Book Title *</label>
                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($old['title']) ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Price (ZAR) *</label>
                <input type="number" step="0.01" min="1" class="form-control" name="price" value="<?= htmlspecialchars($old['price']) ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Author</label>
                <input type="text" class="form-control" name="author" value="<?= htmlspecialchars($old['author']) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Module / Course Code</label>
                <input type="text" class="form-control" name="course_code" value="<?= htmlspecialchars($old['course_code']) ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">ISBN</label>
                <input type="text" class="form-control" name="isbn" value="<?= htmlspecialchars($old['isbn']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Condition *</label>
            <select class="form-select" name="book_condition" required>
                <?php foreach (['new' => 'New', 'like_new' => 'Like New', 'good' => 'Good', 'fair' => 'Fair'] as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $old['book_condition'] === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php
        $selected_university = $_POST['university_id'] ?? ($_SESSION['university_id'] ?? '');
        $selected_campus = $_POST['campus_id'] ?? ($_SESSION['campus_id'] ?? '');
        require __DIR__ . '/includes/location_fields.php';
        ?>

        <div class="mb-3">
            <label class="form-label">Description *</label>
            <textarea class="form-control" name="description" rows="5" required><?= htmlspecialchars($old['description']) ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Book Image</label>
            <input type="file" class="form-control" name="book_image" accept="image/jpeg,image/png,image/webp" onchange="previewListingImage(event)">
            <img id="listingPreview" class="mt-3 rounded d-none" style="max-height:240px;object-fit:cover" alt="">
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-cloud-upload me-1"></i>Submit Listing
        </button>
    </form>
</div>

<script>
function previewListingImage(event) {
    const file = event.target.files && event.target.files[0];
    const img = document.getElementById('listingPreview');
    if (!file || !img) return;
    img.src = URL.createObjectURL(file);
    img.classList.remove('d-none');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
