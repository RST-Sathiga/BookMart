<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$message = '';

/*
----------------------------------------
GET REDIRECT TARGET (FROM URL)
----------------------------------------
*/
$redirect_page = $_GET['redirect'] ?? 'index.php';

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {

            $message = 'Incorrect password.';

        } elseif ($user['status'] === 'banned') {

            $message = 'Your account has been banned.';

        } else {

            /*
            ----------------------------------------
            SESSION SETUP
            ----------------------------------------
            */
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = strtolower($user['role']);
            $_SESSION['user_type'] = strtolower($user['role']);
            $_SESSION['email'] = $user['email'];
            $_SESSION['university_id'] = $user['university_id'];
            $_SESSION['campus_id'] = $user['campus_id'];
            $_SESSION['profile_image'] = $user['profile_image'] ?? null;

            /*
            ----------------------------------------
            PROFILE IMAGE CHECK (PRIORITY REDIRECT)
            ----------------------------------------
            */
            if (empty($user['profile_image'])) {
                $_SESSION['redirect_after_profile'] = $redirect_page;
                header("Location: profile.php?required=1");
                exit();
            }

            /*
            ----------------------------------------
            ROLE REDIRECT
            ----------------------------------------
            */
            if ($_SESSION['user_type'] === 'admin') {
                header("Location: admin/index.php");
                exit();
            }

            /*
            ----------------------------------------
            NORMAL USER REDIRECT
            ----------------------------------------
            */
            header("Location: $redirect_page");
            exit();
        }

    } else {
        $message = 'No account found with that email.';
    }
}

$page_title = 'Login';
$body_class = 'auth-page';

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrap">
    <div class="container">
        <div class="auth-card">

            <h2 class="text-center mb-4 text-primary">Welcome Back</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary w-100">
                    Login
                </button>

            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
