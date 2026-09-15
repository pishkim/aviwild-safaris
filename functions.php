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

/* =========================================================
   SESSION HELPERS
========================================================= */

if (!function_exists('current_user_id')) {
    function current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

/* =========================================================
   RBAC — ROLE BASED ACCESS CONTROL
   Uses profiles.role (name) → roles.role_name → role_id
========================================================= */

if (!function_exists('resolve_role_id')) {
    /**
     * Get the role_id for the currently logged-in user.
     * Caches it in $_SESSION so we only query once.
     */
    function resolve_role_id($conn, $user_id = null) {
        // Fast path: session already has it and we're checking self
        if ($user_id === null || $user_id === ($_SESSION['user_id'] ?? null)) {
            if (!empty($_SESSION['role_id'])) return (int)$_SESSION['role_id'];
        }

        $user_id = $user_id ?? current_user_id();
        if (!$user_id) return null;

        // Look up the role name from profiles
        $uid = (int)$user_id;
        $r = mysqli_query($conn, "SELECT role FROM profiles WHERE id = $uid LIMIT 1");
        if (!$r || mysqli_num_rows($r) === 0) return null;

        $role_name = mysqli_fetch_assoc($r)['role'] ?? '';
        if ($role_name === '') return null;

        // Map role_name → role_id
        $role_name_esc = mysqli_real_escape_string($conn, $role_name);
        $rr = mysqli_query($conn, "SELECT id FROM roles WHERE role_name = '$role_name_esc' LIMIT 1");
        if (!$rr || mysqli_num_rows($rr) === 0) return null;

        $role_id = (int)mysqli_fetch_assoc($rr)['id'];

        // Cache for self
        if ($user_id == ($_SESSION['user_id'] ?? null)) {
            $_SESSION['role_id'] = $role_id;
            $_SESSION['role']    = $role_name;
        }

        return $role_id;
    }
}

if (!function_exists('has_permission')) {
    /**
     * Check if a user has a given permission.
     * Queries role_permissions → permissions using the user's role_id.
     */
    function has_permission($conn, $permission_key, $user_id = null) {
        $role_id = resolve_role_id($conn, $user_id);
        if (!$role_id) return false;

        $permission_key = mysqli_real_escape_string($conn, $permission_key);

        $sql = "SELECT COUNT(*) AS c
                FROM role_permissions rp
                JOIN permissions p ON p.id = rp.permission_id
                WHERE rp.role_id = $role_id
                  AND p.permission_key = '$permission_key'";

        $r = mysqli_query($conn, $sql);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        return $row && (int)$row['c'] > 0;
    }
}

if (!function_exists('require_permission')) {
    function require_permission($conn, $permission_key, $redirect = '?section=dashboard') {
        if (!has_permission($conn, $permission_key)) {
            $_SESSION['message']      = "You don't have permission to access that area.";
            $_SESSION['message_type'] = "error";
            $_SESSION['message_icon'] = "error";
            echo '<script>window.location.href = "' . $redirect . '";</script>';
            exit();
        }
    }
}

if (!function_exists('get_role_permissions')) {
    function get_role_permissions($conn, $role_id) {
        $role_id = (int)$role_id;
        $perms = [];
        $r = mysqli_query($conn, "
            SELECT p.permission_key
            FROM role_permissions rp
            JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = $role_id
        ");
        if ($r) { while ($row = mysqli_fetch_assoc($r)) $perms[] = $row['permission_key']; }
        return $perms;
    }
}

if (!function_exists('current_role_name')) {
    /**
     * Get the current user's role name (e.g., "Admin", "Editor").
     */
    function current_role_name($conn) {
        // Fast path: cached in session
        if (!empty($_SESSION['role'])) return $_SESSION['role'];

        $uid = current_user_id();
        if (!$uid) return 'Guest';

        $r = mysqli_query($conn, "SELECT role FROM profiles WHERE id = " . (int)$uid . " LIMIT 1");
        if (!$r || mysqli_num_rows($r) === 0) return 'Guest';

        $role_name = mysqli_fetch_assoc($r)['role'] ?: 'Guest';
        $_SESSION['role'] = $role_name;   // cache it
        return $role_name;
    }
}

if (!function_exists('current_role_id')) {
    function current_role_id($conn) {
        return resolve_role_id($conn);
    }
}