<?php
require_once __DIR__ . '/config.php';
// require_once __DIR__ . '/functions.php';

// --- Preflight + guards ---
send_cors_headers();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

require_get();
require_api_key();
rate_limit(120);

$conn = db_connect();

// --- Query params ---
$id     = isset($_GET['id'])     ? safe_int($_GET['id'], 0, 1)         : 0;
$status = isset($_GET['status']) ? safe_string($_GET['status'], 20)    : '';
$search = isset($_GET['search']) ? safe_string($_GET['search'], 100)   : '';
$limit  = safe_int($_GET['limit'] ?? 10, 10, 1, 50);   // hard cap 50
$page   = safe_int($_GET['page']  ?? 1,  1,  1, 1000);
$offset = ($page - 1) * $limit;

// ============================================================
// SINGLE POST
// ============================================================
if ($id > 0) {
    $sql  = "SELECT id, title, image, author, publication_date, introduction,
                    call_to_action, status, created_at
             FROM blog_posts WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    if (!$post) {
        json_response(['success' => false, 'error' => 'Post not found'], 404);
    }
    $post['image_url'] = image_url($post['image']);
    json_response(['success' => true, 'data' => $post]);
}

// ============================================================
// LIST / FILTER / SEARCH
// ============================================================
$where  = [];
$params = [];
$types  = '';

if ($status === 'published' || $status === 'draft') {
    $where[]  = "status = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($search !== '') {
    $where[]  = "(title LIKE ? OR author LIKE ? OR introduction LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---- Total count (for pagination meta) ----
if ($types !== '') {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM blog_posts $where_sql");
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
    mysqli_stmt_close($stmt);
} else {
    $res   = mysqli_query($conn, "SELECT COUNT(*) AS total FROM blog_posts");
    $total = (int)(mysqli_fetch_assoc($res)['total'] ?? 0);
}

// ---- Page of results ----
$sql  = "SELECT id, title, image, author, publication_date, introduction,
                call_to_action, status, created_at
         FROM blog_posts
         $where_sql
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $sql);
$bind_types  = $types . 'ii';
$bind_params = array_merge($params, [$limit, $offset]);
mysqli_stmt_bind_param($stmt, $bind_types, ...$bind_params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$posts = [];
while ($row = mysqli_fetch_assoc($res)) {
    $row['image_url'] = image_url($row['image']);
    $posts[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);

json_response([
    'success'    => true,
    'pagination' => [
        'total' => $total,
        'page'  => $page,
        'limit' => $limit,
        'pages' => $limit > 0 ? (int)ceil($total / $limit) : 1,
    ],
    'data' => $posts,
]);