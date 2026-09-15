<?php
/* =========================================================
   AUTH — session, login, guards, flash
   All functions defined at top level, in dependency order.
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    //session_start();
}

/* =========================================================
   1. BASE HELPERS
========================================================= */

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('current_username')) {
    function current_username() {
        return $_SESSION['username'] ?? 'Guest';
    }
}

/* =========================================================
   2. FLASH MESSAGES
========================================================= */

if (!function_exists('flash')) {
    function flash($message, $type = 'success', $icon = 'success') {
        $_SESSION['message']      = $message;
        $_SESSION['message_type'] = $type;
        $_SESSION['message_icon'] = $icon;
    }
}

if (!function_exists('pull_flash')) {
    function pull_flash() {
        $m = [
            'text' => $_SESSION['message']      ?? '',
            'type' => $_SESSION['message_type'] ?? '',
            'icon' => $_SESSION['message_icon'] ?? '',
        ];
        unset($_SESSION['message'], $_SESSION['message_type'], $_SESSION['message_icon']);
        return $m;
    }
}

/* =========================================================
   3. LOGIN / LOGOUT  (single definition each)
========================================================= */

if (!function_exists('login_user')) {
    /**
     * Log a user in. $user is a row from `profiles`.
     * Sets the session keys used across the app:
     *   user_id, username, email, full_name, role_id
     */
    function login_user($conn, $user) {
        session_regenerate_id(true);   // prevent session fixation

        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['username']  = $user['username'] ?? $user['email'] ?? 'user';
        $_SESSION['email']     = $user['email']     ?? '';
        $_SESSION['full_name'] = $user['full_name'] ?? $_SESSION['username'];
        $_SESSION['role']      = $user['role']      ?? null;

        // RBAC — map role name → role_id from the roles table
        if (!empty($user['role'])) {
            $role_name = mysqli_real_escape_string($conn, $user['role']);
            $r = mysqli_query($conn, "SELECT id FROM roles WHERE role_name = '$role_name' LIMIT 1");
            if ($r && mysqli_num_rows($r) > 0) {
                $_SESSION['role_id'] = (int)mysqli_fetch_assoc($r)['id'];
            }
        }

        // Update last login (silently ignore if column doesn't exist)
        @mysqli_query($conn, "UPDATE profiles SET last_login = NOW() WHERE id = " . (int)$user['id']);
    }
}

if (!function_exists('logout_user')) {
    function logout_user() {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }
}

/* =========================================================
   4. GUARDS  (defined AFTER is_logged_in)
========================================================= */

if (!function_exists('require_login')) {
    function require_login($redirect = 'login.php') {
        if (!is_logged_in()) {
            flash("Please sign in to continue.", 'error', 'error');
            header("Location: $redirect");
            exit();
        }
    }
}

if (!function_exists('require_guest')) {
    function require_guest($redirect = 'home.php?section=dashboard') {
        if (is_logged_in()) {
            header("Location: $redirect");
            exit();
        }
    }
}

/* =========================================================
   5. USER LOOKUP
========================================================= */

if (!function_exists('current_user')) {
    function current_user($conn) {
        if (!is_logged_in()) return null;

        $id = (int)$_SESSION['user_id'];
        $r  = mysqli_query($conn, "SELECT * FROM profiles WHERE id = $id LIMIT 1");
        if (!$r || mysqli_num_rows($r) === 0) return null;

        return mysqli_fetch_assoc($r);
    }
}