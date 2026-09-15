<?php
//session_start();
include 'config.php';

// Make sure uploads folder exists
if (!is_dir('uploads/avatars')) {
    mkdir('uploads/avatars', 0777, true);
}

// ============ CREATE ============
if (isset($_POST['create_profile'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $bio       = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $role      = mysqli_real_escape_string($conn, $_POST['role']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);

    $avatar_name = '';
    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);

        if (isset($allowed[$mime]) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
            $avatar_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
            move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/avatars/' . $avatar_name);
        } else {
            $_SESSION['message']      = "Invalid image or file too large (max 2MB).";
            $_SESSION['message_type'] = "error";
            $_SESSION['message_icon'] = "error";
            header("Location: ?section=profile");
            exit();
        }
    }

    $sql = "INSERT INTO profiles (full_name, email, phone, bio, avatar, role, status)
            VALUES ('$full_name', '$email', '$phone', '$bio', '$avatar_name', '$role', '$status')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message']      = "Profile created successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error creating profile: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
   // header("Location: ?section=profile");
    echo '<script>window.location.href = "?section=profile";</script>';
    exit();
}

// ============ UPDATE ============
if (isset($_POST['update_profile'])) {
    $id        = (int) $_POST['profile_id'];
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $bio       = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $role      = mysqli_real_escape_string($conn, $_POST['role']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);

    // Get current avatar
    $r = mysqli_query($conn, "SELECT avatar FROM profiles WHERE id = $id");
    $current = $r ? mysqli_fetch_assoc($r) : null;
    $avatar_name = $current['avatar'] ?? '';

    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $finfo   = finfo_open(FILEINFO_MIME_TYPE);
        $mime    = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);

        if (isset($allowed[$mime]) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
            if ($avatar_name && file_exists('uploads/avatars/' . $avatar_name)) {
                unlink('uploads/avatars/' . $avatar_name);
            }
            $avatar_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
            move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/avatars/' . $avatar_name);
        }
    }

    $sql = "UPDATE profiles SET
                full_name = '$full_name',
                email     = '$email',
                phone     = '$phone',
                bio       = '$bio',
                avatar    = '$avatar_name',
                role      = '$role',
                status    = '$status'
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['message']      = "Profile updated successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error updating profile: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    //header("Location: ?section=profile");
    echo '<script>window.location.href = "?section=profile";</script>';
    exit();
}

// ============ DELETE ============
if (isset($_POST['delete_profile'])) {
    $id = (int) $_POST['profile_id'];

    $r = mysqli_query($conn, "SELECT avatar FROM profiles WHERE id = $id");
    $row = $r ? mysqli_fetch_assoc($r) : null;
    if ($row && $row['avatar'] && file_exists('uploads/avatars/' . $row['avatar'])) {
        unlink('uploads/avatars/' . $row['avatar']);
    }

    if (mysqli_query($conn, "DELETE FROM profiles WHERE id = $id")) {
        $_SESSION['message']      = "Profile deleted successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error deleting profile: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    //header("Location: ?section=profile");
    echo '<script>window.location.href = "?section=profile";</script>';
    exit();
}

// ============ FETCH FOR EDIT ============
$edit_profile = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $r = mysqli_query($conn, "SELECT * FROM profiles WHERE id = $id");
    if ($r && mysqli_num_rows($r) > 0) {
        $edit_profile = mysqli_fetch_assoc($r);
    }
}

// ============ FETCH ALL ============
$result      = mysqli_query($conn, "SELECT * FROM profiles ORDER BY created_at DESC");
$total_profiles = $result ? mysqli_num_rows($result) : 0;

// ============ SESSION FLASH ============
$message      = $_SESSION['message']      ?? '';
$message_type = $_SESSION['message_type'] ?? '';
$message_icon = $_SESSION['message_icon'] ?? '';
if ($message !== '') {
    unset($_SESSION['message'], $_SESSION['message_type'], $_SESSION['message_icon']);
}
?>

<style>
    .avatar-thumb {
        width: 45px; height: 45px;
        object-fit: cover; border-radius: 50%;
        border: 2px solid #e3e6f0;
    }
    .avatar-preview {
        width: 120px; height: 120px;
        object-fit: cover; border-radius: 50%;
        border: 3px solid #e3e6f0;
        display: block; margin: 0 auto;
    }
    .avatar-placeholder {
        width: 120px; height: 120px;
        border-radius: 50%; background: #eaecf4;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto; color: #858796; font-size: 40px;
    }
    .profile-badge-active   { background: #1cc88a; color: #fff; }
    .profile-badge-inactive { background: #858796; color: #fff; }
    .edit-mode-badge {
        background: #f6c23e; color: #fff;
        padding: 5px 15px; border-radius: 20px; font-size: 14px;
    }
</style>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid">

            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Profile Management</h1>
                <div>
                    <?php if ($edit_profile): ?>
                        <span class="edit-mode-badge mr-2">
                            <i class="fas fa-edit"></i> Editing: <?php echo htmlspecialchars($edit_profile['full_name']); ?>
                        </span>
                        <a href="?section=profile" class="btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-times"></i> Cancel Edit
                        </a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-primary shadow-sm"
                                data-toggle="modal" data-target="#createProfileModal">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Profile
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== EDIT FORM ===== -->
            <?php if ($edit_profile): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3" style="background: linear-gradient(135deg,#f6c23e,#dda20a);">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </h6>
                </div>
                <div class="card-body">
                    <form action="?section=profile" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="profile_id" value="<?php echo $edit_profile['id']; ?>">

                        <div class="row">
                            <div class="col-lg-4 text-center">
                                <?php if ($edit_profile['avatar'] && file_exists('uploads/avatars/' . $edit_profile['avatar'])): ?>
                                    <img src="uploads/avatars/<?php echo htmlspecialchars($edit_profile['avatar']); ?>"
                                         class="avatar-preview mb-3" id="editAvatarPreview">
                                <?php else: ?>
                                    <div class="avatar-placeholder mb-3" id="editAvatarPreview">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="custom-file">
                                    <input type="file" name="avatar" class="custom-file-input"
                                           id="editAvatarInput" accept="image/*">
                                    <label class="custom-file-label" for="editAvatarInput">
                                        <i class="fas fa-cloud-upload-alt"></i> Change avatar...
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">JPG, PNG, GIF, WEBP · Max 2MB</small>
                            </div>

                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Full Name <span class="text-danger">*</span></label>
                                            <input type="text" name="full_name" class="form-control"
                                                   value="<?php echo htmlspecialchars($edit_profile['full_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email" class="form-control"
                                                   value="<?php echo htmlspecialchars($edit_profile['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="text" name="phone" class="form-control"
                                                   value="<?php echo htmlspecialchars($edit_profile['phone']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Role</label>
                                            <select name="role" class="form-control">
                                                <?php foreach (['user','editor','admin','manager'] as $r): ?>
                                                    <option value="<?php echo $r; ?>"
                                                        <?php echo $edit_profile['role'] === $r ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($r); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                <option value="active"   <?php echo $edit_profile['status'] === 'active'   ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $edit_profile['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Bio</label>
                                            <textarea name="bio" rows="3" class="form-control"
                                                      placeholder="Short bio..."><?php echo htmlspecialchars($edit_profile['bio']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <button type="submit" name="update_profile" class="btn btn-warning btn-lg">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <a href="?section=profile" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== PROFILES TABLE ===== -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">All Profiles</h6>
                    <span class="badge badge-primary"><?php echo $total_profiles; ?> Profiles</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="10%">Avatar</th>
                                    <th width="20%">Name</th>
                                    <th width="20%">Email</th>
                                    <th width="12%">Phone</th>
                                    <th width="10%">Role</th>
                                    <th width="10%">Status</th>
                                    <th width="13%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_profiles > 0): ?>
                                    <?php $i = 1; while ($p = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td>
                                                <?php if ($p['avatar'] && file_exists('uploads/avatars/' . $p['avatar'])): ?>
                                                    <img src="uploads/avatars/<?php echo htmlspecialchars($p['avatar']); ?>"
                                                         class="avatar-thumb">
                                                <?php else: ?>
                                                    <div class="avatar-thumb d-flex align-items-center justify-content-center bg-light">
                                                        <i class="fas fa-user text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($p['full_name']); ?></strong><br>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar-alt"></i>
                                                    <?php echo date('M d, Y', strtotime($p['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td><i class="fas fa-envelope text-primary"></i> <?php echo htmlspecialchars($p['email']); ?></td>
                                            <td><?php echo $p['phone'] ? htmlspecialchars($p['phone']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($p['role'])); ?></span></td>
                                            <td>
                                                <span class="badge profile-badge-<?php echo $p['status']; ?>">
                                                    <?php echo ucfirst($p['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?section=profile&edit=<?php echo $p['id']; ?>"
                                                   class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-info" title="View"
                                                        data-toggle="modal" data-target="#viewProfileModal<?php echo $p['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                    <form method="POST" action="?section=profile" style="display:inline"
                                                    onsubmit="return confirmDelete(event, '<?php echo htmlspecialchars(addslashes($p['full_name'])); ?>')">
                                                    <input type="hidden" name="profile_id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="delete_profile" value="1">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                                            <h6>No profiles yet</h6>
                                            <p class="text-muted">Click "Add New Profile" to create the first one.</p>
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
</div>

<!-- ===== CREATE MODAL ===== -->
<div class="modal fade" id="createProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#4e73df,#224abe);">
                <h5 class="modal-title text-white"><i class="fas fa-user-plus"></i> Create New Profile</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="?section=profile" method="POST" enctype="multipart/form-data" id="createProfileForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-4 text-center">
                            <div class="avatar-placeholder mb-3" id="createAvatarPreview">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="custom-file">
                                <input type="file" name="avatar" class="custom-file-input"
                                       id="createAvatarInput" accept="image/*">
                                <label class="custom-file-label" for="createAvatarInput">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose avatar...
                                </label>
                            </div>
                            <small class="text-muted d-block mt-2">Max 2MB</small>
                        </div>
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" class="form-control">
                                            <option value="user">User</option>
                                            <option value="editor">Editor</option>
                                            <option value="admin">Admin</option>
                                            <option value="manager">Manager</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Bio</label>
                                        <textarea name="bio" rows="3" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== VIEW MODALS ===== -->
<?php
if ($result && $total_profiles > 0) {
    mysqli_data_seek($result, 0);
    while ($p = mysqli_fetch_assoc($result)):
?>
<div class="modal fade" id="viewProfileModal<?php echo $p['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg,#667eea,#764ba2);">
                <h5 class="modal-title text-white"><?php echo htmlspecialchars($p['full_name']); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-3">
                        <?php if ($p['avatar'] && file_exists('uploads/avatars/' . $p['avatar'])): ?>
                            <img src="uploads/avatars/<?php echo htmlspecialchars($p['avatar']); ?>"
                                 class="avatar-preview">
                        <?php else: ?>
                            <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <table class="table table-sm">
                            <tr><th width="35%">Email</th><td><?php echo htmlspecialchars($p['email']); ?></td></tr>
                            <tr><th>Phone</th><td><?php echo $p['phone'] ? htmlspecialchars($p['phone']) : '-'; ?></td></tr>
                            <tr><th>Role</th><td><span class="badge badge-info"><?php echo ucfirst($p['role']); ?></span></td></tr>
                            <tr><th>Status</th><td><span class="badge profile-badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td></tr>
                            <tr><th>Joined</th><td><?php echo date('F d, Y', strtotime($p['created_at'])); ?></td></tr>
                        </table>
                        <?php if ($p['bio']): ?>
                            <strong><i class="fas fa-quote-left"></i> Bio</strong>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($p['bio'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endwhile; } ?>

<script>
$(document).ready(function() {
    // DataTable — with reinit guard
    if (!$.fn.dataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable({
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            language: {
                search: "Search profiles:",
                lengthMenu: "Show _MENU_ profiles per page",
                info: "Showing _START_ to _END_ of _TOTAL_ profiles",
                zeroRecords: "No matching profiles found"
            },
            columnDefs: [
                { orderable: false, targets: [1, 7] },
                { searchable: false, targets: [1, 7] }
            ]
        });
    }

    // Create avatar preview
    $('#createAvatarInput').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2000000) {
            Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Max 2MB.' });
            $(this).val('');
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            $('#createAvatarPreview').html(
                `<img src="${e.target.result}" class="avatar-preview">`
            );
            $(this).next('.custom-file-label').html('<i class="fas fa-check-circle text-success"></i> ' + file.name);
        };
        reader.readAsDataURL(file);
    });

    // Edit avatar preview
    $('#editAvatarInput').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 2000000) {
            Swal.fire({ icon: 'error', title: 'File Too Large', text: 'Max 2MB.' });
            $(this).val('');
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            $('#editAvatarPreview').replaceWith(
                `<img src="${e.target.result}" class="avatar-preview mb-3" id="editAvatarPreview">`
            );
            $(this).next('.custom-file-label').html('<i class="fas fa-check-circle text-success"></i> ' + file.name);
        };
        reader.readAsDataURL(file);
    });

    // Reset create form
    $('#createProfileModal').on('hidden.bs.modal', function() {
        $('#createProfileForm')[0].reset();
        $('#createAvatarPreview').html('<i class="fas fa-user"></i>');
        $('#createAvatarInput').next('.custom-file-label').html('<i class="fas fa-cloud-upload-alt"></i> Choose avatar...');
    });

    // Flash message
    <?php if ($message !== ''): ?>
    Swal.fire({
        icon: <?php echo json_encode($message_icon); ?>,
        title: <?php echo json_encode($message_type === 'success' ? 'Success!' : 'Error!'); ?>,
        text: <?php echo json_encode($message); ?>,
        confirmButtonColor: <?php echo json_encode($message_type === 'success' ? '#1cc88a' : '#e74a3b'); ?>,
        timer: 3000,
        timerProgressBar: true,
        toast: true,
        position: 'top-end',
        showConfirmButton: false
    });
    <?php endif; ?>
});

// Delete confirmation
function confirmDelete(event, name) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: 'Delete profile?',
        html: `You are about to delete <strong>${name}</strong>.`,
        text: "This cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Yes, delete!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(result => {
        if (result.isConfirmed) form.submit();
    });
    return false;
}
</script>
<script src="sweetalert.js"></script>