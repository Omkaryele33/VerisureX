<?php
/**
 * Diagnose and Fix Constant Redefinition Errors
 * 
 * This script:
 * 1. Diagnoses which constants are being redefined and where
 * 2. Fixes the problem by ensuring constants are defined in only one place
 * 3. Updates all admin pages to use a proper include structure
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to get file path
function getRelativePath($file) {
    return str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnose and Fix Constant Redefinition</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; max-width: 1000px; margin: 0 auto; padding: 20px; }
        h1, h2, h3 { color: #2c3e50; }
        p { margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .code { font-family: monospace; background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .file-path { color: #3498db; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .action-btn { display: inline-block; padding: 8px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .action-btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <h1>Diagnose and Fix Constant Redefinition</h1>";

// Step 1: Diagnose the problem
echo "<h2>Step 1: Diagnosing Constants</h2>";

// List of problematic constants
$problematicConstants = [
    'RATE_LIMIT',
    'RATE_LIMIT_WINDOW',
    'RATE_LIMIT_UNIQUE_KEYS',
    'CSRF_TOKEN_LENGTH',
    'CSRF_TOKEN_EXPIRY',
    'PASSWORD_MIN_LENGTH',
    'PASSWORD_REQUIRE_MIXED_CASE',
    'PASSWORD_REQUIRE_NUMBERS',
    'PASSWORD_REQUIRE_SYMBOLS',
    'MAX_LOGIN_ATTEMPTS',
    'ACCOUNT_LOCKOUT_TIME'
];

// Files to check
$filesToCheck = [
    'security_header.php',
    '../config/config.php'
];

$constantLocations = [];

// Check each file for constant definitions
foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        foreach ($problematicConstants as $constant) {
            if (preg_match("/define\s*\(\s*['\"]" . preg_quote($constant, '/') . "['\"]/", $content)) {
                if (!isset($constantLocations[$constant])) {
                    $constantLocations[$constant] = [];
                }
                $constantLocations[$constant][] = $file;
            }
        }
    } else {
        echo "<p class='error'>File not found: " . htmlspecialchars($file) . "</p>";
    }
}

// Display diagnostics
echo "<h3>Constants Defined in Multiple Files:</h3>";
echo "<table>";
echo "<tr><th>Constant</th><th>Defined In</th></tr>";

$multipleDefinitions = false;
foreach ($constantLocations as $constant => $files) {
    if (count($files) > 1) {
        $multipleDefinitions = true;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($constant) . "</td>";
        echo "<td>" . implode("<br>", array_map('htmlspecialchars', $files)) . "</td>";
        echo "</tr>";
    }
}

if (!$multipleDefinitions) {
    echo "<tr><td colspan='2'>No constants are defined in multiple files. The issue might be in the include order.</td></tr>";
}

echo "</table>";

// Step 2: Implement the fix
echo "<h2>Step 2: Implementing the Fix</h2>";

$fixesApplied = [];

// Fix 1: Update security_header.php to be empty
$securityHeaderFile = "security_header.php";
if (file_exists($securityHeaderFile)) {
    $securityContent = '<?php
/**
 * Security Header
 * This file is now a placeholder and includes the centralized constants
 */

// Include centralized constants (only if they haven\'t been included yet)
if (!defined("RATE_LIMIT")) {
    require_once dirname(__DIR__) . "/config/config.php";
}
';

    if (file_put_contents($securityHeaderFile, $securityContent)) {
        $fixesApplied[] = "Removed all constant definitions from security_header.php and made it include config.php instead";
    } else {
        echo "<p class='error'>Failed to update security_header.php</p>";
    }
} else {
    echo "<p class='error'>security_header.php file not found</p>";
}

// Fix 2: Create a unified init.php
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
if (!defined("RATE_LIMIT")) {
    require_once dirname(__DIR__) . "/config/config.php";
}
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";
require_once dirname(__DIR__) . "/includes/security.php";

// Connect to database
$database = new Database();
$db = $database->getConnection();
';

if (file_put_contents($initFile, $initContent)) {
    $fixesApplied[] = "Created a unified init.php that includes all necessary files in the correct order";
} else {
    echo "<p class='error'>Failed to create init.php</p>";
}

// Fix 3: Update all admin pages to use only init.php
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

$updatedPages = 0;
$updateErrors = 0;

foreach ($adminPages as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        $originalContent = $content;
        
        // Pattern to match all the includes
        $pattern = '/(session_start\(\);)\s*\n\s*((?:(?:\/\/|#).*\n|\/\*(?:.|[\r\n])*?\*\/\s*\n)*)\s*((?:require_once|include_once|require|include).*?;\s*\n)+/s';
        
        if (preg_match($pattern, $content)) {
            // Make sure we only keep session_start() and add our init
            $content = preg_replace(
                $pattern,
                "$1\n\n// Include initialization file\nrequire_once 'init.php';\n\n",
                $content
            );
            
            // In case the pattern was not matched correctly, individually remove includes
            $includePattern = "/require_once\s+['\"](?:\.\.\/)*(?:config|security_header|database|functions|security)\.php['\"]\s*;\s*\n+/";
            $content = preg_replace($includePattern, "", $content);
            
            // Make sure we have the session_start and init
            if (strpos($content, "require_once 'init.php';") === false) {
                // If init.php is not included, add it after session_start
                if (preg_match('/session_start\(\);/', $content)) {
                    $content = preg_replace(
                        '/session_start\(\);/',
                        "session_start();\n\n// Include initialization file\nrequire_once 'init.php';",
                        $content
                    );
                }
            }
            
            if ($originalContent !== $content) {
                if (file_put_contents($page, $content)) {
                    $updatedPages++;
                } else {
                    $updateErrors++;
                    echo "<p class='error'>Failed to update " . htmlspecialchars($page) . "</p>";
                }
            } else {
                echo "<p class='warning'>No changes needed for " . htmlspecialchars($page) . "</p>";
            }
        } else {
            echo "<p class='warning'>Could not find include pattern in " . htmlspecialchars($page) . "</p>";
        }
    } else {
        echo "<p class='warning'>File not found: " . htmlspecialchars($page) . "</p>";
    }
}

$fixesApplied[] = "Updated $updatedPages admin pages to only use init.php";
if ($updateErrors > 0) {
    echo "<p class='error'>Failed to update $updateErrors pages</p>";
}

// Display fixes applied
echo "<h3>Fixes Applied:</h3>";
echo "<ul>";
foreach ($fixesApplied as $fix) {
    echo "<li class='success'>" . htmlspecialchars($fix) . "</li>";
}
echo "</ul>";

// Step 3: Verify the solution
echo "<h2>Step 3: Testing the Solution</h2>";
echo "<p>Click the links below to test whether the constant redefinition errors are fixed:</p>";

echo "<div>";
echo "<a href='dashboard.php' target='_blank' class='action-btn'>Test Dashboard</a>";
echo "<a href='user_management.php' target='_blank' class='action-btn'>Test User Management</a>";
echo "<a href='security_log.php' target='_blank' class='action-btn'>Test Security Log</a>";
echo "</div>";

echo "<h3>Additional Steps if Errors Persist:</h3>";
echo "<ol>";
echo "<li>Clear your browser cache by pressing Ctrl+F5</li>";
echo "<li>Try opening the page in a private/incognito window</li>";
echo "<li>Restart your web server (Apache/XAMPP)</li>";
echo "<li>If errors continue, you may need to manually check each file to ensure constants are only defined once</li>";
echo "</ol>";

echo "<h2>Technical Explanation</h2>";
echo "<p>The constant redefinition errors were occurring because the same constants were being defined in multiple files:</p>";
echo "<ol>";
echo "<li>First in <code>security_header.php</code> (included at the top of admin pages)</li>";
echo "<li>Then again in <code>config.php</code> (included after security_header.php)</li>";
echo "</ol>";

echo "<p>Our solution fixes this by:</p>";
echo "<ol>";
echo "<li>Removing all constant definitions from security_header.php</li>";
echo "<li>Creating a unified init.php that includes all necessary files in the correct order</li>";
echo "<li>Updating all admin pages to only use init.php and not directly include other files</li>";
echo "</ol>";

echo "</body>
</html>";
?>
