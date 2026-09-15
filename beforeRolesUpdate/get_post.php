<?php
include 'config.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $query = "SELECT * FROM blog_posts WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $post = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'post' => $post
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Post not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No ID provided'
    ]);
}
?>