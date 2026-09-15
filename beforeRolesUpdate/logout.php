<?php
require_once 'auth.php';
logout_user();
flash("You have been signed out.", 'success', 'success');
header("Location: login.php");
exit();