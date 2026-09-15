<?php
session_start();
date_default_timezone_set('Africa/Nairobi');

$localhost = "localhost";
$username = "root";
$password = "";
$database = "travel_cms";

//Test connection
$conn = mysqli_connect($localhost, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>