<?php
/**
 * Main initialization file
 * This file handles all necessary includes in the correct order
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration files in the correct order
require_once "../config/config.php";  // Already has all constants defined
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/security.php";

// Connect to database
$database = new Database();
$db = $database->getConnection();
