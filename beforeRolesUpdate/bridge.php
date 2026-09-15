<?php

require_once 'auth.php';
include_once 'functions.php';

ob_start();
$section = $_GET['section'] ?? '';

switch ($section) {
    case 'dashboard':
        include('dashboard.php');
        break;
    case 'blog':
        include('blog.php');
        break;
    case 'customer':
        include('customer.php');
        break;
    case 'activity':
        include('activity_log.php');
        break;
    case 'logout':
        include('logout.php');
        break;
    case 'profile':
        include('profile.php');
        break; 
	case 'settings':
         include('settings.php');
         break;
    case 'logout':
         include('logout.php');
         break; 
    default:
        // optional: show a default page or nothing
         //include('dashboard.php');
		 include('activity_log.php');
        break;
}









//==================================== This also works perfectly =========================
// $allowed = [
//     'kim'  => 'kim.php',
//     'blog' => 'blog.php',
// 	'customer' => 'customer.php',
// 	'dashboard' => 'dashboard.php',
// ];

// $section = $_GET['section'] ?? '';

// if (isset($allowed[$section])) {
//     include($allowed[$section]);
// }

?>

