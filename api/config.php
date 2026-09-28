<?php
// ============================================================
// SHARED CONFIG + HELPERS
// ============================================================

// ---- Database ----
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'travel_cms';

// $localhost = "localhost";
// $username = "aviwilds_noc";
// $password = "Aviwild@26";
// $database = "aviwilds_travel_cms";

// ---- Public base URL where /uploads/ lives ----
// Used to build absolute image URLs for the Next.js frontend
define('BASE_URL', 'http://localhost/travel_cms');

// ---- API KEY ----
//Generate one with:  php -r "echo bin2hex(random_bytes(32));"
define('API_KEY', '9d12c9bead2c8fb4001b672cdd834b61a8c9ac825d1e88687f17613144e52413');

// ---- Allowed origins (Next.js dev + prod) ----
$ALLOWED_ORIGINS = [
    'http://localhost:3000',
    'https://aviwild-safaris.com',
    'https://www.yourdomain.com',
    'https://crm.aviwildsafaris.com',
    
];

// ============================================================
// HEADERS: CORS + JSON
// ============================================================
function send_cors_headers() {
    global $ALLOWED_ORIGINS;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, $ALLOWED_ORIGINS, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");
    }
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-API-Key");
    header("Content-Type: application/json; charset=utf-8");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: no-referrer");
}

// ============================================================
// RESPONSE HELPER
// ============================================================
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================================
// ENFORCE GET
// ============================================================
function require_get() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

// ============================================================
// API KEY AUTH
// ============================================================
function require_api_key() {
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($provided === '' || !hash_equals(API_KEY, $provided)) {
        json_response(['success' => false, 'error' => 'Unauthorized'], 401);
    }
}

// ============================================================
// SIMPLE FILE-BASED RATE LIMIT (per IP, per minute)
// ============================================================
function rate_limit($max_per_minute = 120) {
    $ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/api_rl_' . md5($ip) . '.json';
    $now  = time();

    $data = ['start' => $now, 'count' => 0];
    if (is_file($file)) {
        $decoded = json_decode((string)@file_get_contents($file), true);
        if (is_array($decoded) && isset($decoded['start'], $decoded['count'])) {
            $data = $decoded;
        }
    }
    if ($now - (int)$data['start'] >= 60) {
        $data = ['start' => $now, 'count' => 0];
    }
    $data['count']++;

    @file_put_contents($file, json_encode($data), LOCK_EX);

    if ($data['count'] > $max_per_minute) {
        header('Retry-After: 60');
        json_response(['success' => false, 'error' => 'Too many requests'], 429);
    }
}

// ============================================================
// DB CONNECTION (mysqli procedural)
// ============================================================
function db_connect() {
    global $db_host, $db_user, $db_pass, $db_name;

    $conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
    if (!$conn) {
        // Log real error server-side, hide from client
        error_log('DB connect failed: ' . mysqli_connect_error());
        json_response(['success' => false, 'error' => 'Service temporarily unavailable'], 500);
    }
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

// ============================================================
// INPUT SANITIZERS (kept tiny + predictable)
// ============================================================
function safe_int($value, $default = 0, $min = null, $max = null) {
    if ($value === null || $value === '' || !is_numeric($value)) return $default;
    $v = (int)$value;
    if ($min !== null && $v < $min) $v = $min;
    if ($max !== null && $v > $max) $v = $max;
    return $v;
}

function safe_string($value, $max_len = 200) {
    if (!is_string($value)) return '';
    $value = trim($value);
    if (strlen($value) > $max_len) $value = substr($value, 0, $max_len);
    return $value;
}

// ============================================================
// BUILD ABSOLUTE IMAGE URL
// ============================================================
function image_url($filename) {
    if (!$filename) return null;
    return BASE_URL . '/uploads/' . rawurlencode($filename);
}