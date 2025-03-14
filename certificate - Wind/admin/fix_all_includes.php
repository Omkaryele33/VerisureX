<?php
/**
 * Comprehensive Fix Script
 * This script fixes all include-related issues across the admin panel
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Directories to scan for PHP files
$directories = [
    __DIR__, // admin directory
];

// Success and error tracking
$successCount = 0;
$errorCount = 0;
$modifiedFiles = [];

// First, fix the config.php file to use conditional definitions
$configFile = '../config/config.php';
if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    
    // List of constants that may be redefined
    $constants = [
        'RATE_LIMIT',
        'RATE_LIMIT_WINDOW',
        'RATE_LIMIT_UNIQUE_KEYS',
        'CSRF_TOKEN_LENGTH',
        'CSRF_TOKEN_EXPIRY',
        'PASSWORD_MIN_LENGTH',
        'PASSWORD_REQUIRE_MIXED_CASE',
        'PASSWORD_REQUIRE_NUMBERS',
        'PASSWORD_REQUIRE_SYMBOLS',
        'ACCOUNT_LOCKOUT_THRESHOLD',
        'ACCOUNT_LOCKOUT_DURATION',
        'MAX_LOGIN_ATTEMPTS',
        'ACCOUNT_LOCKOUT_TIME'
    ];
    
    $modified = false;
    
    // For each constant, modify the line to check if it's already defined
    foreach ($constants as $constant) {
        $pattern = "/define\s*\(\s*['\"]$constant['\"]\s*,/";
        $replacement = "if (!defined('$constant')) define('$constant',";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $modified = true;
            $content = $newContent;
        }
    }
    
    // Write the updated content back to the file if modified
    if ($modified) {
        if (file_put_contents($configFile, $content)) {
            $successCount++;
            $modifiedFiles[] = $configFile;
        } else {
            $errorCount++;
            echo "Error: Could not write to config file: $configFile<br>";
        }
    }
}

// Create a master init.php file that properly includes all needed files in the correct order
$initContent = '<?php
/**
 * Master Initialization File
 * This file includes all necessary files in the correct order
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security constants first (conditionally defined)
require_once __DIR__ . "/security_header.php";

// Include main configuration files
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";
require_once __DIR__ . "/../includes/security.php";

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Additional initializations can go here
';

// Write the master init file
$masterInitFile = __DIR__ . '/master_init.php';
if (file_put_contents($masterInitFile, $initContent)) {
    $successCount++;
    $modifiedFiles[] = $masterInitFile;
} else {
    $errorCount++;
    echo "Error: Could not create master init file.<br>";
}

// Now scan all PHP files in admin directory and update their includes
foreach ($directories as $directory) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        // Skip directories and non-PHP files
        if ($file->isDir() || $file->getExtension() !== 'php' || 
            $file->getBasename() === 'master_init.php' || 
            $file->getBasename() === 'fix_all_includes.php') {
            continue;
        }
        
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        
        // Skip files that already include master_init.php
        if (strpos($content, 'master_init.php') !== false) {
            continue;
        }
        
        // Pattern to match the typical include sequence
        $includePattern = "/(session_start\(\);.*?)(require_once.*?config\.php.*?)(require_once.*?database\.php.*?)(require_once.*?functions\.php)/s";
        
        // Check if this is a page with the typical include sequence
        if (preg_match($includePattern, $content)) {
            // Replace the entire include sequence with master_init.php
            $newContent = preg_replace(
                $includePattern,
                "session_start();\n\n// Include master initialization file\nrequire_once 'master_init.php';",
                $content
            );
            
            // Also remove security_header.php if it's included
            $newContent = preg_replace(
                "/(require_once.*?security_header\.php.*?)\n/s",
                "",
                $newContent
            );
            
            // Write the updated content back to the file
            if ($content !== $newContent && file_put_contents($filePath, $newContent)) {
                $successCount++;
                $modifiedFiles[] = $filePath;
            }
        }
    }
}

// Output results
echo "<h2>Fix All Includes - Results</h2>";

if ($successCount > 0) {
    echo "<div style='color: green;'><strong>Successfully modified $successCount files:</strong></div>";
    echo "<ul>";
    foreach ($modifiedFiles as $file) {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul>";
    
    echo "<p>Created a master_init.php file that properly includes all necessary files in the correct order.</p>";
    echo "<p>Modified admin pages to use this master file, eliminating constant redefinition errors.</p>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='dashboard.php'>Go to Dashboard</a> - Check if the error messages are gone</li>";
    echo "<li><a href='user_management.php'>Go to User Management</a> - Test another page</li>";
    echo "<li><a href='security_log.php'>Go to Security Log</a> - Verify security logging works</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='color: red;'><strong>No files were modified. Something went wrong!</strong></div>";
    echo "<p>Please check file permissions and try again.</p>";
}

if ($errorCount > 0) {
    echo "<div style='color: red; margin-top: 20px;'><strong>Encountered $errorCount errors during the process.</strong></div>";
}
?>
