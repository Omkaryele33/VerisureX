<?php
/**
 * Admin Panel - Logout
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Clear session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
