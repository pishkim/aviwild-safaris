<?php
require_once 'auth.php';
require_once 'functions.php';   // ⭐ for log_system()
include 'config.php';           // ⭐ for $conn

/* =========================================================
   LOG THE LOGOUT BEFORE DESTROYING THE SESSION
   (after logout_user() the session is gone — we can't log then)
========================================================= */
if (is_logged_in()) {
    $logout_user = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown';

    log_system(
        $conn,
        'Logout',
        'User Logout',
        "$logout_user signed out",
        $_SERVER['REMOTE_ADDR'] ?? '',
        $logout_user
    );
}

/* =========================================================
   NOW DESTROY THE SESSION
========================================================= */
logout_user();

/* =========================================================
   SET FLASH + REDIRECT
   Note: flash() writes to $_SESSION, so it must be called
   AFTER logout_user() only if you want it to survive.
   Since logout_user() calls session_destroy() and unsets
   $_SESSION, we start a fresh session for the flash message.
========================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
flash("You have been signed out.", 'success', 'success');

echo '<script>window.location.href = "index.php";</script>';
exit();