<?php
/**
 * Final Constants Fix
 * This script removes ALL constant definitions from security_header.php
 * and properly updates admin pages to include files in the correct order
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Final Constants Fix</h1>";

// 1. Fix security_header.php - Remove ALL constant definitions
$securityHeaderFile = "security_header.php";
if (file_exists($securityHeaderFile)) {
    $securityContent = '<?php
/**
 * Security Header
 * Include this file at the top of all admin pages to ensure security constants are defined
 * 
 * NOTICE: All constant definitions have been moved to config.php
 * This file is now just a placeholder for backward compatibility
 */

// No constants defined here - All constants are now defined in config.php
';

    if (file_put_contents($securityHeaderFile, $securityContent)) {
        echo "<p style='color:green'>✅ Successfully removed all constant definitions from security_header.php</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to update security_header.php</p>";
    }
} else {
    echo "<p style='color:red'>❌ security_header.php file not found</p>";
}

// 2. Create init.php with a proper include order
$initFile = "init.php";
$initContent = '<?php
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
';

if (file_put_contents($initFile, $initContent)) {
    echo "<p style='color:green'>✅ Successfully created init.php with proper include order</p>";
} else {
    echo "<p style='color:red'>❌ Failed to create init.php</p>";
}

// 3. Fix all admin pages to use the new init.php
$adminPages = [
    'dashboard.php',
    'user_management.php',
    'security_log.php',
    'certificate_templates.php',
    'create_certificate.php',
    'bulk_certificates.php',
    'create_template.php',
    'change_password.php'
];

$fixedPages = 0;
foreach ($adminPages as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Save original content for comparison
        $originalContent = $content;
        
        // Replace any include of security_header.php
        $content = preg_replace(
            '/require_once\s+[\'"]security_header\.php[\'"]\s*;/i',
            '// security_header.php no longer needed',
            $content
        );
        
        // Replace all requires with just init.php
        $pattern = '/(session_start\(\);[\s\n\r]*)(require_once.*?config\.php.*?)(require_once.*?database\.php.*?)(require_once.*?functions\.php)/s';
        if (preg_match($pattern, $content)) {
            $content = preg_replace(
                $pattern,
                '$1// Include initialization file' . "\nrequire_once 'init.php';",
                $content
            );
            
            if ($content !== $originalContent) {
                if (file_put_contents($page, $content)) {
                    $fixedPages++;
                    echo "<p style='color:green'>✅ Successfully updated $page to use init.php</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to update $page</p>";
                }
            } else {
                echo "<p style='color:orange'>⚠️ No changes needed for $page</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ Could not find include pattern in $page</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ File not found: $page</p>";
    }
}

echo "<h3>Summary</h3>";
echo "<p>Fixed $fixedPages admin pages to use the new include structure.</p>";

echo "<h3>Testing</h3>";
echo "<p>Please test the following pages to verify the fix:</p>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>Dashboard</a></li>";
echo "<li><a href='user_management.php' target='_blank'>User Management</a></li>";
echo "<li><a href='security_log.php' target='_blank'>Security Log</a></li>";
echo "</ul>";

echo "<h3>If Errors Persist</h3>";
echo "<p>If you still see constant redefinition errors, try these additional steps:</p>";
echo "<ol>";
echo "<li>Clear your browser cache (Ctrl+F5)</li>";
echo "<li>Try in a private/incognito browser window</li>";
echo "<li>Restart your web server</li>";
echo "</ol>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    h1, h3 {
        color: #2c3e50;
    }
    p {
        margin: 10px 0;
    }
    a {
        color: #3498db;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    ul, ol {
        margin-left: 20px;
    }
    li {
        margin-bottom: 5px;
    }
</style>
