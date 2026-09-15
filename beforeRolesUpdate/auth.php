<?php
// Authentication helpers — include this in any page that needs auth

// if (session_status() === PHP_SESSION_NONE) {
//     //session_start();
// }

/**
 * Is a user currently logged in?
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the logged-in user's full profile row from the DB.
 * Returns null if not logged in or user no longer exists.
 */
function current_user($conn) {
    if (!is_logged_in()) return null;

    $id = (int) $_SESSION['user_id'];
    $r  = mysqli_query($conn, "SELECT * FROM profiles WHERE id = $id LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) return null;

    return mysqli_fetch_assoc($r);
}

/**
 * Force login. Redirects to login.php if not authenticated.
 * Call this at the top of any protected page (or in bridge.php).
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Ensure guest. Redirects to dashboard if already logged in.
 * Use on login/register pages to prevent double-login.
 */
function require_guest() {
    if (is_logged_in()) {
        header("Location: ?section=dashboard");
        exit();
    }
}

/**
 * Log a user in: sets session, regenerates ID, updates last_login.
 * $user is a row from `profiles`.
 */
function login_user($conn, $user) {
    session_regenerate_id(true);   // prevent session fixation

    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];

    $id = (int) $user['id'];
    mysqli_query($conn, "UPDATE profiles SET last_login = NOW() WHERE id = $id");
}

/**
 * Log the current user out and destroy the session fully.
 */
function logout_user() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

/**
 * Set a flash message to be shown on the next page load.
 */
function flash($message, $type = 'success', $icon = 'success') {
    $_SESSION['message']      = $message;
    $_SESSION['message_type'] = $type;
    $_SESSION['message_icon'] = $icon;
}

/**
 * Pull and clear the flash message.
 */
function pull_flash() {
    $m = [
        'text' => $_SESSION['message']      ?? '',
        'type' => $_SESSION['message_type'] ?? '',
        'icon' => $_SESSION['message_icon'] ?? '',
    ];
    unset($_SESSION['message'], $_SESSION['message_type'], $_SESSION['message_icon']);
    return $m;
}