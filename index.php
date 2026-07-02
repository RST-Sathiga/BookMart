<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';

$page_title = 'Home';

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION -->
<section class="hero-section d-flex align-items-center" style="min-height:75vh;">

    <div class="container text-center text-white">

        <span class="badge badge-gold mb-3">
            Campus Marketplace
        </span>

        <h1 class="display-4 fw-bold mb-4">
            Buy & Sell Textbooks on Your Campus
        </h1>

        <p class="lead mb-5 mx-auto" style="max-width:700px;">
            BookMart connects students across universities.
            Trade safely with campus pickup and secure payments.
        </p>

        <div class="d-flex justify-content-center gap-3 flex-wrap">

            <a href="marketplace.php"
               class="btn btn-gold btn-lg px-5 py-3 fs-5">
                Browse Marketplace
            </a>

            <?php if (current_user_id()): ?>

                <a href="sell.php"
                   class="btn btn-outline-light btn-lg px-5 py-3 fs-5">
                    Sell a Textbook
                </a>

            <?php else: ?>

                <a href="login.php?redirect=sell.php"
                   class="btn btn-outline-light btn-lg px-5 py-3 fs-5">
                    Sell a Textbook
                </a>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>