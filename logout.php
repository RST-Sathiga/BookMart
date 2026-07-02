<?php

require_once __DIR__ . '/config/config.php';

session_start();

// Clear session array
$_SESSION = [];

// Destroy session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Redirect to homepage (FIXED - no site_url needed)
header('Location: index.php');
exit();