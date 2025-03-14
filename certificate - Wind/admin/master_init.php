<?php
/**
 * Master Initialization File
 * This file includes all necessary files in the correct order
 */

// Define the root directory path
define("ROOT_PATH", dirname(__DIR__));

// Include core config files first
require_once ROOT_PATH . "/includes/session.php";  // Session setup must come first
require_once ROOT_PATH . "/config/constants.php";
require_once ROOT_PATH . "/config/config.php";
require_once ROOT_PATH . "/config/database.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/security.php";

// Connect to database
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    // Handle database connection error gracefully
    die("Database connection error: " . $e->getMessage());
}
