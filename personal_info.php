<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

require_login();

$user_id = current_user_id();
$user = get_user_by_id($conn, $user_id);

if (!$user) {
    die("User not found");
}

$message = '';

/*
|--------------------------------------------------------------------------
| LOAD LOCATION DATA SAFELY
|--------------------------------------------------------------------------
*/
$location_grouped = get_universities($conn);
/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMIT
|--------------------------------------------------------------------------
*/
if (isset($_POST['save_info'])) {

    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $course   = trim($_POST['course'] ?? '');
    $universities= trim($_POST['universities'] ?? '');
    $campus_id = trim($_POST['campuses'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */
    if ($fullname === '' || $phone === '' || $course === '') {
        $message = "All required fields must be filled.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | FIX: DIRECT INPUT VALUES (NO BROKEN STRUCTURE DEPENDENCY)
        |--------------------------------------------------------------------------
        */
        $university_id = (int) ($_POST['university_id'] ?? 0);
        $campus_id     = (int) ($_POST['campus_id'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | STUDENT CARD UPLOAD
        |--------------------------------------------------------------------------
        */
        $student_card = $user['student_card_image'] ?? null;

        if (!empty($_FILES['student_card']['name'])) {

            $uploaded = upload_student_card($_FILES['student_card']);

            if ($uploaded) {

                if (!empty($user['student_card_image']) &&
                    file_exists(__DIR__ . "/uploads/" . $user['student_card_image'])) {
                    unlink(__DIR__ . "/uploads/" . $user['student_card_image']);
                }

                $student_card = $uploaded;
            } else {
                $message = "Invalid student card upload.";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE (SAFE + VERIFIED)
        |--------------------------------------------------------------------------
        */
        if ($message === '') {

            $stmt = $conn->prepare("
                UPDATE users 
                SET fullname = ?, 
                    phone = ?, 
                    course = ?, 
                    university_id = ?, 
                    campus_id = ?, 
                    student_card_image = ?
                WHERE user_id = ?
            ");

            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssissi",
                $fullname,
                $phone,
                $course,
                $university_id,
                $campus_id,
                $student_card,
                $user_id
            );

            if ($stmt->execute()) {

                // refresh session (CRITICAL for "outstanding" issue)
                $_SESSION['fullname'] = $fullname;
                $_SESSION['university_id'] = $university_id;
                $_SESSION['campus_id'] = $campus_id;

                header("Location: personal_info.php?saved=1");
                exit();

            } else {
                die("Database error: " . $stmt->error);
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| PAGE SETUP
|--------------------------------------------------------------------------
*/
$page_title = 'Personal Information';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/account_layout_start.php';

?>

<h1 class="section-title">Personal Information</h1>

<?php if (!empty($_GET['saved'])): ?>
    <div class="alert alert-success">Information updated successfully.</div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="feature-card p-4">

<form method="POST" enctype="multipart/form-data">

    <div class="row">

        <div class="col-md-6 mb-3">
            <label>Full Name *</label>
            <input type="text" name="fullname" class="form-control"
                   value="<?= htmlspecialchars($user['fullname']) ?>" required>
        </div>

        <div class="col-md-6 mb-3">
            <label>Phone *</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($user['phone']) ?>" required>
        </div>

    </div>

    <div class="mb-3">
        <label>Course *</label>
        <input type="text" name="course" class="form-control"
               value="<?= htmlspecialchars($user['course'] ?? '') ?>" required>
    </div>

    <div class="row">

        <div class="col-md-6 mb-3">
            <label>University</label>
            <select name="university_id" class="form-control">
                <option value="0">Select University</option>
                <?php foreach ($location_grouped as $uni): ?>
                    <option value="<?= $uni['id'] ?>"
                        <?= ($user['university_id'] == $uni['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($uni['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
<?php
$campuses = $conn->query("
    SELECT id, name
    FROM campuses
    ORDER BY name
");
?>
        <div class="col-md-6 mb-3">
    <label class="form-label">Campus</label>

    <select name="campus_id" class="form-control" required>

        <option value="">Select Campus</option>

        <?php while ($campus = $campuses->fetch_assoc()): ?>

            <option
                value="<?= (int)$campus['id'] ?>"
                <?= ((int)($user['campus_id'] ?? 0) === (int)$campus['id']) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($campus['name']) ?>
            </option>

        <?php endwhile; ?>

    </select>
        </div>

    </div>

    <div class="mb-3">
        <label>Student Card</label><br>

        <?php if (!empty($user['student_card_image'])): ?>
            <img src="uploads/<?= htmlspecialchars($user['student_card_image']) ?>"
                 style="max-height:120px" class="mb-2">
        <?php endif; ?>

        <input type="file" name="student_card" class="form-control">
    </div>

    <button type="submit" name="save_info" class="btn btn-primary">
        Save Changes
    </button>

</form>

</div>

<?php
require_once __DIR__ . '/includes/account_layout_end.php';
require_once __DIR__ . '/includes/footer.php';
?>