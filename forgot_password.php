<?php

require_once __DIR__ . '/includes/auth.php';

$message = '';
$step = 1;

if (isset($_POST['find_user'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare('SELECT user_id, security_question FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['reset_email'] = $email;
        $_SESSION['security_question'] = $user['security_question'];
        $step = 2;
    } else {
        $message = 'No account found with that email.';
    }
}

if (isset($_POST['verify_answer'])) {
    $email = $_SESSION['reset_email'] ?? '';
    $answer = strtolower(trim($_POST['security_answer']));

    $stmt = $conn->prepare('SELECT security_answer FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($answer, $user['security_answer'])) {
        $step = 3;
    } else {
        $message = 'Incorrect security answer.';
        $step = 2;
    }
}

if (isset($_POST['reset_password'])) {
    $email = $_SESSION['reset_email'] ?? '';
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $step = 3;
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters.';
        $step = 3;
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE email = ?');
        $stmt->bind_param('ss', $hashed_password, $email);
        $stmt->execute();

        unset($_SESSION['reset_email'], $_SESSION['security_question']);
        header('Location: login.php?reset=success');
        exit();
    }
}

$page_title = 'Forgot Password';
$body_class = 'auth-page';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrap">
    <div class="container">
        <div class="auth-card">
            <h2 class="text-center mb-4 text-primary">Reset Password</h2>

            <?php if ($message): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" name="find_user" class="btn btn-primary w-100">Continue</button>
                </form>
            <?php elseif ($step === 2): ?>
                <form method="POST">
                    <p class="fw-semibold"><?= htmlspecialchars($_SESSION['security_question'] ?? '') ?></p>
                    <div class="mb-3">
                        <label class="form-label">Your Answer *</label>
                        <input type="text" name="security_answer" class="form-control" required>
                    </div>
                    <button type="submit" name="verify_answer" class="btn btn-primary w-100">Verify</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-primary w-100">Reset Password</button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
