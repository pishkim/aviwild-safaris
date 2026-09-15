<?php
/* =========================================================
   ONE-SHOT ADMIN CREATOR
   Run once, then DELETE this file from the server.
========================================================= */

require_once 'config.php';

/* ---------- CONFIGURE HERE ---------- */
$admin_username = 'admin';              // change if you like
$admin_email    = 'admin@aviwild.test';
$admin_password = 'Admin@2025!';        // change before running
$admin_fullname = 'System Administrator';
$admin_role     = 'Admin';              // must match roles.role_name
/* ------------------------------------- */

header('Content-Type: text/plain; charset=utf-8');

/* =========================================================
   1. Make sure the Admin role exists
========================================================= */
$role_r = mysqli_query($conn, "SELECT id FROM roles WHERE role_name = '" . mysqli_real_escape_string($conn, $admin_role) . "' LIMIT 1");

if (!$role_r || mysqli_num_rows($role_r) === 0) {
    mysqli_query($conn, "INSERT INTO roles (role_name, description, is_system) VALUES ('" . mysqli_real_escape_string($conn, $admin_role) . "', 'Full access to everything', 1)");
    $role_id = mysqli_insert_id($conn);
    echo "➕ Created role: $admin_role (id=$role_id)\n";
} else {
    $role_id = (int)mysqli_fetch_assoc($role_r)['id'];
    echo "✓ Role '$admin_role' already exists (id=$role_id)\n";
}

/* =========================================================
   2. Grant EVERY permission to Admin
========================================================= */
mysqli_query($conn, "
    INSERT IGNORE INTO role_permissions (role_id, permission_id)
    SELECT $role_id, p.id FROM permissions p
");
$granted = mysqli_affected_rows($conn);
echo "✓ Granted $granted new permission(s) to Admin\n";

/* =========================================================
   3. Check if profiles has the columns we need
========================================================= */
$cols_r = mysqli_query($conn, "SHOW COLUMNS FROM profiles");
$cols = [];
while ($c = mysqli_fetch_assoc($cols_r)) $cols[] = $c['Field'];

echo "ℹ️  profiles columns: " . implode(', ', $cols) . "\n";

/* Detect username column name (username vs email-only) */
$has_username = in_array('username', $cols);
$has_fullname = in_array('full_name', $cols);
$has_status   = in_array('status', $cols);

/* =========================================================
   4. Insert or update admin user
========================================================= */
$hash = password_hash($admin_password, PASSWORD_DEFAULT);
$hash_esc = mysqli_real_escape_string($conn, $hash);
$email_esc = mysqli_real_escape_string($conn, $admin_email);
$name_esc  = mysqli_real_escape_string($conn, $admin_fullname);
$role_esc  = mysqli_real_escape_string($conn, $admin_role);
$user_esc  = mysqli_real_escape_string($conn, $admin_username);

/* Check existing */
$existing = mysqli_query($conn, "SELECT id FROM profiles WHERE email = '$email_esc' LIMIT 1");

if ($existing && mysqli_num_rows($existing) > 0) {
    $uid = (int)mysqli_fetch_assoc($existing)['id'];

    $sets = [
        "password = '$hash_esc'",
        "role     = '$role_esc'"
    ];
    if ($has_username) $sets[] = "username = '$user_esc'";
    if ($has_fullname) $sets[] = "full_name = '$name_esc'";
    if ($has_status)   $sets[] = "status = 'active'";

    mysqli_query($conn, "UPDATE profiles SET " . implode(', ', $sets) . " WHERE id = $uid");
    echo "🔄 Updated existing admin (id=$uid)\n";
} else {
    $fields = ['email','password','role'];
    $values = ["'$email_esc'", "'$hash_esc'", "'$role_esc'"];

    if ($has_username) { $fields[] = 'username';  $values[] = "'$user_esc'"; }
    if ($has_fullname) { $fields[] = 'full_name'; $values[] = "'$name_esc'"; }
    if ($has_status)   { $fields[] = 'status';    $values[] = "'active'"; }

    $sql = "INSERT INTO profiles (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";

    if (mysqli_query($conn, $sql)) {
        echo "✅ Created admin user (id=" . mysqli_insert_id($conn) . ")\n";
    } else {
        echo "❌ Insert failed: " . mysqli_error($conn) . "\n";
        exit();
    }
}

/* =========================================================
   5. Summary
========================================================= */
echo "\n============================\n";
echo "ADMIN READY\n";
echo "============================\n";
echo "Email:    $admin_email\n";
if ($has_username) echo "Username: $admin_username\n";
echo "Password: $admin_password\n";
echo "Role:     $admin_role (role_id=$role_id)\n";
echo "\n⚠️  DELETE THIS FILE NOW: create_admin.php\n";