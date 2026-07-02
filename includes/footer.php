</main>

<footer class="footer-bookmart bg-dark text-white py-4">

    <div class="container">

        <div class="row align-items-start">

            <!-- PLATFORM INFO -->
            <div class="col-md-4">
                <h5 class="mb-2">
                    <i class="bi bi-book-half me-2"></i>
                    <?= SITE_NAME ?>
                </h5>

                <p class="mb-2">
                    Campus textbook marketplace designed for safe student-to-student trading.
                    Pickup only on campus.
                </p>

                <small class="text-muted">
                    System Type: E-Commerce Marketplace<br>
                    Region: Campus Network Platform
                </small>
            </div>

            <!-- CONTACT DETAILS -->
            <div class="col-md-4">
                <h5 class="mb-2">Contact & Support</h5>

                <p class="mb-1">
                    Email: <a href="mailto:support@bookmart.co.za">support@bookmart.co.za</a>
                </p>

                <p class="mb-1">
                    Tel: <a href="tel:+27110000000">+27 11 000 0000</a>
                </p>

                <p class="mb-1">
                    WhatsApp: <a href="https://wa.me/27710000000" target="_blank">
                        Chat on WhatsApp
                    </a>
                </p>
            </div>

            <!-- NAVIGATION / USER LINKS -->
            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <a href="<?= site_url('marketplace.php') ?>">Browse Textbooks</a>

                <?php if (current_user_id()): ?>
                    <span class="mx-2">|</span>
                    <a href="<?= site_url('account.php') ?>">My Account</a>
                <?php else: ?>
                    <span class="mx-2">|</span>
                    <a href="<?= site_url('register.php') ?>">Join Now</a>
                <?php endif; ?>

                <hr class="text-light">

                <small class="text-muted">
                    For system queries, disputes, or reports use official contact channels above.
                </small>

            </div>

        </div>

        <hr class="text-light">

        <div class="text-center">
            <small>
                © <?= date("Y") ?> <?= SITE_NAME ?>. All rights reserved.
            </small>
        </div>

    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= site_url('assets/js/main.js') ?>"></script>

<?php if (!empty($load_face_capture)): ?>
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/dist/face-api.min.js"></script>
<script src="<?= site_url('assets/js/face-capture.js') ?>"></script>
<?php endif; ?>

<?php if (!empty($load_chat_call)): ?>
<script src="<?= site_url('assets/js/chat-call.js') ?>"></script>
<?php endif; ?>

</body>
</html>