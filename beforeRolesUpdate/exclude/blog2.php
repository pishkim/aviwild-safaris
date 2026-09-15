<?php
// Start session for messages
session_start();

 include('./include/header.php');
 include('./include/navbar.php');

include 'config.php';

// ============ CREATE ============
if (isset($_POST['create_post'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $pub_date = mysqli_real_escape_string($conn, $_POST['pub_date']);
    $introduction = mysqli_real_escape_string($conn, $_POST['introduction']);
    $cta = mysqli_real_escape_string($conn, $_POST['cta']);
    
    // Handle image upload
    $image_name = '';
    if ($_FILES['image']['name'] != '') {
        $target_dir = "uploads/";
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    }
    
    $query = "INSERT INTO blog_posts (title, image, author, publication_date, introduction, call_to_action) 
              VALUES ('$title', '$image_name', '$author', '$pub_date', '$introduction', '$cta')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Post created successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message'] = "Error creating post!";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    header("Location: blog.php");
    exit();
}

// ============ UPDATE ============
if (isset($_POST['update_post'])) {
    $id = mysqli_real_escape_string($conn, $_POST['post_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $pub_date = mysqli_real_escape_string($conn, $_POST['pub_date']);
    $introduction = mysqli_real_escape_string($conn, $_POST['introduction']);
    $cta = mysqli_real_escape_string($conn, $_POST['cta']);
    
    // Get current image
    $query = "SELECT image FROM blog_posts WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $current_image = $row['image'];
    
    // Handle new image upload
    if ($_FILES['image']['name'] != '') {
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
              call_to_action = '$cta'
              WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Post updated successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message'] = "Error updating post!";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    header("Location: blog.php");
    exit();
}

// ============ DELETE ============
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Get image name to delete file
    $query = "SELECT image FROM blog_posts WHERE id = $id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    if ($row['image'] != '' && file_exists("uploads/" . $row['image'])) {
        unlink("uploads/" . $row['image']);
    }
    
    $query = "DELETE FROM blog_posts WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Post deleted successfully!";
        $_SESSION['message_type'] = "success";
        $_SESSION['message_icon'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting post!";
        $_SESSION['message_type'] = "error";
        $_SESSION['message_icon'] = "error";
    }
    header("Location: blog.php");
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
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
$message_icon = isset($_SESSION['message_icon']) ? $_SESSION['message_icon'] : '';

// Clear session messages after displaying
if (!empty($message)) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    unset($_SESSION['message_icon']);
}
?>
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
        
        .file-info {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .preview-animate {
            animation: fadeIn 0.3s ease;
        }
        
        .table-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .btn-group-actions .btn {
            margin: 0 2px;
        }
        
        .modal-header-gradient-create {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        
        .modal-header-gradient-edit {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
        }
        
        /* Edit form highlight */
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
    <!-- End of Page Wrapper -->

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
                    <form action="blog.php" method="POST" enctype="multipart/form-data" id="createForm">
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
                                    
                                    <small class="text-muted">Supported formats: JPG, PNG, GIF (Max 2MB)</small>
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
                        <div class="col-md-6">
                            <strong><i class="fas fa-user"></i> Author:</strong>
                            <p><?php echo htmlspecialchars($row['author']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-calendar"></i> Date:</strong>
                            <p><?php echo date('F d, Y', strtotime($row['publication_date'])); ?></p>
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

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
                    </a>

    <script>
        $(document).ready(function() {
            // ===== INITIALIZE DATATABLES =====
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
                    { "orderable": false, "targets": [1, 7] },
                    { "searchable": false, "targets": [1, 7] }
                ]
            });
            
            // ===== CREATE IMAGE PREVIEW =====
            $('#createImageInput').change(function(e) {
                const file = this.files[0];
                if (file) {
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
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
                    if (file.size > 2000000) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'Image size must be less than 2MB',
                            confirmButtonColor: '#e74a3b'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#createImageInput').next('.custom-file-label').html(
                            '<i class="fas fa-check-circle text-success"></i> ' + file.name
                        );
                        
                        const previewHTML = `
                            <div class="position-relative w-100 text-center preview-animate">
                                <img src="${e.target.result}" 
                                     alt="Preview image" 
                                     style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                                <div class="file-info mt-2">
                                    <span class="badge badge-success">New image</span>
                                    <span class="text-muted ml-2">${(file.size / 1024).toFixed(1)} KB</span>
                                </div>
                            </div>
                        `;
                        $('#createImagePreviewContainer').html(previewHTML);
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // ===== EDIT IMAGE PREVIEW =====
            $('#editImageInput').change(function(e) {
                const file = this.files[0];
                if (file) {
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
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
                    if (file.size > 2000000) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'Image size must be less than 2MB',
                            confirmButtonColor: '#e74a3b'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#editImageInput').next('.custom-file-label').html(
                            '<i class="fas fa-check-circle text-success"></i> ' + file.name
                        );
                        
                        const previewHTML = `
                            <div class="position-relative w-100 text-center preview-animate">
                                <img src="${e.target.result}" 
                                     alt="Preview image" 
                                     style="max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 3px 15px rgba(0,0,0,0.1);">
                                <div class="file-info mt-2">
                                    <span class="badge badge-success">New image</span>
                                    <span class="text-muted ml-2">${(file.size / 1024).toFixed(1)} KB</span>
                                </div>
                            </div>
                        `;
                        $('#editImagePreviewContainer').html(previewHTML);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
        
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
        
        // ===== SHOW SWEETALERT ON PAGE LOAD =====
        <?php if (!empty($message)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $message_icon; ?>',
                title: '<?php echo $message_type == "success" ? "Success!" : "Error!"; ?>',
                text: '<?php echo htmlspecialchars($message); ?>',
                confirmButtonColor: '<?php echo $message_type == "success" ? "#1cc88a" : "#e74a3b"; ?>',
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        });
        <?php endif; ?>
        
        // ===== RESET CREATE FORM =====
        function resetCreateForm() {
            $('#createImageInput').val('');
            $('#createImageInput').next('.custom-file-label').html('<i class="fas fa-cloud-upload-alt"></i> Choose image...');
            
            var placeholderHTML = `
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
</body>
</html>

<?php

 include('./include/scripts.php');
 include('./include/footer.php');

?>