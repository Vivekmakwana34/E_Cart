<?php
// Logout Process
session_start();

// Destroy all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear cookies if they exist
if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, '/');
}
if (isset($_COOKIE['user_password'])) {
    setcookie('user_password', '', time() - 3600, '/');
}

// Redirect to login page with success message
$_SESSION['success_message'] = "You have been logged out successfully!";
header("Location: login.php");
exit();
?>
