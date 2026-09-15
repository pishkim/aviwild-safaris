<?php
require_once 'config.php';
require_once 'functions.php';

require_permission($conn, 'users.view');

/* =========================================================
   CREATE USER
========================================================= */
if (isset($_POST['create_user'])) {
    require_permission($conn, 'users.create');

    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm']  ?? '';
    $role_name = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));

    if ($full_name === '' || $email === '' || $password === '' || $role_name === '') {
        flash("Please fill in all required fields.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash("Please enter a valid email address.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    if (strlen($password) < 6) {
        flash("Password must be at least 6 characters.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    if ($password !== $confirm) {
        flash("Passwords do not match.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    $chk = mysqli_query($conn, "SELECT id FROM profiles WHERE email = '$email' LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        flash("An account with that email already exists.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    $role_chk = mysqli_query($conn, "SELECT id FROM roles WHERE role_name = '$role_name' LIMIT 1");
    if (!$role_chk || mysqli_num_rows($role_chk) === 0) {
        flash("Invalid role selected.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    $hash     = password_hash($password, PASSWORD_DEFAULT);
    $hash_esc = mysqli_real_escape_string($conn, $hash);

    $sql = "INSERT INTO profiles (full_name, email, password, phone, role, status)
            VALUES ('$full_name', '$email', '$hash_esc', '$phone', '$role_name', 'active')";

    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);

        /* ⭐ LOG: rich creation entry */
        log_create(
            $conn,
            'User Account',
            $full_name,
            $new_id,
            "New {$role_name} account created • email: {$email}"
            . ($phone !== '' ? " • phone: {$phone}" : '')
        );

        flash("User created successfully!", 'success', 'success');
    } else {
        flash("Error creating user: " . mysqli_error($conn), 'error', 'error');
    }
    echo '<script>window.location.href="?section=users";</script>'; exit();
}

/* =========================================================
   UPDATE USER — detect what changed
========================================================= */
if (isset($_POST['update_user'])) {
    require_permission($conn, 'users.edit');

    $id        = (int)$_POST['user_id'];
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role_name = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $status    = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active');
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));

    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm']  ?? '';

    /* ⭐ Fetch OLD values for comparison */
    $old = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT full_name, email, phone, role, status FROM profiles WHERE id = $id"
    ));
    if (!$old) {
        flash("User not found.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    /* Password validation (only if being changed) */
    $pwd_sql = '';
    $password_changed = false;
    if ($password !== '' || $confirm !== '') {
        if (strlen($password) < 6) {
            flash("Password must be at least 6 characters.", 'error', 'error');
            echo '<script>window.location.href="?section=users";</script>'; exit();
        }
        if ($password !== $confirm) {
            flash("Passwords do not match.", 'error', 'error');
            echo '<script>window.location.href="?section=users";</script>'; exit();
        }
        $hash    = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
        $pwd_sql = ", password = '$hash'";
        $password_changed = true;
    }

    $sql = "UPDATE profiles SET
                full_name = '$full_name',
                email     = '$email',
                role      = '$role_name',
                status    = '$status',
                phone     = '$phone'
                $pwd_sql
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {

        /* ⭐ Build a detailed change list */
        $changes = [];
        if ($old['full_name'] !== trim($_POST['full_name']))
            $changes[] = "name: '{$old['full_name']}' → '{$full_name}'";
        if ($old['email'] !== trim($_POST['email']))
            $changes[] = "email: {$old['email']} → {$email}";
        if ($old['phone'] !== trim($_POST['phone'] ?? ''))
            $changes[] = "phone updated";
        if ($old['role'] !== trim($_POST['role_name']))
            $changes[] = "role: {$old['role']} → {$role_name}";
        if ($old['status'] !== $status)
            $changes[] = "status: {$old['status']} → {$status}";
        if ($password_changed)
            $changes[] = "password reset";

        $details = empty($changes)
            ? "User saved (no changes)"
            : $full_name . " — " . implode(' • ', $changes);

        log_update($conn, 'User Account', $full_name, $id, $details);

        flash("User updated successfully!", 'success', 'success');
    } else {
        flash("Error updating user: " . mysqli_error($conn), 'error', 'error');
    }
    echo '<script>window.location.href="?section=users";</script>'; exit();
}

/* =========================================================
   DELETE USER
========================================================= */
if (isset($_GET['delete_user'])) {
    require_permission($conn, 'users.delete');
    $id = (int)$_GET['delete_user'];

    if ($id === (int)current_user_id()) {
        flash("You can't delete your own account.", 'error', 'error');
        echo '<script>window.location.href="?section=users";</script>'; exit();
    }

    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT full_name, email, role FROM profiles WHERE id = $id"
    ));
    $del_name  = $row['full_name'] ?? "User #$id";
    $del_email = $row['email'] ?? '';
    $del_role  = $row['role'] ?? 'unknown';

    if (mysqli_query($conn, "DELETE FROM profiles WHERE id = $id")) {

        /* ⭐ LOG with full context */
        log_delete(
            $conn,
            'User Account',
            $del_name,
            $id,
            "Account removed • role: {$del_role} • email: {$del_email}"
        );

        flash("User deleted successfully.", 'success', 'success');
    } else {
        flash("Error deleting user.", 'error', 'error');
    }
    echo '<script>window.location.href="?section=users";</script>'; exit();
}

/* =========================================================
   FETCH DATA
========================================================= */
$roles_arr = [];
$roles_q = mysqli_query($conn, "SELECT id, role_name FROM roles ORDER BY role_name");
if ($roles_q) { while ($r = mysqli_fetch_assoc($roles_q)) $roles_arr[] = $r; }

$users_res = mysqli_query($conn, "
    SELECT p.*, r.role_name
    FROM profiles p
    LEFT JOIN roles r ON r.role_name = p.role
    ORDER BY p.created_at DESC
");
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid py-4">

            <!-- HEADING -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                        <i class="fas fa-users text-primary"></i> Users
                    </h1>
                    <p class="text-muted small mb-0">Manage users and assign them to roles</p>
                </div>
                <?php if (has_permission($conn, 'users.create')): ?>
                    <button class="btn btn-primary shadow-sm font-weight-bold"
                            data-toggle="modal" data-target="#createUserModal">
                        <i class="fas fa-user-plus"></i> New User
                    </button>
                <?php endif; ?>
            </div>

            <!-- USERS TABLE -->
            <div class="card shadow mb-4" style="border-radius:14px; border:none;">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th width="140">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($users_res && mysqli_num_rows($users_res) > 0): ?>
                            <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($u['full_name'] ?: 'U', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                                <?php if (!empty($u['phone'])): ?>
                                                    <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($u['phone']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($u['email'] ?: '—'); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <i class="fas fa-user-tag"></i>
                                            <?php echo htmlspecialchars($u['role'] ?: 'None'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo !empty($u['last_login']) ? date('M d, Y g:i A', strtotime($u['last_login'])) : 'Never'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if (has_permission($conn, 'users.edit')): ?>
                                            <button class="btn btn-sm btn-warning"
                                                    onclick='openEditUser(<?php echo json_encode([
                                                        "id"        => $u["id"],
                                                        "full_name" => $u["full_name"],
                                                        "email"     => $u["email"],
                                                        "phone"     => $u["phone"] ?? "",
                                                        "role"      => $u["role"],
                                                        "status"    => $u["status"] ?? "active"
                                                    ]); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (has_permission($conn, 'users.delete') && $u['id'] != current_user_id()): ?>
                                            <a href="javascript:void(0)" class="btn btn-sm btn-danger"
                                               onclick="confirmDeleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                    <h6 class="text-muted">No users yet</h6>
                                    <p class="text-muted small mb-0">Click "New User" to add your first user.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- =========================================================
     CREATE USER MODAL
========================================================= -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?section=users" id="createUserForm">
                <div class="modal-header" style="background:linear-gradient(135deg,#4e73df,#224abe); color:#fff; border:none;">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> New User</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Password <span class="text-danger">*</span></label>
                        <div class="password-input-wrap">
                            <input type="password" name="password" id="create_password"
                                   class="form-control" required minlength="6"
                                   placeholder="At least 6 characters">
                            <button type="button" class="toggle-pwd-btn"
                                    data-target="create_password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Confirm Password <span class="text-danger">*</span></label>
                        <div class="password-input-wrap">
                            <input type="password" name="confirm" id="create_confirm"
                                   class="form-control" required minlength="6"
                                   placeholder="Repeat the password">
                            <button type="button" class="toggle-pwd-btn"
                                    data-target="create_confirm" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="create_pwd_hint" class="form-text"></small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Role <span class="text-danger">*</span></label>
                        <select name="role_name" class="custom-select" required>
                            <option value="">Choose a role…</option>
                            <?php foreach ($roles_arr as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role_name']); ?>">
                                    <?php echo htmlspecialchars($r['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_user" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-save"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     EDIT USER MODAL
========================================================= -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?section=users" id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header" style="background:linear-gradient(135deg,#f6c23e,#dda20a); color:#fff; border:none;">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Full Name</label>
                        <input type="text" name="full_name" id="edit_user_full" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Email</label>
                        <input type="email" name="email" id="edit_user_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Phone</label>
                        <input type="text" name="phone" id="edit_user_phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">
                            New Password <small class="text-muted">(leave blank to keep current)</small>
                        </label>
                        <div class="password-input-wrap">
                            <input type="password" name="password" id="edit_password"
                                   class="form-control" minlength="6"
                                   placeholder="Leave blank to keep current">
                            <button type="button" class="toggle-pwd-btn"
                                    data-target="edit_password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Confirm New Password</label>
                        <div class="password-input-wrap">
                            <input type="password" name="confirm" id="edit_confirm"
                                   class="form-control" minlength="6"
                                   placeholder="Repeat the new password">
                            <button type="button" class="toggle-pwd-btn"
                                    data-target="edit_confirm" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small id="edit_pwd_hint" class="form-text"></small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Role <span class="text-danger">*</span></label>
                        <select name="role_name" id="edit_user_role" class="custom-select" required>
                            <?php foreach ($roles_arr as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role_name']); ?>">
                                    <?php echo htmlspecialchars($r['role_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Status <span class="text-danger">*</span></label>
                        <select name="status" id="edit_user_status" class="custom-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-warning font-weight-bold">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .user-avatar {
        width: 38px; height: 38px;
        border-radius: 10px;
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 12px;
    }
    .password-input-wrap { position: relative; }
    .password-input-wrap .form-control { padding-right: 46px; }
    .toggle-pwd-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #858796;
        cursor: pointer;
        padding: 6px 8px;
        font-size: 14px;
        transition: color 0.15s;
        z-index: 3;
    }
    .toggle-pwd-btn:hover { color: #4e73df; }
    .toggle-pwd-btn:focus { outline: none; }
</style>

<script>
/* ===== SHOW / HIDE PASSWORD ===== */
$(document).on('click', '.toggle-pwd-btn', function () {
    const target = $(this).data('target');
    const $input = $('#' + target);
    const $icon  = $(this).find('i');

    if ($input.attr('type') === 'password') {
        $input.attr('type', 'text');
        $icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        $input.attr('type', 'password');
        $icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

/* ===== LIVE MATCH — CREATE ===== */
$(document).on('input', '#create_password, #create_confirm', function () {
    const pwd = $('#create_password').val();
    const cnf = $('#create_confirm').val();
    const $hint = $('#create_pwd_hint');
    if (cnf === '') { $hint.html(''); return; }

    if (pwd === cnf) {
        $hint.html('<i class="fas fa-check-circle text-success"></i> Passwords match')
             .removeClass('text-danger').addClass('text-success');
    } else {
        $hint.html('<i class="fas fa-times-circle text-danger"></i> Passwords do not match')
             .removeClass('text-success').addClass('text-danger');
    }
});

/* ===== LIVE MATCH — EDIT ===== */
$(document).on('input', '#edit_password, #edit_confirm', function () {
    const pwd = $('#edit_password').val();
    const cnf = $('#edit_confirm').val();
    const $hint = $('#edit_pwd_hint');
    if (pwd === '' && cnf === '') { $hint.html(''); return; }

    if (pwd === cnf) {
        $hint.html('<i class="fas fa-check-circle text-success"></i> Passwords match')
             .removeClass('text-danger').addClass('text-success');
    } else {
        $hint.html('<i class="fas fa-times-circle text-danger"></i> Passwords do not match')
             .removeClass('text-success').addClass('text-danger');
    }
});

/* ===== SUBMIT VALIDATION — CREATE ===== */
$(document).on('submit', '#createUserForm', function (e) {
    const pwd = $('#create_password').val();
    const cnf = $('#create_confirm').val();

    if (pwd !== cnf) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Passwords do not match',
            text: 'Please make sure both password fields match.',
            confirmButtonColor: '#e74a3b' });
        return false;
    }
    if (pwd.length < 6) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Password too short',
            text: 'Password must be at least 6 characters.',
            confirmButtonColor: '#e74a3b' });
        return false;
    }
});

/* ===== SUBMIT VALIDATION — EDIT ===== */
$(document).on('submit', '#editUserForm', function (e) {
    const pwd = $('#edit_password').val();
    const cnf = $('#edit_confirm').val();
    if (pwd === '' && cnf === '') return true;

    if (pwd !== cnf) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Passwords do not match',
            text: 'Please make sure both password fields match.',
            confirmButtonColor: '#e74a3b' });
        return false;
    }
    if (pwd.length < 6) {
        e.preventDefault();
        Swal.fire({ icon: 'error', title: 'Password too short',
            text: 'Password must be at least 6 characters.',
            confirmButtonColor: '#e74a3b' });
        return false;
    }
});

/* ===== OPEN EDIT MODAL ===== */
function openEditUser(u) {
    $('#edit_user_id').val(u.id);
    $('#edit_user_full').val(u.full_name);
    $('#edit_user_email').val(u.email);
    $('#edit_user_phone').val(u.phone);
    $('#edit_user_role').val(u.role);
    $('#edit_user_status').val(u.status);

    $('#edit_password').val('').attr('type', 'password');
    $('#edit_confirm').val('').attr('type', 'password');
    $('#edit_pwd_hint').html('');
    $('#editUserModal .toggle-pwd-btn i').removeClass('fa-eye-slash').addClass('fa-eye');

    $('#editUserModal').modal('show');
}

/* ===== CONFIRM DELETE ===== */
function confirmDeleteUser(id, fullName) {
    Swal.fire({
        title: 'Delete user?',
        html: `You are about to delete <strong>${fullName}</strong><br><small class="text-danger">This cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: '<i class="fas fa-trash"></i> Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(r => {
        if (r.isConfirmed) window.location.href = '?section=users&delete_user=' + id;
    });
}

/* ===== RESET CREATE FORM WHEN MODAL CLOSES ===== */
$('#createUserModal').on('hidden.bs.modal', function () {
    $('#createUserForm')[0].reset();
    $('#create_pwd_hint').html('');
    $('#createUserModal .toggle-pwd-btn i').removeClass('fa-eye-slash').addClass('fa-eye');
    $('#create_password, #create_confirm').attr('type', 'password');
});
</script>