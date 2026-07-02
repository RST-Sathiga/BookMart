<?php

require_once __DIR__ . '/includes/auth.php';

require_login();

$user_id = current_user_id();
$user = get_user_by_id($conn, $user_id);

if (!$user) {
    http_response_code(404);
    die('User not found.');
}

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['profile_image']['name'])) {
        $message = 'Choose a clear JPG, PNG, or WEBP profile photo.';
    } else {
        $uploaded = upload_profile_image($_FILES['profile_image']);

        if (!$uploaded) {
            $message = 'Profile photo upload failed. Use JPG, PNG, or WEBP under 2MB.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET profile_image = ? WHERE user_id = ?');
            $stmt->bind_param('si', $uploaded, $user_id);

            if ($stmt->execute()) {
                if (!empty($user['profile_image'])) {
                    $old_file = PROFILE_UPLOAD_DIR . $user['profile_image'];
                    if (is_file($old_file)) {
                        @unlink($old_file);
                    }
                }

                $_SESSION['profile_image'] = $uploaded;
                $success = 'Profile photo updated successfully.';
                $user['profile_image'] = $uploaded;

                if (!empty($_SESSION['redirect_after_profile'])) {
                    $redirect = $_SESSION['redirect_after_profile'];
                    unset($_SESSION['redirect_after_profile']);
                    header('Location: ' . $redirect);
                    exit();
                }
            } else {
                $message = 'Could not save your profile photo. Please try again.';
            }
        }
    }
}

$page_title = 'Profile Photo';
$account_page = 'photo';
$back_url = !empty($_GET['required']) ? null : site_url('account.php');
$back_label = 'Account';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';
?>

<h1 class="section-title">Profile Photo</h1>

<?php if (!empty($_GET['required'])): ?>
    <div class="alert alert-warning">A clear facial profile photo is required for secure campus pickups.</div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="feature-card p-4">
    <div class="d-flex flex-wrap align-items-center gap-4 mb-4">
        <img src="<?= profile_image_url($user['profile_image'] ?? null) ?>" alt="Profile photo" class="rounded-circle" width="120" height="120" style="object-fit:cover">
        <div>
            <h2 class="h5 mb-1"><?= htmlspecialchars($user['fullname']) ?></h2>
            <p class="text-muted mb-0">Upload a clear photo of yourself for buyer and seller pickup verification.</p>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="profile_image" class="form-control" accept="image/jpeg,image/png,image/webp" required>
            <div class="form-text">Accepted formats: JPG, PNG, WEBP. Maximum size: 2MB.</div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-camera me-1"></i>Save Photo
        </button>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
?>
