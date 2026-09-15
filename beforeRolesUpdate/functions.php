<?php
/* =========================================================
   ACTIVITY LOG HELPERS
   All functions are guarded against redeclaration.
========================================================= */

if (!function_exists('logActivity')) {
    function logActivity($conn, $user, $action, $target_type, $target_id = null, $target_title = null, $details = null) {
        $user         = mysqli_real_escape_string($conn, $user);
        $action       = mysqli_real_escape_string($conn, $action);
        $target_type  = mysqli_real_escape_string($conn, $target_type);
        $target_id    = $target_id ? (int)$target_id : 'NULL';
        $target_title = $target_title ? "'" . mysqli_real_escape_string($conn, $target_title) . "'" : 'NULL';
        $details      = $details ? "'" . mysqli_real_escape_string($conn, $details) . "'" : 'NULL';
        $ip           = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        $query = "INSERT INTO activity_log (user, action, target_type, target_id, target_title, details, ip_address)
                  VALUES ('$user', '$action', '$target_type', $target_id, $target_title, $details, '$ip')";

        mysqli_query($conn, $query);
    }
}

if (!function_exists('log_activity')) {
    function log_activity($conn, $action, $target_type, $target_title, $target_id = null, $details = '', $ip = null, $user = null) {
        $action       = mysqli_real_escape_string($conn, $action);
        $target_type  = mysqli_real_escape_string($conn, $target_type);
        $target_title = mysqli_real_escape_string($conn, $target_title);
        $target_id    = $target_id !== null ? (int)$target_id : 'NULL';
        $details      = mysqli_real_escape_string($conn, $details);
        $ip           = mysqli_real_escape_string($conn, $ip ?? ($_SERVER['REMOTE_ADDR'] ?? ''));
        $user         = mysqli_real_escape_string($conn, $user ?? ($_SESSION['username'] ?? 'Admin'));

        return mysqli_query($conn, "
            INSERT INTO activity_log 
            (user, action, target_type, target_title, target_id, details, ip_address)
            VALUES 
            ('$user', '$action', '$target_type', '$target_title',
             " . ($target_id === 'NULL' ? 'NULL' : $target_id) . ",
             '$details', '$ip')
        ");
    }
}

if (!function_exists('log_create')) {
    function log_create($conn, $type, $title, $id, $details = '') {
        return log_activity($conn, 'Created', $type, $title, $id, $details);
    }
}

if (!function_exists('log_update')) {
    function log_update($conn, $type, $title, $id, $details = '') {
        return log_activity($conn, 'Updated', $type, $title, $id, $details);
    }
}

if (!function_exists('log_delete')) {
    function log_delete($conn, $type, $title, $id, $details = '') {
        return log_activity($conn, 'Deleted', $type, $title, $id, $details);
    }
}

if (!function_exists('log_system')) {
    function log_system($conn, $action, $title, $details = '') {
        return log_activity($conn, $action, 'System', $title, null, $details);
    }
}