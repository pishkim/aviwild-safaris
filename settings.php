<?php
include 'config.php';
require_once 'functions.php';   // ⭐ gives you log_system()

if (isset($_POST['update_company'])) { require_permission($conn, 'settings.edit'); }
if (isset($_GET['reset_company']))   { require_permission($conn, 'settings.edit'); }
/* =========================================================
   UPDATE COMPANY INFO
========================================================= */
if (isset($_POST['update_company'])) {
    $id          = mysqli_real_escape_string($conn, $_POST['id']);
    $company     = mysqli_real_escape_string($conn, $_POST['company_name']);
    $tagline     = mysqli_real_escape_string($conn, $_POST['tagline']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);
    $phone       = mysqli_real_escape_string($conn, $_POST['phone']);
    $address     = mysqli_real_escape_string($conn, $_POST['address']);
    $website     = mysqli_real_escape_string($conn, $_POST['website']);
    $about       = mysqli_real_escape_string($conn, $_POST['about']);
    $mission     = mysqli_real_escape_string($conn, $_POST['mission']);
    $vision      = mysqli_real_escape_string($conn, $_POST['vision']);

    // Get existing logo
    $res         = mysqli_query($conn, "SELECT logo FROM company_info WHERE id = $id");
    $row         = mysqli_fetch_assoc($res);
    $logo_name   = $row['logo'] ?? '';

    // Handle new logo upload
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp','svg'];
        $ext     = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed) && $_FILES['logo']['size'] <= 2 * 1024 * 1024) {
            if ($logo_name && file_exists("uploads/$logo_name")) {
                unlink("uploads/$logo_name");
            }
            $logo_name = time() . '_' . basename($_FILES['logo']['name']);
            move_uploaded_file($_FILES['logo']['tmp_name'], "uploads/$logo_name");
        } else {
            $_SESSION['message']      = "Invalid logo file (max 2MB, JPG/PNG/GIF/WEBP/SVG)";
            $_SESSION['message_type'] = "error";
            $_SESSION['message_icon'] = "error";
            echo '<script>window.location.href = "?section=settings";</script>';
            exit();
        }
    }

    $sql = "UPDATE company_info SET
                company_name = '$company',
                tagline      = '$tagline',
                email        = '$email',
                phone        = '$phone',
                address      = '$address',
                website      = '$website',
                about        = '$about',
                mission      = '$mission',
                vision       = '$vision',
                logo         = '$logo_name'
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {

        /* ⭐ SINGLE log call → activity_log (new table) */
        log_system(
            $conn,
            'Updated',
            'Company Profile',
            "Updated company info: $company (ID: $id)"
        );

        $_SESSION['message']      = "Company information updated successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error updating information: " . mysqli_error($conn);
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    echo '<script>window.location.href = "?section=settings";</script>';
    exit();
}

/* =========================================================
   DELETE / RESET COMPANY INFO
========================================================= */
if (isset($_GET['reset_company'])) {
    $id = mysqli_real_escape_string($conn, $_GET['reset_company']);

    // Grab company name BEFORE clearing (for a useful log message)
    $row_old  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_name, logo FROM company_info WHERE id = $id"));
    $old_name = $row_old['company_name'] ?? 'Unnamed Company';
    $old_logo = $row_old['logo'] ?? '';

    // Delete logo file
    if (!empty($old_logo) && file_exists("uploads/$old_logo")) {
        unlink("uploads/$old_logo");
    }

    $sql = "UPDATE company_info SET
                company_name = '',
                tagline      = '',
                email        = '',
                phone        = '',
                address      = '',
                website      = '',
                about        = '',
                mission      = '',
                vision       = '',
                logo         = ''
            WHERE id = $id";

    if (mysqli_query($conn, $sql)) {

        /* ⭐ SINGLE log call */
        log_system(
            $conn,
            'Deleted',
            'Company Profile',
            "Cleared all company info (was: $old_name, ID: $id)"
        );

        $_SESSION['message']      = "Company information has been cleared.";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error clearing information.";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    echo '<script>window.location.href = "?section=settings";</script>';
    exit();
}

/* =========================================================
   FETCH DATA
========================================================= */
$company = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company_info LIMIT 1"));

/* ⭐ Sidebar activity card now reads from the NEW table */
$logs = mysqli_query($conn, "
    SELECT * FROM activity_log
    ORDER BY created_at DESC
    LIMIT 30
");

// Session flash
$message      = $_SESSION['message']      ?? '';
$message_type = $_SESSION['message_type'] ?? '';
$message_icon = $_SESSION['message_icon'] ?? '';
if ($message) {
    unset($_SESSION['message'], $_SESSION['message_type'], $_SESSION['message_icon']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Settings</title>
<style>
    .info-card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,.06);
        transition: transform .25s ease, box-shadow .25s ease;
    }
    .info-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 28px rgba(0,0,0,.10);
    }
    .card-header-gradient {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: #fff;
        padding: 18px 22px;
        border: none;
    }
    .card-header-gradient-edit {
        background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        color: #fff;
    }
    .card-header-gradient-log {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        color: #fff;
    }
    .company-logo-display {
        width: 110px;
        height: 110px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,.15);
        background: #f8f9fc;
    }
    .logo-placeholder {
        width: 110px; height: 110px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 42px;
        margin: 0 auto;
        box-shadow: 0 4px 15px rgba(78,115,223,.35);
    }
    .info-row {
        display: flex;
        align-items: flex-start;
        padding: 12px 0;
        border-bottom: 1px dashed #e3e6f0;
    }
    .info-row:last-child { border-bottom: none; }
    .info-icon {
        width: 38px; height: 38px; flex: 0 0 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        margin-right: 14px;
        font-size: 15px;
    }
    .info-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #858796;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .info-value {
        color: #3a3b45;
        font-weight: 500;
        word-break: break-word;
    }
    .image-preview-container {
        border: 2px dashed #d1d3e2;
        border-radius: 10px;
        padding: 15px;
        background: #f8f9fc;
        min-height: 170px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .3s ease;
    }
    .image-preview-container img {
        max-width: 100%;
        max-height: 180px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,.1);
    }
    .image-preview-placeholder {
        text-align: center;
        color: #858796;
    }
    .image-preview-placeholder i {
        font-size: 50px;
        display: block;
        margin-bottom: 10px;
        color: #d1d3e2;
    }
    .log-timeline {
        position: relative;
        padding-left: 34px;
        max-height: 460px;
        overflow-y: auto;
    }
    .log-timeline::before {
        content: "";
        position: absolute;
        left: 12px; top: 4px; bottom: 4px;
        width: 2px;
        background: linear-gradient(to bottom, #1cc88a, #e3e6f0);
    }
    .log-item {
        position: relative;
        padding: 10px 0 14px 0;
        border-bottom: 1px dashed #eef0f5;
    }
    .log-item:last-child { border-bottom: none; }
    .log-dot {
        position: absolute;
        left: -28px; top: 14px;
        width: 14px; height: 14px;
        border-radius: 50%;
        background: #1cc88a;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #1cc88a;
    }
    .log-dot.update { background:#4e73df; box-shadow:0 0 0 2px #4e73df; }
    .log-dot.delete { background:#e74a3b; box-shadow:0 0 0 2px #e74a3b; }
    .log-dot.create { background:#1cc88a; box-shadow:0 0 0 2px #1cc88a; }
    .log-action-badge {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .5px;
        padding: 3px 8px;
        border-radius: 6px;
        text-transform: uppercase;
    }
    .badge-update { background:#e7efff; color:#4e73df; }
    .badge-delete { background:#fdeceb; color:#e74a3b; }
    .badge-create { background:#e3f9ee; color:#1cc88a; }
    .stat-badge {
        background: rgba(255,255,255,.2);
        color: #fff;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(.9); }
        to   { opacity: 1; transform: scale(1); }
    }
    .preview-animate { animation: fadeIn .3s ease; }
    .section-title {
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #858796;
        font-weight: 700;
        margin: 20px 0 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #e3e6f0;
    }
</style>
</head>
<body id="page-top">
<div id="wrapper">
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid py-4">

                <!-- ===== PAGE HEADING ===== -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h6 class="h3 mb-1 text-gray-800 font-weight-bold">
                            <i class="fas fa-cogs text-primary"></i> System Settings
                        </h6>
                        <p class="text-muted mb-0 small">Manage your tour company profile and monitor activity</p>
                    </div>
                </div>

                <div class="row">
                    <!-- =========================================
                         LEFT CARD: COMPANY INFO (DISPLAY)
                    ========================================== -->
                    <div class="col-lg-5 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header-gradient d-flex align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-building"></i> Company Profile
                                </h6>
                                <span class="stat-badge">
                                    <i class="fas fa-database"></i> Live
                                </span>
                            </div>
                            <div class="card-body p-4">

                                <!-- LOGO -->
                                <div class="text-center mb-4">
                                    <?php if (!empty($company['logo']) && file_exists("uploads/{$company['logo']}")): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($company['logo']); ?>"
                                             class="company-logo-display" alt="Company logo">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <i class="fas fa-plane-departure"></i>
                                        </div>
                                    <?php endif; ?>

                                    <h4 class="mt-3 mb-1 font-weight-bold text-gray-800">
                                        <?php echo $company && $company['company_name'] !== ''
                                            ? htmlspecialchars($company['company_name'])
                                            : '<span class="text-muted">Unnamed Company</span>'; ?>
                                    </h4>
                                    <p class="text-muted small mb-0">
                                        <?php echo $company && $company['tagline']
                                            ? htmlspecialchars($company['tagline'])
                                            : 'No tagline set'; ?>
                                    </p>
                                </div>

                                <!-- CONTACT INFO -->
                                <div class="section-title">
                                    <i class="fas fa-address-card text-primary"></i> Contact Details
                                </div>

                                <div class="info-row">
                                    <div class="info-icon" style="background:#4e73df;"><i class="fas fa-envelope"></i></div>
                                    <div>
                                        <div class="info-label">Email</div>
                                        <div class="info-value">
                                            <?php echo !empty($company['email'])
                                                ? '<a href="mailto:'.htmlspecialchars($company['email']).'">'.htmlspecialchars($company['email']).'</a>'
                                                : '<span class="text-muted">Not set</span>'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon" style="background:#1cc88a;"><i class="fas fa-phone"></i></div>
                                    <div>
                                        <div class="info-label">Phone</div>
                                        <div class="info-value">
                                            <?php echo !empty($company['phone'])
                                                ? htmlspecialchars($company['phone'])
                                                : '<span class="text-muted">Not set</span>'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon" style="background:#f6c23e;"><i class="fas fa-map-marker-alt"></i></div>
                                    <div>
                                        <div class="info-label">Address</div>
                                        <div class="info-value">
                                            <?php echo !empty($company['address'])
                                                ? nl2br(htmlspecialchars($company['address']))
                                                : '<span class="text-muted">Not set</span>'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-row">
                                    <div class="info-icon" style="background:#36b9cc;"><i class="fas fa-globe"></i></div>
                                    <div>
                                        <div class="info-label">Website</div>
                                        <div class="info-value">
                                            <?php echo !empty($company['website'])
                                                ? '<a href="'.htmlspecialchars($company['website']).'" target="_blank">'.htmlspecialchars($company['website']).'</a>'
                                                : '<span class="text-muted">Not set</span>'; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- ABOUT / MISSION / VISION -->
                                <div class="section-title">
                                    <i class="fas fa-info-circle text-primary"></i> About
                                </div>
                                <p class="text-muted small mb-3">
                                    <?php echo !empty($company['about'])
                                        ? nl2br(htmlspecialchars($company['about']))
                                        : '<em>No description set.</em>'; ?>
                                </p>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="p-3 rounded mb-2" style="background:#f0f4ff;">
                                            <div class="info-label text-primary">
                                                <i class="fas fa-bullseye"></i> Mission
                                            </div>
                                            <div class="small text-gray-800">
                                                <?php echo !empty($company['mission'])
                                                    ? htmlspecialchars($company['mission'])
                                                    : '<em class="text-muted">Not set</em>'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 rounded mb-2" style="background:#fff7e6;">
                                            <div class="info-label" style="color:#dda20a;">
                                                <i class="fas fa-eye"></i> Vision
                                            </div>
                                            <div class="small text-gray-800">
                                                <?php echo !empty($company['vision'])
                                                    ? htmlspecialchars($company['vision'])
                                                    : '<em class="text-muted">Not set</em>'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($company['updated_at'])): ?>
                                    <p class="text-muted small text-center mt-3 mb-0">
                                        <i class="far fa-clock"></i>
                                        Last updated: <?php echo date('M d, Y g:i A', strtotime($company['updated_at'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- =========================================
                         RIGHT CARD: EDIT FORM
                    ========================================== -->
                    <div class="col-lg-7 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header-gradient card-header-gradient-edit d-flex align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-edit"></i> Edit Company Information
                                </h6>
                                <button type="button" class="btn btn-sm btn-light text-danger font-weight-bold"
                                        onclick="confirmReset()">
                                    <i class="fas fa-trash-alt"></i> Clear All
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <form action="?section=settings" method="POST" enctype="multipart/form-data" id="editForm">
                                    <input type="hidden" name="id" value="<?php echo (int)($company['id'] ?? 1); ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Company Name <span class="text-danger">*</span></label>
                                                <input type="text" name="company_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Tagline</label>
                                                <input type="text" name="tagline" class="form-control"
                                                       value="<?php echo htmlspecialchars($company['tagline'] ?? ''); ?>"
                                                       placeholder="e.g., Explore the world with us">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold"><i class="fas fa-envelope text-primary"></i> Email</label>
                                                <input type="email" name="email" class="form-control"
                                                       value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold"><i class="fas fa-phone text-success"></i> Phone</label>
                                                <input type="text" name="phone" class="form-control"
                                                       value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold"><i class="fas fa-globe text-info"></i> Website</label>
                                                <input type="text" name="website" class="form-control"
                                                       value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>"
                                                       placeholder="https://example.com">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold"><i class="fas fa-map-marker-alt text-warning"></i> Address</label>
                                                <input type="text" name="address" class="form-control"
                                                       value="<?php echo htmlspecialchars($company['address'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="small font-weight-bold">About Company</label>
                                        <textarea name="about" class="form-control" rows="3"
                                                  placeholder="Describe your company..."><?php echo htmlspecialchars($company['about'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold"><i class="fas fa-bullseye text-primary"></i> Mission</label>
                                                <textarea name="mission" class="form-control" rows="2"><?php echo htmlspecialchars($company['mission'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="small font-weight-bold"><i class="fas fa-eye text-warning"></i> Vision</label>
                                                <textarea name="vision" class="form-control" rows="2"><?php echo htmlspecialchars($company['vision'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="small font-weight-bold">Company Logo</label>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" name="logo"
                                                   accept="image/*" id="logoInput">
                                            <label class="custom-file-label" for="logoInput">
                                                <i class="fas fa-cloud-upload-alt"></i> Choose logo...
                                            </label>
                                        </div>
                                        <small class="text-muted">JPG, PNG, GIF, WEBP, SVG (Max 2MB)</small>

                                        <div class="image-preview-container mt-3" id="logoPreviewContainer">
                                            <?php if (!empty($company['logo']) && file_exists("uploads/{$company['logo']}")): ?>
                                                <div class="text-center">
                                                    <img src="uploads/<?php echo htmlspecialchars($company['logo']); ?>" alt="Current logo">
                                                    <p class="text-muted small mt-2 mb-0">Current logo (upload new to replace)</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="image-preview-placeholder">
                                                    <i class="fas fa-image"></i>
                                                    <p class="mb-0">No logo selected</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="d-flex justify-content-end">
                                        <button type="reset" class="btn btn-outline-secondary mr-2">
                                            <i class="fas fa-undo"></i> Reset Form
                                        </button>
                                        <button type="submit" name="update_company" class="btn btn-warning font-weight-bold">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =========================================
                     ACTIVITY LOG CARD (from activity_log table)
                ========================================== -->
                <div class="row">
                    <div class="col-12">
                        <div class="card info-card">
                            <div class="card-header-gradient card-header-gradient-log d-flex align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold">
                                    <i class="fas fa-history"></i> Activity Log
                                </h6>
                                <span class="stat-badge">
                                    <?php echo mysqli_num_rows($logs); ?> Recent Entries
                                </span>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($logs && mysqli_num_rows($logs) > 0): ?>
                                    <div class="log-timeline">
                                        <?php while ($log = mysqli_fetch_assoc($logs)):
                                            $a = strtolower($log['action']);
                                            $dotClass   = 'log-dot';
                                            $badgeClass = 'badge-create';
                                            if ($a === 'updated')     { $dotClass .= ' update'; $badgeClass = 'badge-update'; }
                                            elseif ($a === 'deleted') { $dotClass .= ' delete'; $badgeClass = 'badge-delete'; }
                                        ?>
                                            <div class="log-item">
                                                <span class="<?php echo $dotClass; ?>"></span>
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1" style="min-width:0;">
                                                        <span class="log-action-badge <?php echo $badgeClass; ?>">
                                                            <?php echo htmlspecialchars($log['action']); ?>
                                                        </span>
                                                        <span class="ml-2 text-gray-800 font-weight-500">
                                                            <?php echo htmlspecialchars($log['target_title'] ?: $log['target_type']); ?>
                                                        </span>
                                                        <?php if (!empty($log['details'])): ?>
                                                            <div class="small text-muted mt-1">
                                                                <?php echo htmlspecialchars($log['details']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted ml-3 text-nowrap">
                                                        <i class="far fa-clock"></i>
                                                        <?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-circle"></i>
                                                    <?php echo htmlspecialchars($log['user']); ?>
                                                    &middot;
                                                    <i class="fas fa-network-wired"></i>
                                                    <?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?>
                                                </small>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3 d-block"></i>
                                        <h6 class="text-muted">No activity yet</h6>
                                        <p class="text-muted small mb-0">Actions you perform will appear here.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ===== RESET FORM (fixed — no duplicate section param) ===== -->
<form id="resetForm" action="?section=settings" method="GET" style="display:none;">
    <input type="hidden" name="reset_company" value="<?php echo (int)($company['id'] ?? 1); ?>">
</form>

<script src="sweetalert.js"></script>
<script>
/* Wait for jQuery to exist, then run */
(function waitForDeps() {
    if (typeof jQuery === 'undefined' || typeof Swal === 'undefined') {
        if (!window.__depsRetries) window.__depsRetries = 0;
        window.__depsRetries++;
        if (window.__depsRetries < 30) {
            return setTimeout(waitForDeps, 100);
        }
        console.error('jQuery or SweetAlert2 failed to load.');
        return;
    }

    jQuery(function ($) {

        /* ===== LOGO PREVIEW ===== */
        $(document).on('change', '#logoInput', function () {
            const file = this.files[0];
            if (!file) return;

            const valid = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
            if (!valid.includes(file.type)) {
                Swal.fire({ icon:'error', title:'Invalid File',
                    text:'Please choose a JPG, PNG, GIF, WEBP or SVG image.',
                    confirmButtonColor:'#e74a3b' });
                $(this).val('');
                return;
            }
            if (file.size > 2000000) {
                Swal.fire({ icon:'error', title:'File Too Large',
                    text:'Logo must be under 2MB.', confirmButtonColor:'#e74a3b' });
                $(this).val('');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                $('#logoInput').next('.custom-file-label').html(
                    '<i class="fas fa-check-circle text-success"></i> ' + file.name
                );
                $('#logoPreviewContainer').html(`
                    <div class="text-center preview-animate">
                        <img src="${e.target.result}" alt="Preview">
                        <div class="mt-2">
                            <span class="badge badge-success">New logo</span>
                            <span class="text-muted small ml-2">${(file.size/1024).toFixed(1)} KB</span>
                        </div>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });

        /* ===== RESET FORM PREVIEW ===== */
        $('#editForm').on('reset', function () {
            setTimeout(function () {
                $('#logoInput').next('.custom-file-label').html(
                    '<i class="fas fa-cloud-upload-alt"></i> Choose logo...'
                );
                <?php if (!empty($company['logo']) && file_exists("uploads/{$company['logo']}")): ?>
                    $('#logoPreviewContainer').html(`
                        <div class="text-center">
                            <img src="uploads/<?php echo htmlspecialchars($company['logo']); ?>" alt="Current logo">
                            <p class="text-muted small mt-2 mb-0">Current logo</p>
                        </div>
                    `);
                <?php else: ?>
                    $('#logoPreviewContainer').html(`
                        <div class="image-preview-placeholder">
                            <i class="fas fa-image"></i>
                            <p class="mb-0">No logo selected</p>
                        </div>
                    `);
                <?php endif; ?>
            }, 50);
        });

        /* ===== FLASH MESSAGE ===== */
        <?php if (!empty($message)): ?>
        Swal.fire({
            icon: '<?php echo $message_icon; ?>',
            title: '<?php echo $message_type === "success" ? "Success!" : "Oops!"; ?>',
            text: '<?php echo htmlspecialchars($message, ENT_QUOTES); ?>',
            confirmButtonColor: '<?php echo $message_type === "success" ? "#1cc88a" : "#e74a3b"; ?>',
            timer: 3200,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
        <?php endif; ?>
    });

    /* ===== GLOBAL FUNCTIONS ===== */
    window.confirmReset = function () {
        Swal.fire({
            title: 'Clear all company information?',
            html: 'This will <strong>permanently erase</strong> all stored company details and the logo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            cancelButtonColor: '#858796',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Yes, clear it',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('resetForm').submit();
            }
        });
    };
})();
</script>

</body>
</html>