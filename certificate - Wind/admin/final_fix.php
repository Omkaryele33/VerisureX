<?php
/**
 * Final Fix for Constant Redefinition Errors
 * This will directly modify the config.php file to prevent redefinition errors
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Final Fix for Constant Redefinition Errors</h2>";

// Path to config.php
$configFile = '../config/config.php';

// Check if config file exists
if (!file_exists($configFile)) {
    die("<p style='color:red'>Error: Config file not found at: " . htmlspecialchars($configFile) . "</p>");
}

// Read the file content
$content = file_get_contents($configFile);
if ($content === false) {
    die("<p style='color:red'>Error: Unable to read config file content.</p>");
}

// Create a backup of the original file
$backupFile = $configFile . '.backup.' . time();
if (!copy($configFile, $backupFile)) {
    echo "<p style='color:orange'>Warning: Unable to create backup of config file.</p>";
} else {
    echo "<p style='color:green'>Created backup of original config file at: " . htmlspecialchars($backupFile) . "</p>";
}

// List of constants that need to be conditionally defined
$constants = [
    'RATE_LIMIT',
    'RATE_LIMIT_WINDOW', 
    'RATE_LIMIT_UNIQUE_KEYS',
    'CSRF_TOKEN_LENGTH',
    'CSRF_TOKEN_EXPIRY',
    'PASSWORD_MIN_LENGTH',
    'PASSWORD_REQUIRE_MIXED_CASE',
    'PASSWORD_REQUIRE_NUMBERS',
    'PASSWORD_REQUIRE_SYMBOLS'
];

// Process each constant definition
$modified = false;
foreach ($constants as $constant) {
    // Look for the specific pattern defining this constant
    $pattern = "/define\s*\(\s*['\"]" . preg_quote($constant, '/') . "['\"]\s*,/";
    $replacement = "if (!defined('$constant')) define('$constant',";
    
    // Replace the pattern
    $newContent = preg_replace($pattern, $replacement, $content);
    
    // Check if the content was modified
    if ($newContent !== $content) {
        $content = $newContent;
        $modified = true;
        echo "<p>Modified constant definition for: <code>$constant</code></p>";
    }
}

// Write the modified content back to the file
if ($modified) {
    if (file_put_contents($configFile, $content)) {
        echo "<p style='color:green'><strong>Success!</strong> The config file has been updated to prevent constant redefinition errors.</p>";
    } else {
        echo "<p style='color:red'><strong>Error:</strong> Unable to write modified content back to config file.</p>";
        die();
    }
} else {
    echo "<p style='color:orange'>No changes were made to the config file. Constants may already be conditionally defined.</p>";
}

// Also create a centralized constants file
$constantsFile = 'constants.php';
$constantsContent = '<?php
/**
 * Central Constants Definition
 * Include this file once at the beginning of your application
 * to ensure constants are defined only once.
 */

// Security settings
if (!defined("RATE_LIMIT")) define("RATE_LIMIT", 10);
if (!defined("RATE_LIMIT_WINDOW")) define("RATE_LIMIT_WINDOW", 60);
if (!defined("RATE_LIMIT_UNIQUE_KEYS")) define("RATE_LIMIT_UNIQUE_KEYS", true);

// CSRF Protection
if (!defined("CSRF_TOKEN_LENGTH")) define("CSRF_TOKEN_LENGTH", 64);
if (!defined("CSRF_TOKEN_EXPIRY")) define("CSRF_TOKEN_EXPIRY", 1800);

// Password Policy
if (!defined("PASSWORD_MIN_LENGTH")) define("PASSWORD_MIN_LENGTH", 12);
if (!defined("PASSWORD_REQUIRE_MIXED_CASE")) define("PASSWORD_REQUIRE_MIXED_CASE", true);
if (!defined("PASSWORD_REQUIRE_NUMBERS")) define("PASSWORD_REQUIRE_NUMBERS", true);
if (!defined("PASSWORD_REQUIRE_SYMBOLS")) define("PASSWORD_REQUIRE_SYMBOLS", true);
if (!defined("MAX_LOGIN_ATTEMPTS")) define("MAX_LOGIN_ATTEMPTS", 5);
if (!defined("ACCOUNT_LOCKOUT_TIME")) define("ACCOUNT_LOCKOUT_TIME", 900);
';

// Write the constants file
if (file_put_contents($constantsFile, $constantsContent)) {
    echo "<p style='color:green'>Created central constants file: <code>constants.php</code></p>";
} else {
    echo "<p style='color:red'>Error: Unable to create central constants file.</p>";
}

// Update security_header.php to include constants.php instead of defining constants directly
$securityHeaderFile = 'security_header.php';
if (file_exists($securityHeaderFile)) {
    $securityContent = file_get_contents($securityHeaderFile);
    
    // Create a new security header file
    $newSecurityContent = '<?php
/**
 * Security Header
 * Include this file at the top of all admin pages to ensure security constants are defined
 */

// Include central constants file
require_once "constants.php";
';

    // Write the updated security header
    if (file_put_contents($securityHeaderFile, $newSecurityContent)) {
        echo "<p style='color:green'>Updated security header to use central constants file.</p>";
    } else {
        echo "<p style='color:red'>Error: Unable to update security header file.</p>";
    }
} else {
    echo "<p style='color:orange'>Warning: security_header.php not found.</p>";
}

echo "<h3>Testing</h3>";
echo "<p>Now let's test if the constant redefinition errors have been fixed:</p>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>Test Dashboard</a></li>";
echo "<li><a href='user_management.php' target='_blank'>Test User Management</a></li>";
echo "<li><a href='security_log.php' target='_blank'>Test Security Log</a></li>";
echo "</ul>";

echo "<p>If you still encounter errors after testing these pages, please refresh your browser cache (Ctrl+F5).</p>";
?>
