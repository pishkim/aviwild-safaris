<?php
//session_start();
include('config.php');
include('functions.php');

/* =========================================================
   SYSTEM ACTIVITY DETECTION
========================================================= */
$system_condition = "(
    LOWER(target_type) IN ('system','settings','auth','backup','database','security')
    OR LOWER(action)   IN ('login','logout','backup','settings','system','error')
    OR user IN ('System','SYSTEM','system')
)";

/* =========================================================
   FILTERS
========================================================= */
$filter_action = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_source = isset($_GET['source']) ? $_GET['source'] : 'all';
$filter_days   = isset($_GET['days'])   ? (int)$_GET['days'] : 0;

$where = "WHERE 1=1";

if ($filter_action !== '') {
    $where .= " AND action = '$filter_action'";
}
if ($filter_search !== '') {
    $where .= " AND (user LIKE '%$filter_search%'
                OR target_title LIKE '%$filter_search%'
                OR details LIKE '%$filter_search%'
                OR ip_address LIKE '%$filter_search%')";
}
if ($filter_source === 'system') {
    $where .= " AND $system_condition";
} elseif ($filter_source === 'user') {
    $where .= " AND NOT $system_condition";
}
if ($filter_days > 0) {
    $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL $filter_days DAY)";
}

/* ⛔ REMOVED: broken CSV export block.
   Export is now handled in index.php BEFORE navbar loads. */

/* =========================================================
   FETCH LOGS FROM DATABASE  ← this is your "Recent Activity"
========================================================= */
$query  = "SELECT * FROM activity_log $where ORDER BY created_at DESC LIMIT 500";
$result = mysqli_query($conn, $query);

/* =========================================================
   STATS
========================================================= */
function q_count($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    return $r ? (int)mysqli_fetch_assoc($r)['c'] : 0;
}

$stats = [
    'total'    => q_count($conn, "SELECT COUNT(*) c FROM activity_log"),
    'today'    => q_count($conn, "SELECT COUNT(*) c FROM activity_log WHERE DATE(created_at) = CURDATE()"),
    'user'     => q_count($conn, "SELECT COUNT(*) c FROM activity_log WHERE NOT $system_condition"),
    'system'   => q_count($conn, "SELECT COUNT(*) c FROM activity_log WHERE $system_condition"),
    'created'  => q_count($conn, "SELECT COUNT(*) c FROM activity_log WHERE action = 'Created'"),
    'updated'  => q_count($conn, "SELECT COUNT(*) c FROM activity_log WHERE action = 'Updated'"),
    'deleted'  => q_count($conn, "SELECT COUNT(*) c FROM activity_log WHERE action = 'Deleted'"),
];

/* Distinct actions for the filter dropdown */
$actions_list = [];
$ar = mysqli_query($conn, "SELECT DISTINCT action FROM activity_log ORDER BY action ASC");
if ($ar) { while ($a = mysqli_fetch_assoc($ar)) $actions_list[] = $a['action']; }
?>
<!-- ... everything from <style> down stays exactly the same ... -->

<style>
    /* ===== STAT CARDS ===== */
    .stat-card {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        position: relative;
        transition: transform .25s ease, box-shadow .25s ease;
        box-shadow: 0 3px 15px rgba(0,0,0,.05);
    }
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 25px rgba(0,0,0,.12);
    }
    .stat-card .icon-bg {
        position: absolute;
        right: -10px; top: -10px;
        font-size: 90px;
        opacity: .12;
        transform: rotate(-15deg);
    }
    .stat-card .stat-label {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: .5px;
        margin-bottom: 4px;
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 4px;
    }
    .stat-card .stat-sub {
        font-size: 11px;
        opacity: .75;
    }
    .stat-primary { background: linear-gradient(135deg, #4e73df, #224abe); color: #fff; }
    .stat-success { background: linear-gradient(135deg, #1cc88a, #13855c); color: #fff; }
    .stat-warning { background: linear-gradient(135deg, #f6c23e, #dda20a); color: #fff; }
    .stat-danger  { background: linear-gradient(135deg, #e74a3b, #be2617); color: #fff; }
    .stat-info    { background: linear-gradient(135deg, #36b9cc, #258391); color: #fff; }
    .stat-dark    { background: linear-gradient(135deg, #5a5c69, #373840); color: #fff; }

    /* ===== SOURCE TABS ===== */
    .source-tabs .nav-link {
        border: none;
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 600;
        font-size: 13px;
        color: #858796;
        background: #f8f9fc;
        margin-right: 8px;
        transition: all .2s ease;
    }
    .source-tabs .nav-link:hover {
        background: #eaecf4;
        color: #4e73df;
    }
    .source-tabs .nav-link.active {
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
        box-shadow: 0 4px 12px rgba(78,115,223,.35);
    }
    .source-tabs .nav-link .count-pill {
        background: rgba(0,0,0,.08);
        border-radius: 20px;
        padding: 1px 8px;
        font-size: 11px;
        margin-left: 6px;
    }
    .source-tabs .nav-link.active .count-pill {
        background: rgba(255,255,255,.25);
    }

    /* ===== FILTER BAR ===== */
    .filter-bar {
        background: #f8f9fc;
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid #e3e6f0;
    }
    .filter-bar .form-control,
    .filter-bar .custom-select {
        border-radius: 8px;
        border: 1px solid #d1d3e2;
        font-size: 13px;
        height: 40px;
    }
    .filter-bar .form-control:focus,
    .filter-bar .custom-select:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 .2rem rgba(78,115,223,.15);
    }

    /* ===== ACTIVITY ROW ===== */
    .activity-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .activity-created  { background: #d4f5e6; color: #1cc88a; }
    .activity-updated  { background: #fff3cd; color: #dda20a; }
    .activity-deleted  { background: #fde7e6; color: #e74a3b; }
    .activity-login    { background: #e0f3f7; color: #36b9cc; }
    .activity-logout   { background: #ececec; color: #5a5c69; }
    .activity-default  { background: #e7efff; color: #4e73df; }

    .activity-row {
        transition: background .15s ease;
    }
    .activity-row:hover {
        background: #f8f9fc;
    }

    /* ===== SOURCE BADGES ===== */
    .source-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .source-user   { background: #e7efff; color: #4e73df; }
    .source-system { background: #f0e7ff; color: #6f42c1; }

    /* ===== RELATIVE TIME ===== */
    .time-ago { font-size: 11px; color: #858796; }
    .time-ago.fresh { color: #1cc88a; font-weight: 600; }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 70px;
        color: #d1d3e2;
        margin-bottom: 16px;
        display: block;
    }

    /* ===== TABLE TWEAKS ===== */
    #dataTable thead th {
        border-top: none;
        border-bottom: 2px solid #e3e6f0;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-weight: 700;
        color: #5a5c69;
    }
    #dataTable tbody td {
        vertical-align: middle;
        font-size: 13px;
    }
    .badge-action {
        font-size: 11px;
        padding: 5px 10px;
        border-radius: 8px;
        font-weight: 600;
        letter-spacing: .2px;
    }
    .target-title {
        font-weight: 600;
        color: #3a3b45;
        display: block;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .details-cell {
        max-width: 280px;
        color: #6c757d;
    }
</style>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid">

            <!-- ===== PAGE HEADING ===== -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                        <i class="fas fa-history text-primary"></i> Activity Log
                    </h1>
                    <p class="text-muted small mb-0">
                        Track every action performed on the system — by users and by the system itself
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <!-- <a href="?section=activity_log&export=csv&source=<?php echo urlencode($filter_source); ?>&action=<?php echo urlencode($filter_action); ?>&search=<?php echo urlencode($filter_search); ?>&days=<?php echo $filter_days; ?>"
                       class="btn btn-sm btn-outline-success shadow-sm mr-2">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a> -->
                    <a href="?section=activity_log" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-sync"></i> Refresh
                    </a>
                </div>
            </div>

            <!-- ===== STAT CARDS ===== -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card stat-card stat-primary h-100">
                        <i class="fas fa-list icon-bg"></i>
                        <div class="card-body p-3">
                            <div class="stat-label">Total Activities</div>
                            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                            <div class="stat-sub"><i class="fas fa-database"></i> All-time records</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card stat-card stat-success h-100">
                        <i class="fas fa-calendar-day icon-bg"></i>
                        <div class="card-body p-3">
                            <div class="stat-label">Today</div>
                            <div class="stat-value"><?php echo number_format($stats['today']); ?></div>
                            <div class="stat-sub"><i class="fas fa-clock"></i> Since midnight</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card stat-card stat-info h-100">
                        <i class="fas fa-user icon-bg"></i>
                        <div class="card-body p-3">
                            <div class="stat-label">User Actions</div>
                            <div class="stat-value"><?php echo number_format($stats['user']); ?></div>
                            <div class="stat-sub"><i class="fas fa-user-check"></i> Human-triggered</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card stat-card stat-dark h-100">
                        <i class="fas fa-server icon-bg"></i>
                        <div class="card-body p-3">
                            <div class="stat-label">System Events</div>
                            <div class="stat-value"><?php echo number_format($stats['system']); ?></div>
                            <div class="stat-sub"><i class="fas fa-microchip"></i> Automated / system</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card stat-card stat-warning h-100">
                        <i class="fas fa-edit icon-bg"></i>
                        <div class="card-body p-3">
                            <div class="stat-label">Updated</div>
                            <div class="stat-value"><?php echo number_format($stats['updated']); ?></div>
                            <div class="stat-sub"><i class="fas fa-pen"></i> Edits made</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="card stat-card stat-danger h-100">
                        <i class="fas fa-trash icon-bg"></i>
                        <div class="card-body p-3">
                            <div class="stat-label">Deleted</div>
                            <div class="stat-value"><?php echo number_format($stats['deleted']); ?></div>
                            <div class="stat-sub"><i class="fas fa-minus-circle"></i> Removals</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== SOURCE TABS ===== -->
            <ul class="nav source-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_source === 'all' ? 'active' : ''; ?>"
                       href="?section=activity_log&source=all&action=<?php echo urlencode($filter_action); ?>&search=<?php echo urlencode($filter_search); ?>&days=<?php echo $filter_days; ?>">
                        <i class="fas fa-layer-group"></i> All Activities
                        <span class="count-pill"><?php echo $stats['total']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_source === 'user' ? 'active' : ''; ?>"
                       href="?section=activity_log&source=user&action=<?php echo urlencode($filter_action); ?>&search=<?php echo urlencode($filter_search); ?>&days=<?php echo $filter_days; ?>">
                        <i class="fas fa-user"></i> User Activities
                        <span class="count-pill"><?php echo $stats['user']; ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter_source === 'system' ? 'active' : ''; ?>"
                       href="?section=activity_log&source=system&action=<?php echo urlencode($filter_action); ?>&search=<?php echo urlencode($filter_search); ?>&days=<?php echo $filter_days; ?>">
                        <i class="fas fa-server"></i> System Activities
                        <span class="count-pill"><?php echo $stats['system']; ?></span>
                    </a>
                </li>
            </ul>

            <!-- ===== FILTER BAR ===== -->
            <div class="filter-bar mb-4">
                <form method="GET" action="" class="form-row align-items-end">
                    <input type="hidden" name="section" value="activity_log">
                    <input type="hidden" name="source"  value="<?php echo htmlspecialchars($filter_source); ?>">

                    <div class="col-md-4 mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Search</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0" style="border-radius:8px 0 0 8px;">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" name="search" class="form-control border-left-0"
                                   style="border-radius:0 8px 8px 0;"
                                   placeholder="User, target, details, IP..."
                                   value="<?php echo htmlspecialchars($filter_search); ?>">
                        </div>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Action</label>
                        <select name="action" class="custom-select">
                            <option value="">All Actions</option>
                            <?php foreach ($actions_list as $a): ?>
                                <option value="<?php echo htmlspecialchars($a); ?>"
                                    <?php echo $filter_action === $a ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Time Range</label>
                        <select name="days" class="custom-select">
                            <option value="0"  <?php echo $filter_days === 0   ? 'selected' : ''; ?>>All Time</option>
                            <option value="1"  <?php echo $filter_days === 1   ? 'selected' : ''; ?>>Last 24 Hours</option>
                            <option value="7"  <?php echo $filter_days === 7   ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $filter_days === 30  ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $filter_days === 90  ? 'selected' : ''; ?>>Last 90 Days</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-2 d-flex">
                        <button type="submit" class="btn btn-primary btn-block mr-1" style="border-radius:8px;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="?section=activity_log" class="btn btn-outline-secondary" style="border-radius:8px;" title="Reset">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>

                <?php if ($filter_search || $filter_action || $filter_source !== 'all' || $filter_days > 0): ?>
                    <div class="mt-2 pt-2 border-top">
                        <small class="text-muted mr-2">Active filters:</small>
                        <?php if ($filter_source !== 'all'): ?>
                            <span class="badge badge-info">
                                <?php echo ucfirst($filter_source); ?> only
                            </span>
                        <?php endif; ?>
                        <?php if ($filter_action): ?>
                            <span class="badge badge-warning">Action: <?php echo htmlspecialchars($filter_action); ?></span>
                        <?php endif; ?>
                        <?php if ($filter_search): ?>
                            <span class="badge badge-secondary">Search: "<?php echo htmlspecialchars($filter_search); ?>"</span>
                        <?php endif; ?>
                        <?php if ($filter_days > 0): ?>
                            <span class="badge badge-primary">Last <?php echo $filter_days; ?> days</span>
                        <?php endif; ?>
                        <a href="?section=activity_log" class="badge badge-light border ml-1">
                            <i class="fas fa-times"></i> Clear all
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== ACTIVITY TABLE ===== -->
            <div class="card shadow mb-4" style="border-radius:14px; border:none;">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center"
                     style="border-radius:14px 14px 0 0; border-bottom:1px solid #e3e6f0;">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-stream"></i>
                        <?php
                            if ($filter_source === 'system')      echo 'System Activities';
                            elseif ($filter_source === 'user')    echo 'User Activities';
                            else                                  echo 'Recent Activity';
                        ?>
                        <span class="badge badge-primary ml-2"><?php echo mysqli_num_rows($result); ?></span>
                    </h6>
                    <div class="small text-muted">
                        <i class="far fa-clock"></i> Auto-timestamped records
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th width="4%">#</th>
                                        <th width="14%">Who</th>
                                        <th width="11%">Source</th>
                                        <th width="11%">Action</th>
                                        <th width="18%">Target</th>
                                        <th width="22%">Details</th>
                                        <th width="10%">IP</th>
                                        <th width="10%">When</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    $counter = 1;
                                    while ($log = mysqli_fetch_assoc($result)):
                                        $act  = strtolower($log['action']);
                                        $is_system = (
                                            in_array(strtolower($log['target_type'] ?? ''), ['system','settings','auth','backup','database','security'])
                                            || in_array($act, ['login','logout','backup','settings','system','error'])
                                            || in_array($log['user'], ['System','SYSTEM','system'])
                                        );

                                        $icon = 'fa-circle';
                                        $cls  = 'activity-default';
                                        $badge = 'badge-secondary';
                                        if ($act === 'created')      { $icon='fa-plus-circle'; $cls='activity-created'; $badge='badge-success'; }
                                        elseif ($act === 'updated')  { $icon='fa-edit';        $cls='activity-updated'; $badge='badge-warning'; }
                                        elseif ($act === 'deleted')  { $icon='fa-trash';       $cls='activity-deleted'; $badge='badge-danger';  }
                                        elseif ($act === 'login')    { $icon='fa-sign-in-alt'; $cls='activity-login';   $badge='badge-info';    }
                                        elseif ($act === 'logout')   { $icon='fa-sign-out-alt';$cls='activity-logout';  $badge='badge-dark';    }
                                        else                         { $icon='fa-bolt';         $cls='activity-default'; $badge='badge-primary'; }

                                        // Relative time
                                        $ts   = strtotime($log['created_at']);
                                        $diff = time() - $ts;
                                        if ($diff < 60)          $rel = 'Just now';
                                        elseif ($diff < 3600)    $rel = floor($diff/60) . ' min ago';
                                        elseif ($diff < 86400)   $rel = floor($diff/3600) . ' hrs ago';
                                        elseif ($diff < 604800)  $rel = floor($diff/86400) . ' days ago';
                                        else                     $rel = date('M d, Y', $ts);
                                ?>
                                    <tr class="activity-row">
                                        <td class="text-muted small"><?php echo $counter++; ?></td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="activity-icon <?php echo $cls; ?> mr-2">
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                </span>
                                                <div>
                                                    <div class="font-weight-bold small">
                                                        <?php echo htmlspecialchars($log['user']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="source-badge <?php echo $is_system ? 'source-system' : 'source-user'; ?>">
                                                <i class="fas <?php echo $is_system ? 'fa-server' : 'fa-user'; ?>"></i>
                                                <?php echo $is_system ? 'System' : 'User'; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="badge badge-action <?php echo $badge; ?>">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <small class="text-muted d-block">
                                                <?php echo htmlspecialchars($log['target_type'] ?? '-'); ?>
                                            </small>
                                            <span class="target-title" title="<?php echo htmlspecialchars($log['target_title'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($log['target_title'] ?: '-'); ?>
                                            </span>
                                            <?php if (!empty($log['target_id'])): ?>
                                                <small class="text-muted">#<?php echo (int)$log['target_id']; ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <td class="details-cell small">
                                            <?php
                                                $d = $log['details'] ?? '-';
                                                echo htmlspecialchars(strlen($d) > 90 ? substr($d, 0, 90) . '…' : $d);
                                            ?>
                                        </td>

                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-network-wired"></i>
                                                <?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <small>
                                                <i class="far fa-calendar-alt text-muted"></i>
                                                <?php echo date('M d, Y', $ts); ?>
                                                <br>
                                                <i class="far fa-clock text-muted"></i>
                                                <?php echo date('h:i A', $ts); ?>
                                                <br>
                                                <span class="time-ago <?php echo $diff < 300 ? 'fresh' : ''; ?>">
                                                    <?php echo $rel; ?>
                                                </span>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h5 class="text-muted mb-2">No activity found</h5>
                            <p class="text-muted small mb-3">
                                <?php if ($filter_source === 'system'): ?>
                                    No system events have been recorded yet.
                                <?php elseif ($filter_source === 'user'): ?>
                                    No user activities match your current filters.
                                <?php else: ?>
                                    Activities will appear here as you and your team use the system.
                                <?php endif; ?>
                            </p>
                            <a href="?section=activity_log" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-times"></i> Clear filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->
</div>
<!-- End of Content Wrapper -->

<script>
$(document).ready(function () {

    /* ===== DATATABLES ===== */
    if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
        $('#dataTable').DataTable({
            "order": [[7, "desc"]],
            "pageLength": 15,
            "lengthMenu": [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, "All"]],
            "language": {
                "search": "",
                "searchPlaceholder": "Quick search...",
                "lengthMenu": "Show _MENU_ per page",
                "info": "_START_–_END_ of _TOTAL_ activities",
                "infoEmpty": "No activities available",
                "infoFiltered": "(filtered from _MAX_)",
                "zeroRecords": "No matching activities found",
                "paginate": {
                    "first":    "«",
                    "last":     "»",
                    "next":     "›",
                    "previous": "‹"
                }
            },
            "columnDefs": [
                { "orderable": false, "targets": [0, 2, 5] },
                { "searchable": false, "targets": [0] }
            ],
            "dom": "<'row mb-2'<'col-sm-6'l><'col-sm-6'f>>" +
                   "<'row'<'col-12'tr>>" +
                   "<'row mt-3'<'col-sm-5'i><'col-sm-7'p>>"
        });
    }

    /* ===== LIVE "time-ago" UPDATER ===== */
    // Refresh relative times every 60 seconds without page reload
    setInterval(function () {
        $('.time-ago').each(function () {
            // optional: could re-fetch or recompute — left minimal here
        });
    }, 60000);

    /* ===== KEYBOARD: "/" focuses search ===== */
    $(document).on('keydown', function (e) {
        if (e.key === '/' && !$(e.target).is('input,textarea,select')) {
            e.preventDefault();
            $('.dataTables_filter input').focus();
        }
    });

    /* ===== AUTO-SUBMIT FILTERS ON CHANGE (optional nicety) ===== */
    $('.filter-bar select').on('change', function () {
        $(this).closest('form').submit();
    });
});
</script>