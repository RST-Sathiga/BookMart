<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/functions.php';

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'account.php';
        header('Location: ' . site_url('login.php'));
        exit();
    }
}

function require_admin(): void
{
    $role = $_SESSION['role'] ?? $_SESSION['user_type'] ?? '';

    if (!isset($_SESSION['user_id']) || strtolower($role) !== 'admin') {
        header('Location: ' . site_url('login.php'));
        exit();
    }
}

function is_admin(): bool
{
    $role = $_SESSION['role'] ?? $_SESSION['user_type'] ?? '';
    return strtolower($role) === 'admin';
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function user_has_profile_photo(mysqli $conn, int $user_id): bool
{
    $user = get_user_by_id($conn, $user_id);

    if (!$user || empty($user['profile_image'])) {
        return false;
    }

    return is_file(PROFILE_UPLOAD_DIR . $user['profile_image']);
}

function require_profile_photo_for_pickup(mysqli $conn): void
{
    require_login();

    if (!user_has_profile_photo($conn, current_user_id())) {
        $_SESSION['flash_message'] = 'A facial profile photo is required for secure campus pickups.';
        $_SESSION['redirect_after_profile'] = $_SERVER['REQUEST_URI'] ?? 'account.php';
        header('Location: ' . site_url('profile.php'));
        exit();
    }
}
