<?php
require_once __DIR__ . '/config.php';
//require_once '../functions.php';

send_cors_headers();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

require_get();
require_api_key();
rate_limit(120);

$conn = db_connect();

// ============================================================
// COMPANY INFO (single row)
// ============================================================
$company = null;
$res = mysqli_query($conn,
    "SELECT company_name, tagline, email, phone, address, website,
            about, mission, vision, logo, updated_at
     FROM company_info LIMIT 1"
);
if ($res) {
    $company = mysqli_fetch_assoc($res) ?: null;
    if ($company) {
        $company['logo_url'] = image_url($company['logo']);
        unset($company['logo']); // hide raw filename, expose URL only
    }
}

// ============================================================
// RECENT ACTIVITY (public-safe fields only — no IP, no user)
// ============================================================
$activity = [];
$res = mysqli_query($conn,
    "SELECT action, target_type, target_title, details, created_at
     FROM activity_log
     ORDER BY created_at DESC
     LIMIT 20"
);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $activity[] = $row;
    }
}

mysqli_close($conn);

json_response([
    'success' => true,
    'data'    => [
        'company'  => $company,
        'activity' => $activity,
    ],
]);