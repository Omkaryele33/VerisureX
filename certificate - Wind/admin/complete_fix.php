<?php
/**
 * Complete Fix for Admin Panel
 * This script will completely reorganize how constants are defined and included
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Complete Fix for Admin Panel</h1>";

// Step 1: Move all constants to a single file
$constantsFile = "../config/constants.php";
$constantsContent = '<?php
/**
 * Application Constants
 * This file defines all constants used throughout the application
 * Include this file only once at the beginning of your application
 */

// Base URL - Change this to match your domain with protocol detection
if (!defined("BASE_URL")) 
    define("BASE_URL", (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://{$_SERVER["HTTP_HOST"]}/certificate");

// Admin panel URL
if (!defined("ADMIN_URL")) 
    define("ADMIN_URL", BASE_URL . "/admin");

// Verification URL
if (!defined("VERIFY_URL")) 
    define("VERIFY_URL", BASE_URL . "/verify");

// File upload settings
if (!defined("MAX_FILE_SIZE")) 
    define("MAX_FILE_SIZE", 5 * 1024 * 1024); // 5MB
if (!defined("ALLOWED_EXTENSIONS")) 
    define("ALLOWED_EXTENSIONS", ["jpg", "jpeg", "png"]);
if (!defined("UPLOAD_DIR")) 
    define("UPLOAD_DIR", dirname(__DIR__) . "/uploads/");
if (!defined("QR_DIR")) 
    define("QR_DIR", UPLOAD_DIR . "qrcodes/");

// Security settings
if (!defined("RATE_LIMIT")) 
    define("RATE_LIMIT", 10); // 10 requests per minute
if (!defined("RATE_LIMIT_WINDOW")) 
    define("RATE_LIMIT_WINDOW", 60); // 60 seconds
if (!defined("RATE_LIMIT_UNIQUE_KEYS")) 
    define("RATE_LIMIT_UNIQUE_KEYS", true); // Use combination of IP, user-agent, and session ID

// Session settings - Improved security
if (!defined("SESSION_NAME")) 
    define("SESSION_NAME", "certificate_admin_secure");
if (!defined("SESSION_LIFETIME")) 
    define("SESSION_LIFETIME", 1800); // 30 minutes (reduced from 1 hour)
if (!defined("SESSION_SECURE")) 
    define("SESSION_SECURE", isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on");
if (!defined("SESSION_HTTPONLY")) 
    define("SESSION_HTTPONLY", true);
if (!defined("CSRF_TOKEN_LENGTH")) 
    define("CSRF_TOKEN_LENGTH", 64);
if (!defined("CSRF_TOKEN_EXPIRY")) 
    define("CSRF_TOKEN_EXPIRY", 1800); // 30 minutes

// Password policy
if (!defined("PASSWORD_MIN_LENGTH")) 
    define("PASSWORD_MIN_LENGTH", 12);
if (!defined("PASSWORD_REQUIRE_MIXED_CASE")) 
    define("PASSWORD_REQUIRE_MIXED_CASE", true);
if (!defined("PASSWORD_REQUIRE_NUMBERS")) 
    define("PASSWORD_REQUIRE_NUMBERS", true);
if (!defined("PASSWORD_REQUIRE_SYMBOLS")) 
    define("PASSWORD_REQUIRE_SYMBOLS", true);
if (!defined("MAX_LOGIN_ATTEMPTS")) 
    define("MAX_LOGIN_ATTEMPTS", 5);
if (!defined("ACCOUNT_LOCKOUT_TIME")) 
    define("ACCOUNT_LOCKOUT_TIME", 900);

// File security
if (!defined("FILE_PERMISSIONS")) 
    define("FILE_PERMISSIONS", 0644);
if (!defined("DIR_PERMISSIONS")) 
    define("DIR_PERMISSIONS", 0755);
if (!defined("USE_RANDOM_FILENAMES")) 
    define("USE_RANDOM_FILENAMES", true);
';

// Write the constants file
if (file_put_contents($constantsFile, $constantsContent)) {
    echo "<p style='color:green'>✅ Created central constants file: <code>" . htmlspecialchars($constantsFile) . "</code></p>";
} else {
    echo "<p style='color:red'>❌ Error: Unable to create central constants file.</p>";
    die();
}

// Step 2: Update config.php to include constants.php instead of defining constants
$configFile = "../config/config.php";
$configBackup = $configFile . ".backup." . time();

// Backup the original config file
if (file_exists($configFile)) {
    if (copy($configFile, $configBackup)) {
        echo "<p style='color:green'>✅ Created backup of original config file at: <code>" . htmlspecialchars($configBackup) . "</code></p>";
    } else {
        echo "<p style='color:orange'>⚠️ Warning: Unable to create backup of config file.</p>";
    }
    
    // Create a new config.php that includes constants.php
    $configContent = file_get_contents($configFile);
    
    // Remove all constant definitions from config.php
    $constantsToRemove = [
        'BASE_URL', 'ADMIN_URL', 'VERIFY_URL', 'MAX_FILE_SIZE', 'ALLOWED_EXTENSIONS',
        'UPLOAD_DIR', 'QR_DIR', 'RATE_LIMIT', 'RATE_LIMIT_WINDOW', 'RATE_LIMIT_UNIQUE_KEYS',
        'SESSION_NAME', 'SESSION_LIFETIME', 'SESSION_SECURE', 'SESSION_HTTPONLY',
        'CSRF_TOKEN_LENGTH', 'CSRF_TOKEN_EXPIRY', 'PASSWORD_MIN_LENGTH',
        'PASSWORD_REQUIRE_MIXED_CASE', 'PASSWORD_REQUIRE_NUMBERS', 'PASSWORD_REQUIRE_SYMBOLS',
        'ACCOUNT_LOCKOUT_THRESHOLD', 'ACCOUNT_LOCKOUT_DURATION', 'FILE_PERMISSIONS',
        'DIR_PERMISSIONS', 'USE_RANDOM_FILENAMES'
    ];
    
    foreach ($constantsToRemove as $constant) {
        // Pattern to match constant definition
        $pattern = "/define\s*\(\s*['\"]" . preg_quote($constant, '/') . "['\"]\s*,[^\)]+\);\s*(\n|\r\n)?/";
        $configContent = preg_replace($pattern, '', $configContent);
    }
    
    // Add include for constants.php at the beginning of the file after the initial PHP tag
    $configContent = preg_replace(
        '/<\?php/',
        "<?php\n// Include application constants\nrequire_once dirname(__FILE__) . '/constants.php';",
        $configContent,
        1
    );
    
    // Write the updated config file
    if (file_put_contents($configFile, $configContent)) {
        echo "<p style='color:green'>✅ Updated config.php to include constants.php instead of defining constants directly.</p>";
    } else {
        echo "<p style='color:red'>❌ Error: Unable to update config.php file.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Error: Config file not found at: " . htmlspecialchars($configFile) . "</p>";
}

// Step 3: Update security_header.php to include constants.php
$securityHeaderFile = "security_header.php";
$securityHeaderContent = '<?php
/**
 * Security Header
 * Include this file at the top of all admin pages to ensure security constants are defined
 */

// Include application constants
require_once dirname(__DIR__) . "/config/constants.php";
';

// Write the updated security header
if (file_put_contents($securityHeaderFile, $securityHeaderContent)) {
    echo "<p style='color:green'>✅ Updated security_header.php to use central constants file.</p>";
} else {
    echo "<p style='color:red'>❌ Error: Unable to update security_header.php file.</p>";
}

// Step 4: Create a master init file that correctly includes all necessary files
$masterInitFile = "master_init.php";
$masterInitContent = '<?php
/**
 * Master Initialization File
 * This file includes all necessary files in the correct order
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include application constants first
require_once dirname(__DIR__) . "/config/constants.php";

// Include configuration and database files
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";
require_once dirname(__DIR__) . "/includes/security.php";

// Database connection
$database = new Database();
$db = $database->getConnection();
';

// Write the master init file
if (file_put_contents($masterInitFile, $masterInitContent)) {
    echo "<p style='color:green'>✅ Created master_init.php with proper include order.</p>";
} else {
    echo "<p style='color:red'>❌ Error: Unable to create master_init.php file.</p>";
}

// Step 5: Update admin files to use master_init.php
$adminFiles = [
    'dashboard.php',
    'user_management.php',
    'certificate_templates.php',
    'create_certificate.php',
    'create_template.php',
    'bulk_certificates.php',
    'security_log.php',
    'change_password.php'
];

$updatedFiles = 0;
foreach ($adminFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        // Pattern to find and replace the includes
        $includePattern = "/(session_start\(\);).*?(require_once.*?config\.php.*?)(require_once.*?database\.php.*?)(require_once.*?functions\.php)/s";
        
        if (preg_match($includePattern, $content)) {
            // Replace with master_init.php
            $content = preg_replace(
                $includePattern,
                "$1\n\n// Include master initialization file\nrequire_once 'master_init.php';",
                $content
            );
            
            // Remove security_header.php if it exists
            $content = preg_replace(
                "/(require_once\s+['\"](\.\.\/)*security_header\.php['\"];(\r\n|\n)*)/",
                "",
                $content
            );
            
            // Write the updated file if changed
            if ($content !== $originalContent && file_put_contents($file, $content)) {
                $updatedFiles++;
                echo "<p style='color:green'>✅ Updated <code>" . htmlspecialchars($file) . "</code> to use master_init.php.</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ No include pattern found in <code>" . htmlspecialchars($file) . "</code>.</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ File not found: <code>" . htmlspecialchars($file) . "</code>.</p>";
    }
}

echo "<h3>Summary</h3>";
echo "<p>Updated <strong>$updatedFiles</strong> admin files to use the new include structure.</p>";

echo "<h3>Testing</h3>";
echo "<p>Now let's test if the constant redefinition errors have been fixed:</p>";
echo "<ul>";
echo "<li><a href='dashboard.php' target='_blank'>Test Dashboard</a></li>";
echo "<li><a href='user_management.php' target='_blank'>Test User Management</a></li>";
echo "<li><a href='security_log.php' target='_blank'>Test Security Log</a></li>";
echo "</ul>";

echo "<p>If you still encounter errors after testing these pages, please follow these steps:</p>";
echo "<ol>";
echo "<li>Clear your browser cache by pressing Ctrl+F5</li>";
echo "<li>Ensure PHP is not caching files by adding the following to <code>php.ini</code>:<br>";
echo "<pre>opcache.enable=0\nzend_extension=opcache</pre></li>";
echo "<li>Restart your web server</li>";
echo "</ol>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 20px;
}
h1 {
    color: #2c3e50;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
h3 {
    margin-top: 25px;
    color: #2c3e50;
}
p {
    margin: 15px 0;
}
a {
    color: #3498db;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
code {
    background: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 3px;
    overflow: auto;
}
ul, ol {
    margin-left: 25px;
}
.test-links {
    margin-top: 20px;
}
.test-links a {
    display: inline-block;
    margin-right: 15px;
    padding: 8px 15px;
    background: #3498db;
    color: white;
    border-radius: 4px;
    text-decoration: none;
}
.test-links a:hover {
    background: #2980b9;
}
</style>
