<?php
require_once 'config.php';
require_once 'functions.php';

require_permission($conn, 'roles.view');

/* =========================================================
   CREATE ROLE
========================================================= */
if (isset($_POST['create_role'])) {
    require_permission($conn, 'roles.create');

    $name     = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $desc     = mysqli_real_escape_string($conn, trim($_POST['description']));
    $perm_ids = $_POST['permissions'] ?? [];

    if ($name === '') {
        flash("Role name is required.", 'error', 'error');
        echo '<script>window.location.href="?section=roles";</script>'; exit();
    }

    $chk = mysqli_query($conn, "SELECT id FROM roles WHERE role_name = '$name'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        flash("A role named '$name' already exists.", 'error', 'error');
        echo '<script>window.location.href="?section=roles";</script>'; exit();
    }

    if (mysqli_query($conn, "INSERT INTO roles (role_name, description) VALUES ('$name', '$desc')")) {
        $role_id = mysqli_insert_id($conn);

        /* Insert permissions, track which were actually added */
        $added_keys = [];
        foreach ($perm_ids as $pid) {
            $pid = (int)$pid;
            if (mysqli_query($conn, "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($role_id, $pid)")) {
                $pr = mysqli_query($conn, "SELECT permission_key FROM permissions WHERE id = $pid LIMIT 1");
                if ($pr && $row = mysqli_fetch_assoc($pr)) {
                    $added_keys[] = $row['permission_key'];
                }
            }
        }

        /* Rich log */
        $detail = "New role '{$name}' created";
        if ($desc !== '') $detail .= " • \"{$desc}\"";
        $detail .= " • " . count($added_keys) . " permission(s) granted";
        if (count($added_keys) > 0 && count($added_keys) <= 6) {
            $detail .= ": " . implode(', ', $added_keys);
        } elseif (count($added_keys) > 6) {
            $detail .= ": " . implode(', ', array_slice($added_keys, 0, 5)) . ", +" . (count($added_keys) - 5) . " more";
        }

        log_system($conn, 'Created', 'Role', $detail);
        flash("Role '$name' created successfully!", 'success', 'success');
    } else {
        flash("Error creating role: " . mysqli_error($conn), 'error', 'error');
    }
    echo '<script>window.location.href="?section=roles";</script>'; exit();
}

/* =========================================================
   UPDATE ROLE
========================================================= */
if (isset($_POST['update_role'])) {
    require_permission($conn, 'roles.edit');

    $id       = (int)$_POST['role_id'];
    $name     = mysqli_real_escape_string($conn, trim($_POST['role_name']));
    $desc     = mysqli_real_escape_string($conn, trim($_POST['description']));
    $perm_ids = $_POST['permissions'] ?? [];

    /* Fetch OLD values */
    $old = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT role_name, description, is_system FROM roles WHERE id = $id"
    ));
    if (!$old) {
        flash("Role not found.", 'error', 'error');
        echo '<script>window.location.href="?section=roles";</script>'; exit();
    }

    if ($old['is_system'] && $old['role_name'] !== $name) {
        flash("The system role's name cannot be changed.", 'error', 'error');
        echo '<script>window.location.href="?section=roles";</script>'; exit();
    }

    /* Fetch OLD permission keys BEFORE wipe */
    $old_perms = [];
    $op_res = mysqli_query($conn, "
        SELECT p.permission_key
        FROM role_permissions rp
        JOIN permissions p ON p.id = rp.permission_id
        WHERE rp.role_id = $id
    ");
    if ($op_res) { while ($r = mysqli_fetch_assoc($op_res)) $old_perms[] = $r['permission_key']; }

    $old_name = $old['role_name'];

    /* If renaming, propagate to profiles.role */
    if ($old_name !== $name) {
        $old_esc = mysqli_real_escape_string($conn, $old_name);
        mysqli_query($conn, "UPDATE profiles SET role = '$name' WHERE role = '$old_esc'");
    }

    mysqli_query($conn, "UPDATE roles SET role_name = '$name', description = '$desc' WHERE id = $id");

    /* Reset permissions */
    mysqli_query($conn, "DELETE FROM role_permissions WHERE role_id = $id");

    $new_perms = [];
    foreach ($perm_ids as $pid) {
        $pid = (int)$pid;
        if (mysqli_query($conn, "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES ($id, $pid)")) {
            $pr = mysqli_query($conn, "SELECT permission_key FROM permissions WHERE id = $pid LIMIT 1");
            if ($pr && $row = mysqli_fetch_assoc($pr)) {
                $new_perms[] = $row['permission_key'];
            }
        }
    }

    /* Build change list */
    $changes = [];

    if ($old_name !== trim($_POST['role_name'])) {
        $changes[] = "renamed '{$old_name}' → '{$name}'";
    }
    if (($old['description'] ?? '') !== trim($_POST['description'])) {
        $changes[] = "description updated";
    }

    $added   = array_diff($new_perms, $old_perms);
    $removed = array_diff($old_perms, $new_perms);

    if (!empty($added)) {
        $preview = count($added) <= 4
            ? implode(', ', $added)
            : implode(', ', array_slice($added, 0, 3)) . ", +" . (count($added) - 3) . " more";
        $changes[] = "+" . count($added) . " permission(s): " . $preview;
    }
    if (!empty($removed)) {
        $preview = count($removed) <= 4
            ? implode(', ', $removed)
            : implode(', ', array_slice($removed, 0, 3)) . ", +" . (count($removed) - 3) . " more";
        $changes[] = "-" . count($removed) . " permission(s): " . $preview;
    }

    $details = empty($changes)
        ? "Role '{$name}' saved (no changes)"
        : "Role '{$name}' — " . implode(' • ', $changes);

    log_system($conn, 'Updated', 'Role', $details);

    /* Success toast */
    if (empty($changes)) {
        flash("Role saved (no changes made).", 'success', 'success');
    } else {
        flash("Role updated successfully!", 'success', 'success');
    }
    echo '<script>window.location.href="?section=roles";</script>'; exit();
}

/* =========================================================
   DELETE ROLE
========================================================= */
if (isset($_GET['delete_role'])) {
    require_permission($conn, 'roles.delete');
    $id = (int)$_GET['delete_role'];

    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_name, is_system FROM roles WHERE id = $id"));
    if (!$row) {
        flash("Role not found.", 'error', 'error');
        echo '<script>window.location.href="?section=roles";</script>'; exit();
    }

    if ($row['is_system']) {
        flash("System roles cannot be deleted.", 'error', 'error');
        echo '<script>window.location.href="?section=roles";</script>'; exit();
    }

    $old_name = $row['role_name'];

    /* Count permissions */
    $perm_count = 0;
    $pc_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM role_permissions WHERE role_id = $id");
    if ($pc_res) $perm_count = (int)mysqli_fetch_assoc($pc_res)['c'];

    /* Count affected users */
    $old_esc = mysqli_real_escape_string($conn, $old_name);
    $affected = 0;
    $af_res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM profiles WHERE role = '$old_esc'");
    if ($af_res) $affected = (int)mysqli_fetch_assoc($af_res)['c'];

    /* Reassign to Viewer */
    $viewer_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_name FROM roles WHERE role_name = 'Viewer' LIMIT 1"));
    $viewer_name = $viewer_row['role_name'] ?? null;

    if ($viewer_name && $viewer_name !== $old_name) {
        $viewer_esc = mysqli_real_escape_string($conn, $viewer_name);
        mysqli_query($conn, "UPDATE profiles SET role = '$viewer_esc' WHERE role = '$old_esc'");
    }

    mysqli_query($conn, "DELETE FROM role_permissions WHERE role_id = $id");

    if (mysqli_query($conn, "DELETE FROM roles WHERE id = $id")) {
        $detail = "Role '{$old_name}' deleted";
        if ($perm_count > 0)  $detail .= " • {$perm_count} permission(s) removed";
        if ($affected > 0)    $detail .= " • {$affected} user(s) reassigned to " . ($viewer_name ?: 'no role');

        log_system($conn, 'Deleted', 'Role', $detail);

        flash("Role '{$old_name}' deleted." . ($affected ? " {$affected} user(s) reassigned." : ''), 'success', 'success');
    } else {
        flash("Error deleting role: " . mysqli_error($conn), 'error', 'error');
    }
    echo '<script>window.location.href="?section=roles";</script>'; exit();
}

/* =========================================================
   FETCH DATA
========================================================= */

/* Roles + counts (users counted via profiles.role = roles.role_name) */
$roles_res = mysqli_query($conn, "
    SELECT r.*,
           (SELECT COUNT(*) FROM profiles p WHERE p.role = r.role_name)        AS user_count,
           (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.id)  AS perm_count
    FROM roles r
    ORDER BY r.is_system DESC, r.role_name ASC
");

if (!$roles_res) {
    die("Query failed: " . mysqli_error($conn));
}

$perms_res = mysqli_query($conn, "SELECT * FROM permissions ORDER BY permission_group, label");
$all_perms = [];
if ($perms_res) {
    while ($p = mysqli_fetch_assoc($perms_res)) {
        $all_perms[$p['permission_group']][] = $p;
    }
}

/* Pre-load each role's permission keys */
$role_perms_map = [];
$rp_res = mysqli_query($conn, "
    SELECT rp.role_id, p.permission_key
    FROM role_permissions rp
    JOIN permissions p ON p.id = rp.permission_id
");
if ($rp_res) {
    while ($row = mysqli_fetch_assoc($rp_res)) {
        $role_perms_map[$row['role_id']][] = $row['permission_key'];
    }
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid py-4">

            <!-- ===== HEADING ===== -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                        <i class="fas fa-user-shield text-primary"></i> Roles &amp; Permissions
                    </h1>
                    <p class="text-muted small mb-0">Manage roles and control what each role can see and do</p>
                </div>
                <div>
                    <?php if (has_permission($conn, 'roles.create')): ?>
                        <button class="btn btn-primary shadow-sm font-weight-bold"
                                data-toggle="modal" data-target="#createRoleModal">
                            <i class="fas fa-plus"></i> New Role
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== ROLES GRID ===== -->
            <div class="row">
                <?php while ($role = mysqli_fetch_assoc($roles_res)):
                    $role_perms = $role_perms_map[$role['id']] ?? [];
                ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card role-card h-100">
                            <div class="card-header role-header <?php echo $role['is_system'] ? 'system' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="m-0 font-weight-bold">
                                            <i class="fas <?php echo $role['is_system'] ? 'fa-crown' : 'fa-user-tag'; ?>"></i>
                                            <?php echo htmlspecialchars($role['role_name']); ?>
                                            <?php if ($role['is_system']): ?>
                                                <span class="badge badge-light ml-2">System</span>
                                            <?php endif; ?>
                                        </h5>
                                        <small class="opacity-75"><?php echo htmlspecialchars($role['description']); ?></small>
                                    </div>
                                    <div class="text-right">
                                        <div class="role-stat">
                                            <i class="fas fa-users"></i> <?php echo $role['user_count']; ?>
                                        </div>
                                        <div class="role-stat">
                                            <i class="fas fa-key"></i> <?php echo $role['perm_count']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-3">

                                <?php if (!empty($role_perms)): ?>
                                    <div class="perm-pills">
                                        <?php foreach ($role_perms as $pk): ?>
                                            <span class="perm-pill">
                                                <i class="fas fa-check-circle"></i>
                                                <?php echo htmlspecialchars($pk); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small mb-0"><em>No permissions assigned.</em></p>
                                <?php endif; ?>

                            </div>
                            <div class="card-footer bg-white d-flex justify-content-end">
                                <?php if (has_permission($conn, 'roles.edit')): ?>
                                    <button class="btn btn-sm btn-warning mr-2"
                                            onclick='openEditRole(<?php echo json_encode([
                                                "id"        => $role["id"],
                                                "name"      => $role["role_name"],
                                                "desc"      => $role["description"],
                                                "perms"     => $role_perms,
                                                "is_system" => (bool)$role["is_system"]
                                            ]); ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                <?php endif; ?>
                                <?php if (has_permission($conn, 'roles.delete') && !$role['is_system']): ?>
                                    <button class="btn btn-sm btn-danger"
                                            onclick="confirmDeleteRole(<?php echo $role['id']; ?>, '<?php echo htmlspecialchars($role['role_name'], ENT_QUOTES); ?>', <?php echo $role['user_count']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        </div>
    </div>
</div>

<!-- =========================================================
     CREATE ROLE MODAL
========================================================= -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="?section=roles" id="createRoleForm">
                <div class="modal-header role-modal-create">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-plus-circle"></i> Create New Role
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="role_name" class="form-control"
                               placeholder="e.g., Content Manager" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <input type="text" name="description" class="form-control"
                               placeholder="What can this role do?">
                    </div>

                    <hr>
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-key"></i> Permissions
                    </label>

                    <?php foreach ($all_perms as $group => $perms): ?>
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input group-toggle"
                                           id="group_create_<?php echo md5($group); ?>"
                                           data-target=".group-create-<?php echo md5($group); ?>">
                                    <label class="custom-control-label font-weight-bold" for="group_create_<?php echo md5($group); ?>">
                                        <?php echo htmlspecialchars($group); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="perm-group-body">
                                <?php foreach ($perms as $p): ?>
                                    <div class="custom-control custom-checkbox perm-check">
                                        <input type="checkbox" class="custom-control-input group-create-<?php echo md5($group); ?>"
                                               id="create_perm_<?php echo $p['id']; ?>"
                                               name="permissions[]" value="<?php echo $p['id']; ?>">
                                        <label class="custom-control-label" for="create_perm_<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['label']); ?>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($p['permission_key']); ?></small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_role" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-save"></i> Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     EDIT ROLE MODAL
========================================================= -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="?section=roles" id="editRoleForm">
                <input type="hidden" name="role_id" id="edit_role_id">
                <div class="modal-header role-modal-edit">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-edit"></i> Edit Role
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                        <small class="text-muted" id="system_name_hint" style="display:none;">
                            <i class="fas fa-lock"></i> System role — name cannot be changed
                        </small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Description</label>
                        <input type="text" name="description" id="edit_role_desc" class="form-control">
                    </div>

                    <hr>
                    <label class="font-weight-bold mb-3">
                        <i class="fas fa-key"></i> Permissions
                    </label>

                    <?php foreach ($all_perms as $group => $perms): ?>
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input group-toggle"
                                           id="group_edit_<?php echo md5($group); ?>"
                                           data-target=".group-edit-<?php echo md5($group); ?>">
                                    <label class="custom-control-label font-weight-bold" for="group_edit_<?php echo md5($group); ?>">
                                        <?php echo htmlspecialchars($group); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="perm-group-body">
                                <?php foreach ($perms as $p): ?>
                                    <div class="custom-control custom-checkbox perm-check">
                                        <input type="checkbox" class="custom-control-input group-edit-<?php echo md5($group); ?>"
                                               id="edit_perm_<?php echo $p['id']; ?>"
                                               name="permissions[]" value="<?php echo $p['id']; ?>"
                                               data-key="<?php echo $p['permission_key']; ?>">
                                        <label class="custom-control-label" for="edit_perm_<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['label']); ?>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($p['permission_key']); ?></small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_role" class="btn btn-warning font-weight-bold">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .role-card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,.06);
        transition: transform .25s ease, box-shadow .25s ease;
    }
    .role-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(0,0,0,.12);
    }
    .role-header {
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
        padding: 16px 22px;
        border: none;
    }
    .role-header.system {
        background: linear-gradient(135deg, #f6c23e, #dda20a);
    }
    .role-stat {
        font-size: 12px;
        opacity: .9;
        font-weight: 600;
    }
    .role-stat i { margin-right: 4px; }

    .perm-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .perm-pill {
        background: #eef0f5;
        color: #4e73df;
        font-size: 10px;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 6px;
        letter-spacing: .3px;
    }
    .perm-pill i { color: #1cc88a; margin-right: 3px; }

    .role-modal-create { background: linear-gradient(135deg, #4e73df, #224abe); border:none; }
    .role-modal-edit   { background: linear-gradient(135deg, #f6c23e, #dda20a); border:none; }

    .perm-group {
        background: #f8f9fc;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
        border: 1px solid #e3e6f0;
    }
    .perm-group-header {
        background: #eaecf4;
        padding: 10px 16px;
        margin: 0;
    }
    .perm-group-body {
        padding: 12px 16px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .perm-check { padding-left: 1.5rem; }
    .perm-check .custom-control-label { font-size: 13px; }
    @media (max-width: 575px) {
        .perm-group-body { grid-template-columns: 1fr; }
    }
</style>

<script>
/* ===== GROUP TOGGLE ===== */
$(document).on('change', '.group-toggle', function () {
    const target = $(this).data('target');
    $(target).prop('checked', $(this).is(':checked'));
});

/* ===== OPEN EDIT ROLE ===== */
function openEditRole(role) {
    $('#edit_role_id').val(role.id);
    $('#edit_role_name').val(role.name);
    $('#edit_role_desc').val(role.desc);

    if (role.is_system) {
        $('#edit_role_name').prop('readonly', true);
        $('#system_name_hint').show();
    } else {
        $('#edit_role_name').prop('readonly', false);
        $('#system_name_hint').hide();
    }

    $('#editRoleForm input[name="permissions[]"]').prop('checked', false);
    role.perms.forEach(function (key) {
        $('#editRoleForm input[data-key="' + key + '"]').prop('checked', true);
    });

    $('#editRoleModal').modal('show');
}

/* ===== CONFIRM DELETE ===== */
function confirmDeleteRole(id, name, userCount) {
    let extra = userCount > 0
        ? `<br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${userCount} user(s) will be reassigned to Viewer</small>`
        : '';

    Swal.fire({
        title: 'Delete role?',
        html: `You are about to delete <strong>${name}</strong>${extra}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) {
            window.location.href = '?section=roles&delete_role=' + id;
        }
    });
}

/* ===== RESET CREATE FORM WHEN MODAL CLOSES ===== */
$('#createRoleModal').on('hidden.bs.modal', function () {
    $('#createRoleForm')[0].reset();
    $('.group-toggle').prop('checked', false);
});
</script>