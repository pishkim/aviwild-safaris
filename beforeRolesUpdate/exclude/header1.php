<?php 
//session_start();
// include('./include/header.php');

// Database connection
include('./config.php');

// ===== GET TOTAL POSTS =====
$total_posts = 0;
$query = "SELECT COUNT(*) as total FROM blog_posts";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_posts = $row['total'];
}

// ===== GET PUBLISHED POSTS =====
$published_posts = 0;
$query = "SELECT COUNT(*) as published FROM blog_posts WHERE status = 'published' OR status IS NULL";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $published_posts = $row['published'];
}

// ===== GET DRAFT POSTS =====
$draft_posts = 0;
$query = "SELECT COUNT(*) as drafts FROM blog_posts WHERE status = 'draft'";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $draft_posts = $row['drafts'];
}

// ===== GET TOTAL VIEWS =====
$total_views = 0;
$query = "SELECT SUM(views) as total_views FROM blog_posts";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_views = $row['total_views'] ?? 0;
}

// ===== GET RECENT POSTS =====
$recent_posts = [];
$query = "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result && mysqli_num_rows($result) > 0) {
    $recent_posts = $result;
}

// ===== GET POSTS BY MONTH =====
$months = [];
$counts = [];
$query = "SELECT DATE_FORMAT(created_at, '%b') as month, COUNT(*) as count 
          FROM blog_posts 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY MONTH(created_at) 
          ORDER BY MONTH(created_at)";
$monthly_data = mysqli_query($conn, $query);
if ($monthly_data && mysqli_num_rows($monthly_data) > 0) {
    while ($row = mysqli_fetch_assoc($monthly_data)) {
        $months[] = $row['month'];
        $counts[] = $row['count'];
    }
} else {
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $counts = [0, 0, 0, 0, 0, 0];
}

$max_count = !empty($counts) ? max($counts) : 1;
?>



<!-- Page Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Posts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_posts; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-blog fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Published</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $published_posts; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Drafts</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $draft_posts; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-pencil-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Views</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_views); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-eye fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row - Simple CSS Bars -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">📈 Posts Per Month</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area" style="height: 250px; padding-top: 20px;">
                                <?php if (array_sum($counts) > 0): ?>
                                    <div class="d-flex align-items-end justify-content-around h-100" style="gap: 10px;">
                                        <?php foreach ($counts as $index => $count): ?>
                                            <?php 
                                            $height = ($max_count > 0) ? ($count / $max_count) * 200 : 0;
                                            $color = $count > 0 ? '#4e73df' : '#e2e3e5';
                                            ?>
                                            <div class="d-flex flex-column align-items-center" style="flex: 1; height: 100%;">
                                                <div class="position-relative" style="width: 100%; max-width: 60px; height: 200px; display: flex; flex-direction: column; justify-content: flex-end;">
                                                    <div style="background: <?php echo $color; ?>; 
                                                          height: <?php echo max($height, 5); ?>px; 
                                                          width: 100%;
                                                          border-radius: 4px 4px 0 0; 
                                                          transition: height 0.5s ease;
                                                          position: relative;">
                                                        <?php if ($count > 0): ?>
                                                            <span style="position: absolute; top: -22px; left: 50%; transform: translateX(-50%); 
                                                                  font-size: 13px; font-weight: 700; color: #333;">
                                                                <?php echo $count; ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-center">
                                                    <span style="font-size: 12px; color: #858796; font-weight: 600;">
                                                        <?php echo isset($months[$index]) ? $months[$index] : ''; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No data available yet. Create your first post!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Doughnut (CSS) -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">📊 Post Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center" style="height: 200px; padding: 10px;">
                                <?php 
                                $total = $published_posts + $draft_posts;
                                if ($total > 0):
                                    $published_percent = ($published_posts / $total) * 100;
                                    $draft_percent = ($draft_posts / $total) * 100;
                                ?>
                                <div class="position-relative" style="width: 180px; height: 180px;">
                                    <!-- Simple CSS Doughnut using SVG -->
                                    <svg viewBox="0 0 120 120" style="transform: rotate(-90deg); width: 100%; height: 100%;">
                                        <!-- Background circle -->
                                        <circle cx="60" cy="60" r="50" fill="none" 
                                                stroke="#e9ecef" stroke-width="18"/>
                                        <!-- Published -->
                                        <circle cx="60" cy="60" r="50" fill="none" 
                                                stroke="#1cc88a" stroke-width="18"
                                                stroke-dasharray="<?php echo $published_percent * 3.14; ?> 314"
                                                stroke-linecap="round"/>
                                        <!-- Draft -->
                                        <circle cx="60" cy="60" r="50" fill="none" 
                                                stroke="#f6c23e" stroke-width="18"
                                                stroke-dasharray="<?php echo $draft_percent * 3.14; ?> 314"
                                                stroke-dashoffset="<?php echo -($published_percent * 3.14); ?>"
                                                stroke-linecap="round"/>
                                    </svg>
                                    <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                        <div class="h3 mb-0 font-weight-bold text-gray-800"><?php echo $total; ?></div>
                                        <div class="small text-muted">Total Posts</div>
                                    </div>
                                </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <i class="fas fa-circle fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3 text-center small">
                                <span class="mr-3">
                                    <i class="fas fa-circle text-success"></i> Published (<?php echo $published_posts; ?>)
                                </span>
                                <span>
                                    <i class="fas fa-circle text-warning"></i> Drafts (<?php echo $draft_posts; ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Posts Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">📝 Recent Blog Posts</h6>
                            <a href="blog.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recent_posts && mysqli_num_rows($recent_posts) > 0): ?>
                                            <?php 
                                            $counter = 1;
                                            while ($post = mysqli_fetch_assoc($recent_posts)): 
                                            ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-user-circle text-primary"></i>
                                                        <?php echo htmlspecialchars($post['author']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo date('M d, Y', strtotime($post['publication_date'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $status = isset($post['status']) ? $post['status'] : 'published';
                                                        if ($status == 'published'): 
                                                        ?>
                                                            <span class="badge badge-success">Published</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">Draft</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="blog.php?edit=<?php echo $post['id']; ?>" 
                                                               class="btn btn-warning" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button class="btn btn-info" title="View" 
                                                                    data-toggle="modal" data-target="#viewModal<?php echo $post['id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <a href="javascript:void(0)" 
                                                               class="btn btn-danger" title="Delete"
                                                               onclick="confirmDelete(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars($post['title']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-blog fa-3x text-muted mb-3 d-block"></i>
                                                    <h6>No posts yet</h6>
                                                    <p class="text-muted">Create your first blog post!</p>
                                                    <a href="blog.php" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-plus"></i> Create Post
                                                    </a>
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
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->

</div>
<!-- End of Content Wrapper -->

<!-- View Modals for Recent Posts -->
<?php 
if ($recent_posts && mysqli_num_rows($recent_posts) > 0):
    mysqli_data_seek($recent_posts, 0);
    while ($post = mysqli_fetch_assoc($recent_posts)): 
?>
<div class="modal fade" id="viewModal<?php echo $post['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white"><?php echo htmlspecialchars($post['title']); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if ($post['image'] != ''): ?>
                    <div class="text-center mb-4">
                        <img src="uploads/<?php echo $post['image']; ?>" class="img-fluid rounded" style="max-height: 300px;">
                    </div>
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-user"></i> Author:</strong>
                        <p><?php echo htmlspecialchars($post['author']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-calendar"></i> Date:</strong>
                        <p><?php echo date('F d, Y', strtotime($post['publication_date'])); ?></p>
                    </div>
                </div>
                <div class="mb-3">
                    <strong><i class="fas fa-paragraph"></i> Introduction:</strong>
                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($post['introduction'])); ?></p>
                </div>
                <?php if ($post['call_to_action'] != ''): ?>
                    <div class="text-center mt-4">
                        <a href="#" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-right"></i> 
                            <?php echo htmlspecialchars($post['call_to_action']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php 
    endwhile;
endif; 
?>

<script>
// ===== CONFIRM DELETE WITH SWEETALERT =====
function confirmDelete(id, title) {
    Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete "<strong>${title}</strong>"`,
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'blog.php?delete=' + id;
        }
    });
}
</script>

