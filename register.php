<?php

require_once __DIR__ . '/includes/auth.php';

$message = '';

if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $id_passport = strtoupper(trim($_POST['id_passport_number'] ?? ''));
    $course = trim($_POST['course'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer = strtolower(trim($_POST['security_answer'] ?? ''));
    $location = resolve_location_from_request($conn, $_POST);

    $missing = [];
    foreach ([
        'Full Name' => $fullname,
        'Username' => $username,
        'Email' => $email,
        'Phone' => $phone,
        'Student Number' => $student_number,
        'ID / Passport' => $id_passport,
        'Course' => $course,
        'Password' => $password,
        'Security Question' => $security_question,
        'Security Answer' => $security_answer,
    ] as $label => $value) {
        if ($value === '') {
            $missing[] = $label;
        }
    }

    if ($missing) {
        $message = 'Missing: ' . implode(', ', $missing);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address.';
    } elseif (!validate_id_passport($id_passport)) {
        $message = 'ID / Passport must be 6 to 20 letters or numbers.';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif (isset($location['error'])) {
        $message = $location['error'];
    } else {
        $profile_image = upload_profile_image($_FILES['profile_image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
        $student_card = upload_student_card($_FILES['student_card'] ?? ['error' => UPLOAD_ERR_NO_FILE]);

        if (!$profile_image || !$student_card) {
            $message = 'Upload a valid profile photo and student card image.';
        } else {
            $check = $conn->prepare('
                SELECT user_id FROM users
                WHERE email = ? OR username = ? OR id_passport_number = ? OR student_number = ?
                LIMIT 1
            ');
            $check->bind_param('ssss', $email, $username, $id_passport, $student_number);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $message = 'An account already exists with that email, username, ID/passport, or student number.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $hashed_answer = password_hash($security_answer, PASSWORD_BCRYPT);
                $role = 'user';
                $status = 'active';
                $university_id = (int) $location['university_id'];
                $campus_id = (int) $location['campus_id'];

                $stmt = $conn->prepare('
                    INSERT INTO users
                        (fullname, username, email, phone, student_number, id_passport_number, password,
                         university_id, campus_id, course, profile_image, student_card_image,
                         security_question, security_answer, role, status)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->bind_param(
                    'sssssssiisssssss',
                    $fullname,
                    $username,
                    $email,
                    $phone,
                    $student_number,
                    $id_passport,
                    $hashed_password,
                    $university_id,
                    $campus_id,
                    $course,
                    $profile_image,
                    $student_card,
                    $security_question,
                    $hashed_answer,
                    $role,
                    $status
                );

                if ($stmt->execute()) {
                    header('Location: ' . site_url('login.php?registered=1'));
                    exit();
                }

                $message = 'Registration failed. Please run the database repair from database/install.php, then try again.';
            }
        }
    }
}

$page_title = 'Register';
$body_class = 'auth-page';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.auth-card { max-width: 680px; margin: auto; padding: 24px; }
.auth-card .form-control, .auth-card .form-select { min-height: 48px; }
.upload-label { font-weight: 600; margin-top: 10px; display: block; }
</style>

<div class="container py-5">
    <div class="auth-card feature-card">
        <h1 class="h3 mb-3">Create Account</h1>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div id="step1">
                <h2 class="h5 mb-3">Personal Information</h2>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <input type="text" name="fullname" class="form-control" placeholder="Full Name" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="text" name="phone" class="form-control" placeholder="Phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="text" name="student_number" class="form-control" placeholder="Student Number" value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="text" name="id_passport_number" class="form-control" placeholder="ID / Passport Number" value="<?= htmlspecialchars($_POST['id_passport_number'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    </div>
                </div>

                <button type="button" onclick="goToStep2()" class="btn btn-primary w-100">Next</button>
            </div>

            <div id="step2" style="display:none;">
                <h2 class="h5 mb-3">Academic Details</h2>

                <div class="mb-3">
                    <input type="text" name="course" class="form-control" placeholder="Course" value="<?= htmlspecialchars($_POST['course'] ?? '') ?>" required>
                </div>

                <?php require __DIR__ . '/includes/location_fields.php'; ?>

                <label class="upload-label">Profile Image (clear face photo)</label>
                <input type="file" name="profile_image" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" required>

                <label class="upload-label">Student Card</label>
                <input type="file" name="student_card" class="form-control mb-3" accept="image/jpeg,image/png,image/webp" required>

                <select name="security_question" class="form-select mb-3" required>
                    <option value="">Select Security Question</option>
                    <option <?= ($_POST['security_question'] ?? '') === "What was your first pet's name?" ? 'selected' : '' ?>>What was your first pet's name?</option>
                    <option <?= ($_POST['security_question'] ?? '') === 'What primary school did you attend?' ? 'selected' : '' ?>>What primary school did you attend?</option>
                </select>

                <input type="text" name="security_answer" class="form-control mb-3" placeholder="Security Answer" required>

                <button type="submit" name="register" class="btn btn-success w-100">Create Account</button>
                <button type="button" onclick="backToStep1()" class="btn btn-secondary w-100 mt-2">Back</button>
            </div>
        </form>
    </div>
</div>

<script>
function goToStep2() {
    const inputs = document.querySelectorAll('#step1 input');
    for (const input of inputs) {
        if (!input.value) {
            alert('Complete all fields in Step 1');
            return;
        }
    }

    if (document.querySelector('[name="password"]').value !== document.querySelector('[name="confirm_password"]').value) {
        alert('Passwords do not match');
        return;
    }

    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
}

function backToStep1() {
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
