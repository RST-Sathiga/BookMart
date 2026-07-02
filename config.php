<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookmart');

define('SITE_NAME', 'BookMart');

if (!function_exists('bookmart_detect_base_url')) {
    function bookmart_detect_base_url(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));

        if ($docRoot && str_starts_with($projectRoot, $docRoot)) {
            $basePath = substr($projectRoot, strlen($docRoot));
            return rtrim($protocol . '://' . $host . $basePath, '/');
        }

        return rtrim($protocol . '://' . $host . '/Bookmart', '/');
    }
}

define('SITE_URL', bookmart_detect_base_url());
define('PLATFORM_URL', SITE_URL);

define('COMMISSION_RATE', 0.15);
define('ADMIN_EMAIL', 'admin@bookmart.com');

define('PAYFAST_MERCHANT_ID', '10000100');
define('PAYFAST_MERCHANT_KEY', '46f0cd694581a');
define('PAYFAST_PASSPHRASE', '');
define('PAYFAST_SANDBOX', true);

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('PROFILE_UPLOAD_DIR', __DIR__ . '/../uploads/profiles/');
define('PROFILE_UPLOAD_URL', SITE_URL . '/uploads/profiles/');
