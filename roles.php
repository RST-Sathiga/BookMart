<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| ROLE HANDLING
|--------------------------------------------------------------------------
*/
if (isset($_POST['user_type'])) {

    $role = strtolower(trim($_POST['user_type']));

    // ADMIN (NOT SHOWN IN UI — SYSTEM ONLY)
    if ($role === 'admin') {
        $_SESSION['user_type'] = 'admin';
        header("Location: admin_dashboard.php");
        exit();
    }

    // BUYER + SELLER SHARE SAME DASHBOARD
    if ($role === 'buyer' || $role === 'seller') {
        $_SESSION['user_type'] = $role;
        header("Location: user_dashboard.php");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| AUTO REDIRECT IF ALREADY SET
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['user_type'])) {

    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    }

    header("Location: user_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Account Type</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-5">

            <div class="card shadow">

                <div class="card-body text-center">

                    <h3 class="mb-4">Select Account Type</h3>

                    <form method="POST">

                        <button type="submit"
                                name="user_type"
                                value="buyer"
                                class="btn btn-primary w-100 mb-3">

                            Buyer
                        </button>

                        <button type="submit"
                                name="user_type"
                                value="seller"
                                class="btn btn-success w-100">

                            Seller
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>