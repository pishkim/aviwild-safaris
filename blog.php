<?php
include 'config.php';
require_once 'functions.php';   // ⭐ provides log_create / log_update / log_delete

// Protect the handlers:
if (isset($_POST['create_post'])) { require_permission($conn, 'blog.create'); }
if (isset($_POST['update_post'])) { require_permission($conn, 'blog.edit'); }
if (isset($_GET['delete']))       { require_permission($conn, 'blog.delete');}


/* =========================================================
   CONSTANT — max upload size (10 MB)
========================================================= */
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);   // 10 MB
define('MAX_UPLOAD_LABEL', '10MB');

// ============ CREATE ============
if (isset($_POST['create_post'])) {
    $title        = mysqli_real_escape_string($conn, $_POST['title']);
    $author       = mysqli_real_escape_string($conn, $_POST['author']);
    $pub_date     = mysqli_real_escape_string($conn, $_POST['pub_date']);
    $introduction = mysqli_real_escape_string($conn, $_POST['introduction']);
    $cta          = mysqli_real_escape_string($conn, $_POST['cta']);
    $status       = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'published';

    // Handle image upload
    $image_name = '';
    if (!empty($_FILES['image']['name'])) {

        // ⭐ Server-side size check
        if ($_FILES['image']['size'] > MAX_UPLOAD_BYTES) {
            $_SESSION['message']      = "Image is too large. Max " . MAX_UPLOAD_LABEL . " allowed.";
            $_SESSION['message_type'] = "error";
            $_SESSION['message_icon'] = "error";
            echo '<script>window.location.href = "?section=blog";</script>';
            exit();
        }

        $target_dir = "uploads/";
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }

    $query = "INSERT INTO blog_posts (title, image, author, publication_date, introduction, call_to_action, status)
              VALUES ('$title', '$image_name', '$author', '$pub_date', '$introduction', '$cta', '$status')";
    if (mysqli_query($conn, $query)) {

        // ⭐ LOG ACTIVITY
        log_create(
            $conn,
            'Blog Post',
            $title,
            mysqli_insert_id($conn),
            "New post by $author ({$status})"
        );

        $_SESSION['message']      = "Post created successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error creating post!";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    echo '<script>window.location.href = "?section=blog";</script>';
    exit();
}

// ============ UPDATE ============
if (isset($_POST['update_post'])) {
    $id           = mysqli_real_escape_string($conn, $_POST['post_id']);
    $title        = mysqli_real_escape_string($conn, $_POST['title']);
    $author       = mysqli_real_escape_string($conn, $_POST['author']);
    $pub_date     = mysqli_real_escape_string($conn, $_POST['pub_date']);
    $introduction = mysqli_real_escape_string($conn, $_POST['introduction']);
    $cta          = mysqli_real_escape_string($conn, $_POST['cta']);
    $status       = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'published';

    // Get current image + old title (for log)
    $query = "SELECT title, image FROM blog_posts WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $current_image = $row['image'] ?? '';
    $old_title     = $row['title'] ?? 'Unknown';

    // Handle new image upload
    if (!empty($_FILES['image']['name'])) {

        // ⭐ Server-side size check
        if ($_FILES['image']['size'] > MAX_UPLOAD_BYTES) {
            $_SESSION['message']      = "Image is too large. Max " . MAX_UPLOAD_LABEL . " allowed.";
            $_SESSION['message_type'] = "error";
            $_SESSION['message_icon'] = "error";
            echo '<script>window.location.href = "?section=blog";</script>';
            exit();
        }

        // Delete old image
        if ($current_image != '' && file_exists("uploads/$current_image")) {
            unlink("uploads/$current_image");
        }
        // Upload new image
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = "uploads/" . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    } else {
        $image_name = $current_image;
    }

    $query = "UPDATE blog_posts SET
              title = '$title',
              image = '$image_name',
              author = '$author',
              publication_date = '$pub_date',
              introduction = '$introduction',
              call_to_action = '$cta',
              status = '$status'
              WHERE id = $id";
    if (mysqli_query($conn, $query)) {

        // ⭐ LOG ACTIVITY
        $detail = ($old_title !== $title)
            ? "Renamed from '$old_title' to '$title'"
            : "Updated post by $author";

        log_update(
            $conn,
            'Blog Post',
            $title,
            $id,
            $detail
        );

        $_SESSION['message']      = "Post updated successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error updating post!";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    echo '<script>window.location.href = "?section=blog";</script>';
    exit();
}

// ============ DELETE ============
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    // ⭐ Fetch title + image BEFORE deleting (so we can log the title)
    $query  = "SELECT title, image FROM blog_posts WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $row    = mysqli_fetch_assoc($result);
    $del_title = $row['title'] ?? 'Unknown';

    // ⭐ LOG BEFORE the row disappears
    log_delete(
        $conn,
        'Blog Post',
        $del_title,
        $id,
        "Post permanently deleted"
    );

    // Delete the image file
    if (!empty($row['image']) && file_exists("uploads/" . $row['image'])) {
        unlink("uploads/" . $row['image']);
    }

    $query = "DELETE FROM blog_posts WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message']      = "Post deleted successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message']      = "Error deleting post!";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    echo '<script>window.location.href = "?section=blog";</script>';
    exit();
}

// ============ FETCH POST FOR EDIT ============
$edit_post = null;
if (isset($_GET['edit'])) {
    $id = mysqli_real_escape_string($conn, $_GET['edit']);
    $query = "SELECT * FROM blog_posts WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $edit_post = mysqli_fetch_assoc($result);
}

// ============ FETCH ALL POSTS ============
$query = "SELECT * FROM blog_posts ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$total_posts = mysqli_num_rows($result);

// Get message from session
$message      = $_SESSION['message']      ?? '';
$message_type = $_SESSION['message_type'] ?? '';
$message_icon = $_SESSION['message_icon'] ?? '';

if (!empty($message)) {
    unset($_SESSION['message'], $_SESSION['message_type'], $_SESSION['message_icon']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .image-preview-container {
            border: 2px dashed #d1d3e2;
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fc;
            transition: all 0.3s ease;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .image-preview-container img {
            max-width: 100%;
            max-height: 250px;
            border-radius: 8px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }
        .image-preview-placeholder {
            text-align: center;
            color: #858796;
        }
        .image-preview-placeholder i {
            font-size: 60px;
            margin-bottom: 15px;
            display: block;
            color: #d1d3e2;
        }
        .file-info { font-size: 0.85rem; margin-top: 5px; }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }
        .preview-animate { animation: fadeIn 0.3s ease; }
        .table-image {
            width: 50px; height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .btn-group-actions .btn { margin: 0 2px; }
        .modal-header-gradient-create {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        .modal-header-gradient-edit {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        }
        .edit-mode-badge {
            background: #f6c23e;
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Blog Management</h1>
                        <div>
                            <?php if ($edit_post): ?>
                                <span class="edit-mode-badge mr-2">
                                    <i class="fas fa-edit"></i> Editing: <?php echo htmlspecialchars($edit_post['title']); ?>
                                </span>
                                <a href="?section=blog" class="btn btn-sm btn-secondary shadow-sm">
                                    <i class="fas fa-times"></i> Cancel Edit
                                </a>
                            <?php else: ?>
                                <?php if (has_permission($conn, 'blog.create')): ?>
                                    <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"
                                            data-toggle="modal" data-target="#createPostModal">
                                        <i class="fas fa-plus fa-sm text-white-50"></i> Create New Post
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ===== EDIT FORM ===== -->
                    <?php if ($edit_post): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fas fa-edit"></i> Edit Blog Post
                            </h6>
                        </div>
                        <div class="card-body">
                            <form action="?section=blog" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">

                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Blog Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="title"
                                                   value="<?php echo htmlspecialchars($edit_post['title']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Author Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="author"
                                                   value="<?php echo htmlspecialchars($edit_post['author']); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Publication Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="pub_date"
                                                   value="<?php echo $edit_post['publication_date']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Call to Action</label>
                                            <input type="text" class="form-control" name="cta"
                                                   value="<?php echo htmlspecialchars($edit_post['call_to_action']); ?>"
                                                   placeholder="e.g., Read Full Story, Sign Up Now">
                                        </div>
                                        <div class="form-group">
                                            <label>Status <span class="text-danger">*</span></label>
                                            <div class="d-flex">
                                                <?php $edit_status = $edit_post['status'] ?? 'published'; ?>
                                                <div class="custom-control custom-radio mr-4">
                                                    <input type="radio" id="edit_status_published" name="status"
                                                           class="custom-control-input" value="published"
                                                           <?php echo $edit_status == 'published' ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label text-success" for="edit_status_published">
                                                        <i class="fas fa-check-circle"></i> Published
                                                    </label>
                                                </div>
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="edit_status_draft" name="status"
                                                           class="custom-control-input" value="draft"
                                                           <?php echo $edit_status == 'draft' ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label text-warning" for="edit_status_draft">
                                                        <i class="fas fa-pencil-alt"></i> Draft
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label>Featured Image</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" name="image"
                                                       accept="image/*" id="editImageInput">
                                                <label class="custom-file-label" for="editImageInput">
                                                    <i class="fas fa-cloud-upload-alt"></i> Choose image...
                                                </label>
                                            </div>

                                            <?php if ($edit_post['image'] != ''): ?>
                                                <div class="mt-3 text-center">
                                                    <img src="uploads/<?php echo $edit_post['image']; ?>"
                                                         class="img-fluid img-thumbnail" style="max-height: 200px;">
                                                    <p class="text-muted small mt-1">Current image (upload new to replace)</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="image-preview-container mt-3">
                                                    <div class="image-preview-placeholder">
                                                        <i class="fas fa-image"></i>
                                                        <p>No image selected</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <small class="text-muted">Supported formats: JPG, PNG, GIF, WEBP (Max <?php echo MAX_UPLOAD_LABEL; ?>)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Introduction <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="introduction" rows="5" required><?php echo htmlspecialchars($edit_post['introduction']); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <hr>
                                        <button type="submit" name="update_post" class="btn btn-warning btn-lg">
                                            <i class="fas fa-save"></i> Update Post
                                        </button>
                                        <a href="?section=blog" class="btn btn-secondary btn-lg">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ===== BLOG POSTS LIST ===== -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">All Blog Posts</h6>
                            <span class="badge badge-primary"><?php echo $total_posts; ?> Posts</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="8%">Image</th>
                                            <th width="17%">Title</th>
                                            <th width="10%">Author</th>
                                            <th width="10%">Date</th>
                                            <th width="22%">Introduction</th>
                                            <th width="7%">CTA</th>
                                            <th width="9%">Status</th>
                                            <th width="12%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($total_posts > 0): ?>
                                            <?php $counter = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td>
                                                        <?php if ($row['image'] != ''): ?>
                                                            <img src="uploads/<?php echo $row['image']; ?>"
                                                                 class="table-image" alt="Blog image">
                                                        <?php else: ?>
                                                            <span class="text-muted">No image</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar-alt"></i>
                                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-user-circle text-primary"></i>
                                                        <?php echo htmlspecialchars($row['author']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            <?php echo date('M d, Y', strtotime($row['publication_date'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $intro = htmlspecialchars($row['introduction']);
                                                        echo strlen($intro) > 80 ? substr($intro, 0, 80) . '...' : $intro;
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['call_to_action'] != ''): ?>
                                                            <span class="badge badge-success">
                                                                <?php echo htmlspecialchars($row['call_to_action']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php $status = $row['status'] ?? 'published'; ?>
                                                        <?php if ($status == 'published'): ?>
                                                            <span class="badge badge-success">
                                                                <i class="fas fa-check-circle"></i> Published
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">
                                                                <i class="fas fa-pencil-alt"></i> Draft
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group-actions">
                                                            <a href="?section=blog&edit=<?php echo $row['id']; ?>"
                                                               class="btn btn-sm btn-warning" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-info" title="View"
                                                                    data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <a href="javascript:void(0)"
                                                               class="btn btn-sm btn-danger" title="Delete"
                                                               onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <i class="fas fa-blog fa-3x text-muted mb-3 d-block"></i>
                                                    <h6>No blog posts found</h6>
                                                    <p class="text-muted">Create your first blog post using the "Create New Post" button above.</p>
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
    </div>

    <!-- ===== CREATE POST MODAL ===== -->
    <div class="modal fade" id="createPostModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal-header-gradient-create">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-plus-circle"></i> Create New Blog Post
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="?section=blog" method="POST" enctype="multipart/form-data" id="createForm">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Blog Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title"
                                           placeholder="Enter blog title" required>
                                </div>
                                <div class="form-group">
                                    <label>Author Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="author"
                                           placeholder="Enter author name" required>
                                </div>
                                <div class="form-group">
                                    <label>Publication Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="pub_date"
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Call to Action</label>
                                    <input type="text" class="form-control" name="cta"
                                           placeholder="e.g., Read Full Story, Sign Up Now">
                                </div>
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <div class="d-flex">
                                        <div class="custom-control custom-radio mr-4">
                                            <input type="radio" id="create_status_published" name="status"
                                                   class="custom-control-input" value="published" checked>
                                            <label class="custom-control-label text-success" for="create_status_published">
                                                <i class="fas fa-check-circle"></i> Published
                                            </label>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="create_status_draft" name="status"
                                                   class="custom-control-input" value="draft">
                                            <label class="custom-control-label text-warning" for="create_status_draft">
                                                <i class="fas fa-pencil-alt"></i> Draft
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Featured Image</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="image"
                                               accept="image/*" id="createImageInput">
                                        <label class="custom-file-label" for="createImageInput">
                                            <i class="fas fa-cloud-upload-alt"></i> Choose image...
                                        </label>
                                    </div>

                                    <div class="image-preview-container mt-3" id="createImagePreviewContainer">
                                        <div class="image-preview-placeholder">
                                            <i class="fas fa-image"></i>
                                            <p>No image selected</p>
                                            <small class="text-muted">Click "Choose image" to upload</small>
                                        </div>
                                    </div>

                                    <small class="text-muted">Supported formats: JPG, PNG, GIF, WEBP (Max <?php echo MAX_UPLOAD_LABEL; ?>)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Introduction <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="introduction" rows="5"
                                              placeholder="Write your blog introduction here..." required></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <button type="submit" name="create_post" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Publish Post
                                </button>
                                <button type="reset" class="btn btn-outline-secondary btn-lg" onclick="resetCreateForm()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== VIEW MODALS ===== -->
    <?php
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)):
    ?>
    <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="modal-title text-white"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <?php if ($row['image'] != ''): ?>
                        <div class="text-center mb-4">
                            <img src="uploads/<?php echo $row['image']; ?>" class="img-fluid rounded" style="max-height: 300px;">
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong><i class="fas fa-user"></i> Author:</strong>
                            <p><?php echo htmlspecialchars($row['author']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fas fa-calendar"></i> Date:</strong>
                            <p><?php echo date('F d, Y', strtotime($row['publication_date'])); ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fas fa-info-circle"></i> Status:</strong>
                            <p>
                                <?php $status = $row['status'] ?? 'published'; ?>
                                <?php if ($status == 'published'): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> Published
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-pencil-alt"></i> Draft
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong><i class="fas fa-paragraph"></i> Introduction:</strong>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($row['introduction'])); ?></p>
                    </div>

                    <?php if ($row['call_to_action'] != ''): ?>
                        <div class="text-center mt-4">
                            <a href="#" class="btn btn-primary btn-lg">
                                <?php echo htmlspecialchars($row['call_to_action']); ?>
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
    <?php endwhile; ?>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script>
        /* ===== MAX UPLOAD SIZE — read from PHP so JS matches server ===== */
        const MAX_UPLOAD = <?php echo MAX_UPLOAD_BYTES; ?>;   // 10 MB in bytes
        const MAX_LABEL  = '<?php echo MAX_UPLOAD_LABEL; ?>';

        $(document).ready(function () {
            /* ===== DATATABLES ===== */
            $('#dataTable').DataTable({
                "order": [[0, "asc"]],
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                "language": {
                    "search": "Search posts:",
                    "lengthMenu": "Show _MENU_ posts per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ posts",
                    "infoEmpty": "No posts available",
                    "infoFiltered": "(filtered from _MAX_ total posts)",
                    "zeroRecords": "No matching posts found"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [1, 8] },
                    { "searchable": false, "targets": [1, 8] }
                ]
            });

            /* ===== UNIVERSAL IMAGE PREVIEW + VALIDATION ===== */
            function bindImagePreview(inputSelector, previewContainerSelector) {
                $(document).on('change', inputSelector, function () {
                    const file = this.files[0];
                    if (!file) return;

                    const validTypes = ['image/jpeg','image/png','image/gif','image/webp'];
                    if (!validTypes.includes(file.type)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid File',
                            text: 'Please select a valid image file (JPG, PNG, GIF, or WEBP)',
                            confirmButtonColor: '#e74a3b'
                        });
                        $(this).val('');
                        return;
                    }

                    // ⭐ 10MB check
                    if (file.size > MAX_UPLOAD) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'Image size must be less than ' + MAX_LABEL + '.',
                            confirmButtonColor: '#e74a3b'
                        });
                        $(this).val('');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $(inputSelector).next('.custom-file-label').html(
                            '<i class="fas fa-check-circle text-success"></i> ' + file.name
                        );

                        const previewHTML = `
                            <div class="position-relative w-100 text-center preview-animate">
                                <img src="${e.target.result}"
                                     alt="Preview image"
                                     style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                                <div class="file-info mt-2">
                                    <span class="badge badge-success">New image</span>
                                    <span class="text-muted ml-2">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                                </div>
                            </div>
                        `;

                        if ($(previewContainerSelector).length) {
                            $(previewContainerSelector).html(previewHTML);
                        } else {
                            // If no dedicated container, append below the file input
                            const tempId = inputSelector.replace('#', '') + 'Preview';
                            if (!$('#' + tempId).length) {
                                $(inputSelector).closest('.form-group').append('<div id="' + tempId + '" class="mt-3"></div>');
                            }
                            $('#' + tempId).html(previewHTML);
                        }
                    };
                    reader.readAsDataURL(file);
                });
            }

            bindImagePreview('#createImageInput', '#createImagePreviewContainer');
            bindImagePreview('#editImageInput',   '#editImagePreviewContainer');
        });

        /* ===== CONFIRM DELETE ===== */
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
                    window.location.href = '?section=blog&delete=' + id;
                }
            });
        }

        /* ===== FLASH MESSAGE ===== */
        <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: '<?php echo $message_icon; ?>',
                title: '<?php echo $message_type == "success" ? "Success!" : "Error!"; ?>',
                text: '<?php echo htmlspecialchars($message, ENT_QUOTES); ?>',
                confirmButtonColor: '<?php echo $message_type == "success" ? "#1cc88a" : "#e74a3b"; ?>',
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        });
        <?php endif; ?>

        /* ===== RESET CREATE FORM ===== */
        function resetCreateForm() {
            $('#createImageInput').val('');
            $('#createImageInput').next('.custom-file-label').html('<i class="fas fa-cloud-upload-alt"></i> Choose image...');

            const placeholderHTML = `
                <div class="image-preview-placeholder">
                    <i class="fas fa-image"></i>
                    <p>No image selected</p>
                    <small class="text-muted">Click "Choose image" to upload</small>
                </div>
            `;
            $('#createImagePreviewContainer').html(placeholderHTML);
            $('#createForm')[0].reset();
        }
    </script>
    <script src="sweetalert.js"></script>
</body>
</html>