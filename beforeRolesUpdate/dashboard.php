<?php
require_once 'config.php';
require_once 'functions.php';

/* =========================================================
   HELPERS
========================================================= */
function d_q($conn, $sql, $field = 'c') {
    $r = mysqli_query($conn, $sql);
    return $r ? (int)mysqli_fetch_assoc($r)[$field] : 0;
}

/* =========================================================
   KPI — BLOG
========================================================= */
$total_posts   = d_q($conn, "SELECT COUNT(*) c FROM blog_posts");
$posts_today   = d_q($conn, "SELECT COUNT(*) c FROM blog_posts WHERE DATE(created_at) = CURDATE()");
$posts_week    = d_q($conn, "SELECT COUNT(*) c FROM blog_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$posts_prev_wk = d_q($conn, "SELECT COUNT(*) c FROM blog_posts WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)");
$posts_month   = d_q($conn, "SELECT COUNT(*) c FROM blog_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

$posts_growth  = $posts_prev_wk > 0
    ? round((($posts_week - $posts_prev_wk) / $posts_prev_wk) * 100)
    : ($posts_week > 0 ? 100 : 0);

/* =========================================================
   KPI — ACTIVITY
========================================================= */
$act_total   = d_q($conn, "SELECT COUNT(*) c FROM activity_log");
$act_today   = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE DATE(created_at) = CURDATE()");
$act_yest    = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$act_week    = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

$act_growth  = $act_yest > 0
    ? round((($act_today - $act_yest) / $act_yest) * 100)
    : ($act_today > 0 ? 100 : 0);

$act_system  = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE
                    LOWER(target_type) IN ('system','settings','auth','backup','database','security')
                    OR LOWER(action) IN ('login','logout','backup','settings','system','error')
                    OR user IN ('System','SYSTEM','system')");
$act_user    = max(0, $act_total - $act_system);

/* =========================================================
   CHART DATA — 14 day activity line
========================================================= */
$chart_labels_14 = [];
$chart_data_14   = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels_14[] = date('M j', strtotime($d));
    $chart_data_14[]   = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE DATE(created_at) = '$d'");
}

/* =========================================================
   CHART DATA — posts per month (6 months bar)
========================================================= */
$month_labels = [];
$month_data   = [];
for ($i = 5; $i >= 0; $i--) {
    $start = date('Y-m-01', strtotime("-$i months"));
    $end   = date('Y-m-t', strtotime("-$i months"));
    $month_labels[] = date('M', strtotime($start));
    $month_data[]   = d_q($conn, "SELECT COUNT(*) c FROM blog_posts WHERE DATE(created_at) BETWEEN '$start' AND '$end'");
}

/* =========================================================
   CHART — Action breakdown (doughnut)
========================================================= */
$created_count = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE action = 'Created'");
$updated_count = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE action = 'Updated'");
$deleted_count = d_q($conn, "SELECT COUNT(*) c FROM activity_log WHERE action = 'Deleted'");
$other_count   = max(0, $act_total - ($created_count + $updated_count + $deleted_count));

/* =========================================================
   HOURLY HEATMAP
========================================================= */
$hourly = array_fill(0, 24, 0);
$hr = mysqli_query($conn, "
    SELECT HOUR(created_at) h, COUNT(*) c
    FROM activity_log
    WHERE DATE(created_at) = CURDATE()
    GROUP BY HOUR(created_at)
");
if ($hr) {
    while ($r = mysqli_fetch_assoc($hr)) {
        $hourly[(int)$r['h']] = (int)$r['c'];
    }
}

/* =========================================================
   TOP CONTRIBUTORS
========================================================= */
$top_users = mysqli_query($conn, "
    SELECT user, COUNT(*) as total
    FROM activity_log
    GROUP BY user
    ORDER BY total DESC
    LIMIT 3
");

/* =========================================================
   TARGET TYPE BREAKDOWN
========================================================= */
$top_targets = mysqli_query($conn, "
    SELECT target_type, COUNT(*) as total
    FROM activity_log
    WHERE target_type IS NOT NULL AND target_type <> ''
    GROUP BY target_type
    ORDER BY total DESC
    LIMIT 3
");
$top_targets_max = 1;
$target_rows = [];
if ($top_targets) {
    while ($r = mysqli_fetch_assoc($top_targets)) {
        $target_rows[] = $r;
        if ($r['total'] > $top_targets_max) $top_targets_max = $r['total'];
    }
}

/* =========================================================
   LAST 8 ACTIVITY LOG
========================================================= */
$recent_logs = mysqli_query($conn, "
    SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5
");

/* =========================================================
   RECENT POSTS
========================================================= */
$recent_posts = mysqli_query($conn, "
    SELECT id, title, author, image, publication_date, created_at
    FROM blog_posts ORDER BY created_at DESC LIMIT 3
");

/* =========================================================
   SYSTEM INFO
========================================================= */
$company     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company_info LIMIT 1"));
$db_size     = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
    FROM information_schema.TABLES WHERE table_schema = DATABASE()
"))['size'] ?? 0;
$php_version = phpversion();
$mysql_v     = mysqli_get_server_info($conn);
$server_time = date('D, M d Y · h:i A');

/* Greeting */
$hour = (int)date('H');
if     ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else                $greeting = 'Good evening';
$admin_name = $_SESSION['username'] ?? 'Admin';

/* Progress ring */
$monthly_target = 20;
$month_progress = min(100, $posts_month > 0 ? round(($posts_month / $monthly_target) * 100) : 0);
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid py-4">

            <!-- ===== HERO ===== -->
            <div class="hero-card mb-4">
                <div class="hero-content">
                    <div>
                        <h1 class="hero-title mb-1">
                            <?php echo "<!-- DASHBOARD VERSION 2 LOADED: " . date('H:i:s') . " -->"; 
                            echo $greeting; ?>, <?php echo htmlspecialchars($admin_name); ?> 👋
                        </h1>
                        <p class="hero-subtitle mb-0">
                            Here's what's happening at
                            <strong><?php echo htmlspecialchars($company['company_name'] ?: 'your company'); ?></strong>
                        </p>
                        <div class="hero-meta mt-2">
                            <span><i class="far fa-calendar-alt"></i> <?php echo $server_time; ?></span>
                            <span class="mx-2">·</span>
                            <!-- <span><i class="fas fa-server"></i> PHP <?php echo $php_version; ?></span>
                            <span class="mx-2">·</span>
                            <span><i class="fas fa-database"></i> MySQL <?php echo $mysql_v; ?></span>
                            <span class="mx-2">·</span> -->
                            <span><i class="fas fa-hdd"></i> <?php echo $db_size; ?> MB</span>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <a href="?section=blog" class="btn btn-light btn-sm font-weight-bold shadow-sm">
                            <i class="fas fa-plus"></i> New Post
                        </a>
                        <a href="?section=activity_log" class="btn btn-outline-light btn-sm font-weight-bold ml-2">
                            <i class="fas fa-history"></i> Full Log
                        </a>
                    </div>
                </div>
            </div>

            <!-- ===== KPI TILES ===== -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-icon kpi-icon-primary">
                                <i class="fas fa-blog"></i>
                            </div>
                            <div class="kpi-trend <?php echo $posts_growth >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-arrow-<?php echo $posts_growth >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($posts_growth); ?>%
                            </div>
                        </div>
                        <div class="kpi-value"><?php echo number_format($total_posts); ?></div>
                        <div class="kpi-label">Total Blog Posts</div>
                        <div class="kpi-spark">
                            <canvas id="sparkPosts" height="40"></canvas>
                        </div>
                        <div class="kpi-footer">
                            <span><i class="fas fa-plus-circle text-success"></i> <?php echo $posts_today; ?> today</span>
                            <span><i class="far fa-calendar text-info"></i> <?php echo $posts_week; ?> this week</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-icon kpi-icon-success">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="kpi-trend <?php echo $act_growth >= 0 ? 'up' : 'down'; ?>">
                                <i class="fas fa-arrow-<?php echo $act_growth >= 0 ? 'up' : 'down'; ?>"></i>
                                <?php echo abs($act_growth); ?>%
                            </div>
                        </div>
                        <div class="kpi-value"><?php echo number_format($act_today); ?></div>
                        <div class="kpi-label">Today's Activities</div>
                        <div class="kpi-spark">
                            <canvas id="sparkActivity" height="40"></canvas>
                        </div>
                        <div class="kpi-footer">
                            <span><i class="fas fa-calendar-week text-success"></i> <?php echo $act_week; ?> this week</span>
                            <span><i class="fas fa-database text-muted"></i> <?php echo number_format($act_total); ?> total</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-icon kpi-icon-info">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="kpi-badge">User</div>
                        </div>
                        <div class="kpi-value"><?php echo number_format($act_user); ?></div>
                        <div class="kpi-label">User Actions</div>
                        <div class="kpi-ring-wrap">
                            <div class="progress kpi-progress">
                                <div class="progress-bar bg-info" style="width: <?php echo $act_total > 0 ? round(($act_user / $act_total) * 100) : 0; ?>%"></div>
                            </div>
                            <small class="text-muted">
                                <?php echo $act_total > 0 ? round(($act_user / $act_total) * 100) : 0; ?>% of all activity
                            </small>
                        </div>
                        <div class="kpi-footer">
                            <span><i class="fas fa-user-circle text-info"></i> Human-triggered</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div class="kpi-icon kpi-icon-dark">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="kpi-badge dark">System</div>
                        </div>
                        <div class="kpi-value"><?php echo number_format($act_system); ?></div>
                        <div class="kpi-label">System Events</div>
                        <div class="kpi-ring-wrap">
                            <div class="progress kpi-progress">
                                <div class="progress-bar bg-dark" style="width: <?php echo $act_total > 0 ? round(($act_system / $act_total) * 100) : 0; ?>%"></div>
                            </div>
                            <small class="text-muted">
                                <?php echo $act_total > 0 ? round(($act_system / $act_total) * 100) : 0; ?>% of all activity
                            </small>
                        </div>
                        <div class="kpi-footer">
                            <span><i class="fas fa-microchip text-dark"></i> Automated</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== CHART ROW 1 — Activity line + Goal ring ===== -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0 font-weight-bold text-gray-800">
                                    <i class="fas fa-chart-line text-primary"></i> Activity Trend
                                </h6>
                                <small class="text-muted">Last 14 days</small>
                            </div>
                            <span class="badge badge-primary"><?php echo array_sum($chart_data_14); ?> total</span>
                        </div>
                        <div class="card-body">
                            <canvas id="activityChart" height="85"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-bullseye text-warning"></i> Monthly Goal
                            </h6>
                            <small class="text-muted">Posts published</small>
                        </div>
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="goal-ring-wrap">
                                <svg viewBox="0 0 120 120" class="goal-ring">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="#eef0f5" stroke-width="10"></circle>
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="url(#goalGrad)"
                                            stroke-width="10" stroke-linecap="round"
                                            stroke-dasharray="326.7"
                                            stroke-dashoffset="<?php echo 326.7 - (326.7 * $month_progress / 100); ?>"
                                            transform="rotate(-90 60 60)"></circle>
                                    <defs>
                                        <linearGradient id="goalGrad" x1="0" y1="0" x2="1" y2="1">
                                            <stop offset="0%" stop-color="#4e73df"></stop>
                                            <stop offset="100%" stop-color="#1cc88a"></stop>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="goal-ring-label">
                                    <div class="goal-value"><?php echo $month_progress; ?><small>%</small></div>
                                    <div class="goal-sub"><?php echo $posts_month; ?> / <?php echo $monthly_target; ?></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge badge-success">
                                    <i class="fas fa-arrow-up"></i> <?php echo $posts_month; ?> published this month
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== CHART ROW 2 — Posts bar + Action doughnut ===== -->
            <div class="row mb-4">
                <div class="col-lg-7 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0 font-weight-bold text-gray-800">
                                    <i class="fas fa-chart-bar text-success"></i> Posts Published Per Month
                                </h6>
                                <small class="text-muted">Last 6 months</small>
                            </div>
                            <span class="badge badge-success"><?php echo array_sum($month_data); ?> total</span>
                        </div>
                        <div class="card-body">
                            <canvas id="postsBarChart" height="85"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-chart-pie text-warning"></i> Action Breakdown
                            </h6>
                            <small class="text-muted">All-time activity</small>
                        </div>
                        <div class="card-body" style="position:relative; height:260px;">
                            <canvas id="actionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== CHART ROW 3 — Top contributors + Target types + Hourly heatmap ===== -->
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-crown text-warning"></i> Top Contributors
                            </h6>
                            <small class="text-muted">Most active users</small>
                        </div>
                        <div class="card-body p-3">
                            <?php
                            $max_user = 1;
                            $users_list = [];
                            if ($top_users) {
                                while ($u = mysqli_fetch_assoc($top_users)) {
                                    $users_list[] = $u;
                                    if ($u['total'] > $max_user) $max_user = $u['total'];
                                }
                            }
                            $medals = ['#f6c23e', '#858796', '#cd7f32', '#4e73df', '#36b9cc'];
                            ?>
                            <?php if (!empty($users_list)): ?>
                                <?php foreach ($users_list as $i => $u): ?>
                                    <div class="contributor-row">
                                        <div class="contributor-medal" style="background:<?php echo $medals[$i] ?? '#858796'; ?>;">
                                            <?php echo $i + 1; ?>
                                        </div>
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="font-weight-bold small text-truncate">
                                                    <?php echo htmlspecialchars($u['user']); ?>
                                                </span>
                                                <span class="small text-muted">
                                                    <?php echo $u['total']; ?> acts
                                                </span>
                                            </div>
                                            <div class="progress contributor-bar">
                                                <div class="progress-bar"
                                                     style="width:<?php echo round(($u['total'] / $max_user) * 100); ?>%;
                                                            background:<?php echo $medals[$i] ?? '#858796'; ?>;"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3 small mb-0">No contributors yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-crosshairs text-info"></i> Top Targets
                            </h6>
                            <small class="text-muted">Most modified entities</small>
                        </div>
                        <div class="card-body p-3">
                            <?php if (!empty($target_rows)): ?>
                                <?php foreach ($target_rows as $t): ?>
                                    <div class="target-row">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small font-weight-bold">
                                                <?php echo htmlspecialchars($t['target_type']); ?>
                                            </span>
                                            <span class="badge badge-light"><?php echo $t['total']; ?></span>
                                        </div>
                                        <div class="progress target-bar">
                                            <div class="progress-bar"
                                                 style="width:<?php echo round(($t['total'] / $top_targets_max) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center py-3 small mb-0">No target data yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3">
                    <div class="card chart-card h-100">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-clock text-danger"></i> Busiest Hours
                            </h6>
                            <small class="text-muted">Activity today by hour</small>
                        </div>
                        <div class="card-body">
                            <div class="heatmap">
                                <?php
                                $max_hour = max(1, max($hourly));
                                for ($h = 0; $h < 24; $h++):
                                    $intensity = $hourly[$h] / $max_hour;
                                    $alpha     = $intensity > 0 ? 0.15 + ($intensity * 0.85) : 0.05;
                                ?>
                                    <div class="heat-cell"
                                         style="background: rgba(78,115,223,<?php echo $alpha; ?>);"
                                         title="<?php echo sprintf('%02d:00 — %d activities', $h, $hourly[$h]); ?>">
                                        <span><?php echo $h; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small text-muted">
                                <span>00:00</span>
                                <span>12:00</span>
                                <span>23:00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== RECENT POSTS + LAST 8 ACTIVITY (TIMELINE) ===== -->
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card feed-card h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-blog text-primary"></i> Latest Posts
                            </h6>
                            <a href="?section=blog" class="small font-weight-bold text-primary">
                                View all <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($recent_posts && mysqli_num_rows($recent_posts) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($p = mysqli_fetch_assoc($recent_posts)):
                                        $has_img = !empty($p['image']) && file_exists("uploads/{$p['image']}");
                                    ?>
                                        <a href="?section=blog&edit=<?php echo (int)$p['id']; ?>"
                                           class="list-group-item list-group-item-action feed-item">
                                            <?php if ($has_img): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($p['image']); ?>"
                                                     class="feed-thumb" alt="">
                                            <?php else: ?>
                                                <div class="feed-thumb feed-thumb-empty">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <div class="font-weight-bold text-truncate">
                                                    <?php echo htmlspecialchars($p['title']); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-circle"></i>
                                                    <?php echo htmlspecialchars($p['author']); ?>
                                                    &middot;
                                                    <?php echo date('M d, Y', strtotime($p['publication_date'])); ?>
                                                </small>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted ml-2"></i>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-mini">
                                    <i class="fas fa-blog"></i>
                                    <p class="text-muted small mb-2">No blog posts yet</p>
                                    <a href="?section=blog" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus"></i> Create First Post
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ===== VERTICAL TIMELINE ===== -->
                <div class="col-lg-6 mb-4">
                    <div class="card feed-card h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-bolt text-warning"></i> Last 8 Activities
                            </h6>
                            <a href="?section=activity_log" class="small font-weight-bold text-primary">
                                View all <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($recent_logs && mysqli_num_rows($recent_logs) > 0): ?>
                                <div class="vtl">
                                    <?php
                                    $total_logs = mysqli_num_rows($recent_logs);
                                    $idx = 0;
                                    while ($log = mysqli_fetch_assoc($recent_logs)):
                                        $idx++;
                                        $is_last = ($idx === $total_logs);

                                        $act = strtolower($log['action']);
                                        $icon = 'fa-bolt'; $color = '#4e73df'; $label = 'Activity';
                                        if ($act === 'created')      { $icon='fa-plus-circle';  $color='#1cc88a'; $label='Created'; }
                                        elseif ($act === 'updated')  { $icon='fa-edit';         $color='#dda20a'; $label='Updated'; }
                                        elseif ($act === 'deleted')  { $icon='fa-trash';        $color='#e74a3b'; $label='Deleted'; }
                                        elseif ($act === 'login')    { $icon='fa-sign-in-alt';  $color='#36b9cc'; $label='Login'; }
                                        elseif ($act === 'logout')   { $icon='fa-sign-out-alt'; $color='#5a5c69'; $label='Logout'; }

                                        $ts   = strtotime($log['created_at']);
                                        $diff = time() - $ts;
                                        if     ($diff < 60)     $rel = 'Just now';
                                        elseif ($diff < 3600)   $rel = floor($diff/60) . 'm ago';
                                        elseif ($diff < 86400)  $rel = floor($diff/3600) . 'h ago';
                                        elseif ($diff < 604800) $rel = floor($diff/86400) . 'd ago';
                                        else                    $rel = date('M d', $ts);

                                        $title = $log['target_title'] ?: ($log['target_type'] ?: 'System');
                                    ?>
                                        <div class="vtl-item <?php echo $is_last ? 'vtl-last' : ''; ?>">
                                            <div class="vtl-marker">
                                                <div class="vtl-dot" style="color:<?php echo $color; ?>; background:<?php echo $color; ?>;"></div>
                                            </div>
                                            <div class="vtl-content">
                                                <div class="vtl-header">
                                                    <span class="vtl-action" style="color:<?php echo $color; ?>;">
                                                        <i class="fas <?php echo $icon; ?>"></i>
                                                        <?php echo $label; ?>
                                                    </span>
                                                    <span class="vtl-time">
                                                        <i class="far fa-clock"></i> <?php echo $rel; ?>
                                                    </span>
                                                </div>
                                                <div class="vtl-title">
                                                    <?php echo htmlspecialchars($title); ?>
                                                </div>
                                                <div class="vtl-meta">
                                                    <span class="vtl-user">
                                                        <i class="fas fa-user-circle"></i>
                                                        <?php echo htmlspecialchars($log['user']); ?>
                                                    </span>
                                                    <span class="vtl-date">
                                                        <?php echo date('M d, g:i A', $ts); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-mini">
                                    <i class="fas fa-history"></i>
                                    <p class="text-muted small mb-0">No activity yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== QUICK ACTIONS ===== -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card chart-card">
                        <div class="card-header bg-white">
                            <h6 class="m-0 font-weight-bold text-gray-800">
                                <i class="fas fa-bolt text-warning"></i> Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="?section=blog" class="quick-action">
                                        <div class="qa-icon qa-primary"><i class="fas fa-plus-circle"></i></div>
                                        <div class="qa-label">Create Post</div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="?section=settings" class="quick-action">
                                        <div class="qa-icon qa-warning"><i class="fas fa-cog"></i></div>
                                        <div class="qa-label">Company Settings</div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="?section=activity_log" class="quick-action">
                                        <div class="qa-icon qa-success"><i class="fas fa-history"></i></div>
                                        <div class="qa-label">Activity Log</div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <a href="#" class="quick-action" onclick="event.preventDefault(); confirmLogout();">
                                        <div class="qa-icon qa-danger"><i class="fas fa-sign-out-alt"></i></div>
                                        <div class="qa-label">Logout</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* ===== HERO ===== */
    .hero-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 26px 30px;
        color: #fff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(102,126,234,.25);
    }
    .hero-card::before {
        content: ""; position: absolute; right: -80px; top: -80px;
        width: 260px; height: 260px;
        background: rgba(255,255,255,.08); border-radius: 50%;
    }
    .hero-card::after {
        content: ""; position: absolute; right: 60px; bottom: -100px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.06); border-radius: 50%;
    }
    .hero-content {
        position: relative; z-index: 1;
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 16px;
    }
    .hero-title { font-weight: 800; font-size: 1.65rem; letter-spacing: -.5px; }
    .hero-subtitle { opacity: .9; font-size: .95rem; }
    .hero-meta { opacity: .8; font-size: .78rem; }

    /* ===== KPI CARDS ===== */
    .kpi-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 3px 15px rgba(0,0,0,.06);
        transition: transform .25s ease, box-shadow .25s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 30px rgba(0,0,0,.12);
    }
    .kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }
    .kpi-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        color: #fff;
        box-shadow: 0 6px 15px rgba(0,0,0,.10);
    }
    .kpi-icon-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
    .kpi-icon-success { background: linear-gradient(135deg, #1cc88a, #13855c); }
    .kpi-icon-info    { background: linear-gradient(135deg, #36b9cc, #258391); }
    .kpi-icon-dark    { background: linear-gradient(135deg, #5a5c69, #373840); }

    .kpi-trend {
        font-size: 11px;
        font-weight: 700;
        padding: 4px 9px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .kpi-trend.up   { background: #e3f9ee; color: #1cc88a; }
    .kpi-trend.down { background: #fdeceb; color: #e74a3b; }

    .kpi-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: .4px;
        background: #e7efff;
        color: #4e73df;
    }
    .kpi-badge.dark { background: #ececec; color: #5a5c69; }

    .kpi-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: #3a3b45;
        line-height: 1;
        margin-bottom: 6px;
    }
    .kpi-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-weight: 700;
        color: #858796;
        margin-bottom: 12px;
    }
    .kpi-spark {
        height: 40px;
        margin-bottom: 12px;
        flex-grow: 1;
    }
    .kpi-ring-wrap { margin-bottom: 12px; }
    .kpi-progress { height: 8px; border-radius: 6px; background: #eef0f5; margin-bottom: 6px; }
    .kpi-progress .progress-bar { border-radius: 6px; }
    .kpi-footer {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: #6c757d;
        padding-top: 12px;
        border-top: 1px dashed #e3e6f0;
        margin-top: auto;
    }

    /* ===== CHART CARD ===== */
    .chart-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 3px 15px rgba(0,0,0,.05);
    }
    .chart-card .card-header {
        border-bottom: 1px solid #e3e6f0;
        padding: 16px 22px;
        border-radius: 14px 14px 0 0 !important;
    }
    .chart-card .card-body { padding: 20px; }

    /* ===== GOAL RING ===== */
    .goal-ring-wrap {
        position: relative;
        width: 180px;
        height: 180px;
        margin: 0 auto;
    }
    .goal-ring { width: 100%; height: 100%; }
    .goal-ring-label {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .goal-value {
        font-size: 2rem;
        font-weight: 800;
        color: #3a3b45;
        line-height: 1;
    }
    .goal-value small { font-size: 1rem; opacity: .6; }
    .goal-sub { font-size: 12px; color: #858796; margin-top: 4px; }

    /* ===== CONTRIBUTORS ===== */
    .contributor-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .contributor-medal {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        flex-shrink: 0;
    }
    .contributor-bar { height: 6px; border-radius: 4px; background: #eef0f5; }
    .contributor-bar .progress-bar { border-radius: 4px; }

    /* ===== TARGETS ===== */
    .target-row { margin-bottom: 14px; }
    .target-bar { height: 6px; border-radius: 4px; background: #eef0f5; }
    .target-bar .progress-bar {
        background: linear-gradient(90deg, #36b9cc, #258391);
        border-radius: 4px;
    }

    /* ===== HEATMAP ===== */
    .heatmap {
        display: grid;
        grid-template-columns: repeat(24, 1fr);
        gap: 3px;
    }
    .heat-cell {
        aspect-ratio: 1;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        color: #3a3b45;
        font-weight: 600;
        transition: transform .15s ease;
        cursor: pointer;
    }
    .heat-cell:hover { transform: scale(1.2); z-index: 2; }
    .heat-cell span { opacity: .7; }

    /* ===== FEED ===== */
    .feed-card {
        border: none; border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,.05);
    }
    .feed-card .card-header {
        border-bottom: 1px solid #e3e6f0;
        padding: 16px 22px;
    }
    .feed-item { padding: 14px 20px; border-color: #f1f3f9; transition: background .15s ease; }
    .feed-item:hover { background: #f8f9fc; }
    .feed-thumb {
        width: 46px; height: 46px;
        object-fit: cover;
        border-radius: 10px;
        margin-right: 14px;
        flex-shrink: 0;
        background: #f1f3f9;
    }
    .feed-thumb-empty {
        display: flex; align-items: center; justify-content: center;
        color: #c1c5d0; font-size: 18px;
    }

    /* ===== VERTICAL TIMELINE ===== */
    .vtl {
        position: relative;
        padding: 6px 0;
    }
    .vtl-item {
        position: relative;
        display: flex;
        padding-bottom: 22px;
    }
    .vtl-item.vtl-last { padding-bottom: 0; }
    .vtl-item.vtl-last .vtl-marker::after { display: none; }

    .vtl-marker {
        position: relative;
        width: 34px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
    }
    .vtl-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        margin-top: 4px;
        position: relative;
        z-index: 2;
        box-shadow: 0 0 0 4px #fff, 0 0 0 5px currentColor;
        transition: transform .15s ease;
    }
    .vtl-marker::after {
        content: "";
        position: absolute;
        top: 22px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: calc(100% + 0px);
        background: linear-gradient(to bottom, #d1d3e2, #eef0f5);
        border-radius: 2px;
        z-index: 1;
    }

    .vtl-content {
        flex-grow: 1;
        min-width: 0;
        padding-left: 6px;
    }
    .vtl-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }
    .vtl-action {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .vtl-action i { font-size: 11px; }
    .vtl-time {
        font-size: 11px;
        color: #858796;
        font-weight: 500;
        white-space: nowrap;
    }
    .vtl-title {
        font-size: 14px;
        font-weight: 600;
        color: #3a3b45;
        line-height: 1.35;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .vtl-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 11px;
        color: #858796;
    }
    .vtl-user i { margin-right: 3px; }
    .vtl-date { font-style: italic; }

    .vtl-item:hover .vtl-title { color: #4e73df; }
    .vtl-item:hover .vtl-dot   { transform: scale(1.15); }

    /* ===== QUICK ACTIONS ===== */
    .quick-action {
        display: block;
        padding: 20px 12px;
        border-radius: 14px;
        text-decoration: none !important;
        transition: all .25s ease;
        background: #f8f9fc;
    }
    .quick-action:hover {
        background: #fff;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,.08);
    }
    .qa-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #fff;
        margin-bottom: 10px;
        box-shadow: 0 6px 15px rgba(0,0,0,.10);
    }
    .qa-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
    .qa-warning { background: linear-gradient(135deg, #f6c23e, #dda20a); }
    .qa-success { background: linear-gradient(135deg, #1cc88a, #13855c); }
    .qa-danger  { background: linear-gradient(135deg, #e74a3b, #be2617); }
    .qa-label { font-size: 13px; font-weight: 600; color: #3a3b45; }

    /* ===== EMPTY MINI ===== */
    .empty-mini { padding: 40px 20px; text-align: center; }
    .empty-mini i {
        font-size: 42px; color: #d1d3e2;
        display: block; margin-bottom: 12px;
    }

    @media (max-width: 767.98px) {
        .hero-card { padding: 20px; }
        .hero-title { font-size: 1.3rem; }
        .hero-actions { width: 100%; }
        .heatmap { grid-template-columns: repeat(12, 1fr); }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function () {

    /* ===== 14-DAY ACTIVITY LINE ===== */
    const lineCtx = document.getElementById('activityChart');
    if (lineCtx) {
        const ctx = lineCtx.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 260);
        grad.addColorStop(0, 'rgba(78,115,223,.35)');
        grad.addColorStop(1, 'rgba(78,115,223,0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels_14); ?>,
                datasets: [{
                    label: 'Activities',
                    data: <?php echo json_encode($chart_data_14); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: grad,
                    borderWidth: 3,
                    fill: true,
                    tension: .4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#4e73df',
                    pointBorderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#3a3b45', padding: 12, cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#858796', font: { size: 11 } },
                        grid: { color: '#f1f3f9', drawBorder: false }
                    },
                    x: {
                        ticks: { color: '#858796', font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    /* ===== POSTS BAR CHART ===== */
    const barCtx = document.getElementById('postsBarChart');
    if (barCtx) {
        new Chart(barCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($month_labels); ?>,
                datasets: [{
                    label: 'Posts',
                    data: <?php echo json_encode($month_data); ?>,
                    backgroundColor: [
                        'rgba(78,115,223,.7)',
                        'rgba(28,200,138,.7)',
                        'rgba(246,194,62,.7)',
                        'rgba(54,185,204,.7)',
                        'rgba(231,74,59,.7)',
                        'rgba(90,92,105,.7)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 55
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#3a3b45', padding: 12, cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, color: '#858796', font: { size: 11 } },
                        grid: { color: '#f1f3f9', drawBorder: false }
                    },
                    x: {
                        ticks: { color: '#858796', font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    /* ===== ACTION DOUGHNUT ===== */
    const actCtx = document.getElementById('actionChart');
    if (actCtx) {
        new Chart(actCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Created', 'Updated', 'Deleted', 'Other'],
                datasets: [{
                    data: [
                        <?php echo $created_count; ?>,
                        <?php echo $updated_count; ?>,
                        <?php echo $deleted_count; ?>,
                        <?php echo $other_count; ?>
                    ],
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#4e73df'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10, padding: 12,
                            font: { size: 11 }, usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: '#3a3b45', padding: 10, cornerRadius: 8
                    }
                }
            }
        });
    }

    /* ===== KPI SPARKLINE — POSTS ===== */
    const sparkPosts = document.getElementById('sparkPosts');
    if (sparkPosts) {
        new Chart(sparkPosts.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($month_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($month_data); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78,115,223,.15)',
                    borderWidth: 2,
                    fill: true,
                    tension: .4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });
    }

    /* ===== KPI SPARKLINE — ACTIVITY ===== */
    const sparkAct = document.getElementById('sparkActivity');
    if (sparkAct) {
        new Chart(sparkAct.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels_14); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_data_14); ?>,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28,200,138,.15)',
                    borderWidth: 2,
                    fill: true,
                    tension: .4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } }
            }
        });
    }
});

/* ===== CONFIRM LOGOUT ===== */
function confirmLogout() {
    Swal.fire({
        title: 'Ready to leave?',
        text: 'You will be signed out of the admin panel.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#858796',
        confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Yes, logout',
        cancelButtonText: 'Stay',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) {
            window.location.href = '?section=logout';
        }
    });
}
</script>