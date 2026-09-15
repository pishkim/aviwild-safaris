<?php
/* =========================================================
   SIDEBAR NAVBAR — AviWild
   Detects current section from ?section= to highlight active item
========================================================= */
$current_section = $_GET['section'] ?? 'dashboard';

/* Auto-open collapse groups if a child is active */
$blog_open     = in_array($current_section, ['blog'])          ? 'show' : '';
$settings_open = in_array($current_section, ['settings','profile']) ? 'show' : '';
?>
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- ===== SIDEBAR BRAND ===== -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="?section=dashboard">
        <div class="sidebar-brand-icon">
            <img src="uploads/aviwild.jpeg" alt="AviWild"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="brand-fallback" style="display:none;">
                <i class="fas fa-plane-departure"></i>
            </div>
        </div>
        <div class="sidebar-brand-text mx-3">
            AviWild
            <small class="d-block text-white-50" style="font-size:10px; font-weight:400; letter-spacing:1px;">
                TOURS &amp; TRAVEL
            </small>
        </div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- ===== DASHBOARD ===== -->
    <li class="nav-item <?php echo $current_section === 'dashboard' ? 'active' : ''; ?>">
        <a class="nav-link" href="?section=dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Management
    </div>

    <!-- ===== BLOG (Collapsible) ===== -->
    <li class="nav-item <?php echo $current_section === 'blog' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $blog_open ? '' : 'collapsed'; ?>"
           href="#" data-toggle="collapse" data-target="#collapseBlog"
           aria-expanded="<?php echo $blog_open ? 'true' : 'false'; ?>"
           aria-controls="collapseBlog">
            <i class="fas fa-fw fa-blog"></i>
            <span>Blog</span>
        </a>
        <div id="collapseBlog" class="collapse <?php echo $blog_open; ?>"
             aria-labelledby="headingBlog" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Posts:</h6>
                <a class="collapse-item <?php echo $current_section === 'blog' ? 'active' : ''; ?>"
                   href="?section=blog">
                    <i class="fas fa-list fa-sm mr-1"></i> All Posts
                </a>
                <a class="collapse-item" href="?section=blog#createPostModal">
                    <i class="fas fa-plus-circle fa-sm mr-1"></i> Create Post
                </a>
            </div>
        </div>
    </li>

    <!-- ===== ACTIVITY LOG (Direct link) ===== -->
    <li class="nav-item <?php echo $current_section === 'activity_log' ? 'active' : ''; ?>">
        <a class="nav-link" href="?section=activity_log">
            <i class="fas fa-fw fa-history"></i>
            <span>Activity Log</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        System
    </div>

    <!-- ===== SETTINGS (Collapsible) ===== -->
    <li class="nav-item <?php echo $current_section === 'settings' || $current_section === 'profile' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $settings_open ? '' : 'collapsed'; ?>"
           href="#" data-toggle="collapse" data-target="#collapseSettings"
           aria-expanded="<?php echo $settings_open ? 'true' : 'false'; ?>"
           aria-controls="collapseSettings">
            <i class="fas fa-fw fa-cogs"></i>
            <span>Settings</span>
        </a>
        <div id="collapseSettings" class="collapse <?php echo $settings_open; ?>"
             aria-labelledby="headingSettings" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Configuration:</h6>
                <a class="collapse-item <?php echo $current_section === 'settings' ? 'active' : ''; ?>"
                   href="?section=settings">
                    <i class="fas fa-building fa-sm mr-1"></i> Company Info
                </a>
                <a class="collapse-item <?php echo $current_section === 'profile' ? 'active' : ''; ?>"
                   href="?section=profile">
                    <i class="fas fa-user-circle fa-sm mr-1"></i> My Profile
                </a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- ===== USER FOOTER CARD ===== -->
    <li class="nav-item mt-3 mb-2 px-3">
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ml-2 flex-grow-1" style="min-width:0;">
                    <div class="text-white small font-weight-bold text-truncate">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin User'); ?>
                    </div>
                    <div class="text-white-50" style="font-size:10px;">
                        <i class="fas fa-circle text-success" style="font-size:6px;"></i> Online
                    </div>
                </div>
            </div>
        </div>
    </li>

    <!-- Sidebar Toggler -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>

<!-- ===== SCROLL TO TOP ===== -->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- ===== LOGOUT MODAL ===== -->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog"
     aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:14px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#4e73df,#224abe); border-radius:14px 14px 0 0; border:none;">
                <h5 class="modal-title text-white" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt"></i> Ready to Leave?
                </h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-question-circle text-warning mb-3" style="font-size:48px;"></i>
                <p class="mb-0 text-muted">
                    You're about to end your session. Any unsaved changes will be lost.
                </p>
            </div>
            <div class="modal-footer" style="border:none;">
                <button class="btn btn-outline-secondary" type="button" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a class="btn btn-primary font-weight-bold" href="?section=logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* ===== SIDEBAR BRAND ===== */
    .sidebar-brand-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .sidebar-brand-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 8px;
        background: rgba(255,255,255,.95);
        padding: 3px;
    }
    .brand-fallback {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255,255,255,.2);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .sidebar-brand-text {
        font-weight: 800;
        letter-spacing: .5px;
        line-height: 1.15;
    }

    /* ===== ACTIVE LINK ENHANCEMENT ===== */
    .sidebar .nav-item.active > .nav-link {
        background: rgba(255,255,255,.15);
        border-radius: 10px;
        margin: 0 8px;
        color: #fff;
        font-weight: 600;
    }
    .sidebar .nav-item.active > .nav-link i {
        color: #fff;
    }

    .sidebar .nav-link {
        border-radius: 10px;
        margin: 2px 8px;
        transition: background .15s ease;
    }
    .sidebar .nav-link:hover {
        background: rgba(255,255,255,.10);
    }

    /* ===== COLLAPSE ITEM ACTIVE ===== */
    .collapse-item.active {
        background: #e7efff !important;
        color: #4e73df !important;
        font-weight: 700;
    }

    /* ===== USER FOOTER CARD ===== */
    .sidebar-user-card {
        background: rgba(255,255,255,.10);
        border-radius: 12px;
        padding: 10px 12px;
        backdrop-filter: blur(4px);
    }
    .sidebar-user-card .user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: rgba(255,255,255,.25);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    /* ===== COLLAPSE HEADERS ===== */
    .collapse-header {
        font-size: 11px;
        color: #858796;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 6px 16px;
    }
</style>