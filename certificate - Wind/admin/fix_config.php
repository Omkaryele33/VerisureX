<?php
/**
 * Config Fixer
 * This script fixes the constant redefinition warnings
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$configFile = '../config/config.php';

// Check if file exists
if (!file_exists($configFile)) {
    die('Error: Config file not found.');
}

// Read the file content
$content = file_get_contents($configFile);

// List of constants to check and modify
$constants = [
    'RATE_LIMIT',
    'RATE_LIMIT_WINDOW',
    'RATE_LIMIT_UNIQUE_KEYS',
    'SESSION_NAME',
    'SESSION_LIFETIME',
    'SESSION_SECURE',
    'SESSION_HTTPONLY',
    'CSRF_TOKEN_LENGTH',
    'CSRF_TOKEN_EXPIRY',
    'PASSWORD_MIN_LENGTH',
    'PASSWORD_REQUIRE_MIXED_CASE',
    'PASSWORD_REQUIRE_NUMBERS',
    'PASSWORD_REQUIRE_SYMBOLS',
    'ACCOUNT_LOCKOUT_THRESHOLD',
    'ACCOUNT_LOCKOUT_DURATION'
];

// For each constant, modify the line to check if it's already defined
foreach ($constants as $constant) {
    $pattern = "/define\s*\(\s*'$constant'\s*,/";
    $replacement = "if (!defined('$constant')) define('$constant',";
    $content = preg_replace($pattern, $replacement, $content);
}

// Write the updated content back to the file
if (file_put_contents($configFile, $content)) {
    echo "Success: Config file has been fixed. Constants will now only be defined if they don't already exist.<br>";
    
    // Display which constants were modified
    echo "<h3>Updated constants:</h3>";
    echo "<ul>";
    foreach ($constants as $constant) {
        echo "<li>$constant</li>";
    }
    echo "</ul>";
    
    echo "<p>The constant redefinition warnings should now be resolved. Please refresh your admin pages to check.</p>";
    
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
} else {
    echo "Error: Could not write to config file. Check file permissions.<br>";
}
?>
