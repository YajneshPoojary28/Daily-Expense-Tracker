<?php
session_start();

// Auto-login as admin
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['currency'] = '$';
$_SESSION['login_time'] = time();

header("Location: admin_dashboard.php");
exit();
?>