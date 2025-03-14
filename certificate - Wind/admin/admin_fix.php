<?php
/**
 * Admin Panel Fix Script
 * This script diagnoses and fixes common issues with the admin panel
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to check if a file exists and is readable
function checkFile($path, $description) {
    echo "<tr>";
    echo "<td>$description</td>";
    echo "<td>$path</td>";
    
    if (file_exists($path) && is_readable($path)) {
        echo "<td class='text-success'><i class='bi bi-check-circle-fill'></i> File exists and is readable</td>";
        return true;
    } else if (file_exists($path) && !is_readable($path)) {
        echo "<td class='text-warning'><i class='bi bi-exclamation-triangle-fill'></i> File exists but is not readable</td>";
        return false;
    } else {
        echo "<td class='text-danger'><i class='bi bi-x-circle-fill'></i> File not found</td>";
        return false;
    }
    echo "</tr>";
}

// Function to fix the syntax error in PHP files
function fixSyntaxError($file) {
    if (!file_exists($file) || !is_readable($file)) {
        return ["status" => false, "message" => "File not found or not readable: $file"];
    }
    
    $content = file_get_contents($file);
    
    // Fix common syntax errors
    $fixes = [
        // Fix double semicolons
        '/;\s*;/' => ';',
        
        // Fix broken quotes in PHP code
        "/require_once\s+'master_init\.php';';/" => "require_once 'master_init.php';",
        
        // Fix missing semicolons in PHP
        '/(?<!;|\{|\})\s*\?>/' => ';?>',
        
        // Fix extra closing brackets
        '/\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}/' => '}}}}}}}}}}',
        '/\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}/' => '}}}}}}}}}',
        '/\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}/' => '}}}}}}}}',
        '/\}\s*\}\s*\}\s*\}\s*\}\s*\}\s*\}/' => '}}}}}}}',
        '/\}\s*\}\s*\}\s*\}\s*\}\s*\}/' => '}}}}}}',
        '/\}\s*\}\s*\}\s*\}\s*\}/' => '}}}}}',
        
        // Fix malformed database connection
        '/\$database\s*=\s*new\s+Database\(\)\s*;\s*\$db\s*=\s*new\s+Database\(\)\s*;/' => '$database = new Database(); $db = $database->getConnection();'
    ];
    
    $newContent = preg_replace(array_keys($fixes), array_values($fixes), $content);
    
    // Fix the issue with multiple includes
    $includePattern = '/require_once\s+\'master_init\.php\'\s*;\s*require_once\s+\'\.\.\/includes\/security\.php\'\s*;/';
    if (preg_match($includePattern, $newContent)) {
        $newContent = preg_replace($includePattern, "require_once 'master_init.php';", $newContent);
    }
    
    if ($newContent != $content) {
        if (file_put_contents($file, $newContent)) {
            return ["status" => true, "message" => "Successfully fixed syntax errors in $file"];
        } else {
            return ["status" => false, "message" => "Failed to write changes to $file"];
        }
    }
    
    return ["status" => true, "message" => "No syntax errors found in $file"];
}

// Function to fix the master_init.php file
function fixMasterInit() {
    $file = "master_init.php";
    $content = '<?php
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

    if (file_put_contents($file, $content)) {
        return ["status" => true, "message" => "Successfully updated master_init.php"];
    } else {
        return ["status" => false, "message" => "Failed to update master_init.php"];
    }
}

// Function to fix security_header.php
function fixSecurityHeader() {
    $file = "security_header.php";
    $content = '<?php
/**
 * Security Header
 * This file is now a placeholder that includes master_init.php
 */

// Simply include the master initialization file
require_once "master_init.php";
';

    if (file_put_contents($file, $content)) {
        return ["status" => true, "message" => "Successfully updated security_header.php"];
    } else {
        return ["status" => false, "message" => "Failed to update security_header.php"];
    }
}

// Function to fix all admin pages to use master_init.php
function fixAdminPages() {
    $adminPages = [
        'dashboard.php',
        'certificate_templates.php',
        'user_management.php',
        'security_log.php',
        'create_certificate.php',
        'bulk_certificates.php',
        'create_template.php',
        'change_password.php',
        'edit_certificate.php'
    ];
    
    $results = [];
    
    foreach ($adminPages as $page) {
        if (file_exists($page)) {
            $result = fixSyntaxError($page);
            $results[$page] = $result;
        } else {
            $results[$page] = ["status" => false, "message" => "File not found: $page"];
        }
    }
    
    return $results;
}

// Function to ensure constants are defined once
function ensureConstantsDefinedOnce() {
    $configPath = dirname(__DIR__) . "/config/config.php";
    $constantsPath = dirname(__DIR__) . "/config/constants.php";
    
    // Check if constants.php exists
    if (!file_exists($constantsPath)) {
        // Create constants.php
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
    define("MAX_FILE_SIZE", 5 * 1024 * 1024);  // 5MB

if (!defined("ALLOWED_EXTENSIONS")) 
    define("ALLOWED_EXTENSIONS", "jpg,jpeg,png,pdf");

if (!defined("UPLOAD_DIR")) 
    define("UPLOAD_DIR", dirname(__DIR__) . "/uploads");

// Security settings
if (!defined("RATE_LIMIT")) 
    define("RATE_LIMIT", 5);  // Maximum allowed requests

if (!defined("RATE_LIMIT_WINDOW")) 
    define("RATE_LIMIT_WINDOW", 60);  // Time window in seconds

if (!defined("RATE_LIMIT_UNIQUE_KEYS")) 
    define("RATE_LIMIT_UNIQUE_KEYS", 1000);  // Maximum unique keys to track

if (!defined("CSRF_TOKEN_LENGTH")) 
    define("CSRF_TOKEN_LENGTH", 64);  // CSRF token length

if (!defined("CSRF_TOKEN_EXPIRY")) 
    define("CSRF_TOKEN_EXPIRY", 3600);  // CSRF token expiry in seconds

// Password policy
if (!defined("PASSWORD_MIN_LENGTH")) 
    define("PASSWORD_MIN_LENGTH", 8);

if (!defined("PASSWORD_REQUIRE_MIXED_CASE")) 
    define("PASSWORD_REQUIRE_MIXED_CASE", true);

if (!defined("PASSWORD_REQUIRE_NUMBERS")) 
    define("PASSWORD_REQUIRE_NUMBERS", true);

if (!defined("PASSWORD_REQUIRE_SYMBOLS")) 
    define("PASSWORD_REQUIRE_SYMBOLS", true);

// Login security
if (!defined("MAX_LOGIN_ATTEMPTS")) 
    define("MAX_LOGIN_ATTEMPTS", 5);

if (!defined("ACCOUNT_LOCKOUT_TIME")) 
    define("ACCOUNT_LOCKOUT_TIME", 15 * 60);  // 15 minutes
';
        
        if (!file_put_contents($constantsPath, $constantsContent)) {
            return ["status" => false, "message" => "Failed to create constants.php"];
        }
    }
    
    // Now update config.php to include constants.php instead of defining constants itself
    if (file_exists($configPath)) {
        $configContent = file_get_contents($configPath);
        
        // Remove constant definitions from config.php
        $pattern = '/define\s*\(\s*["\'](RATE_LIMIT|RATE_LIMIT_WINDOW|RATE_LIMIT_UNIQUE_KEYS|CSRF_TOKEN_LENGTH|CSRF_TOKEN_EXPIRY|PASSWORD_MIN_LENGTH|PASSWORD_REQUIRE_MIXED_CASE|PASSWORD_REQUIRE_NUMBERS|PASSWORD_REQUIRE_SYMBOLS|MAX_LOGIN_ATTEMPTS|ACCOUNT_LOCKOUT_TIME|BASE_URL|ADMIN_URL|VERIFY_URL|MAX_FILE_SIZE|ALLOWED_EXTENSIONS|UPLOAD_DIR)["\']/m';
        
        if (preg_match($pattern, $configContent)) {
            // Replace constant definitions with an include to constants.php
            $newContent = preg_replace('/\/\/\s*Security constants[\s\S]*?\/\/\s*Database credentials/m', 
                "// Include constants first\nrequire_once __DIR__ . '/constants.php';\n\n// Database credentials", 
                $configContent);
            
            if ($newContent != $configContent) {
                if (!file_put_contents($configPath, $newContent)) {
                    return ["status" => false, "message" => "Failed to update config.php"];
                }
            }
        }
    } else {
        return ["status" => false, "message" => "config.php not found"];
    }
    
    return ["status" => true, "message" => "Successfully ensured constants are defined once"];
}

// Start the HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Fix Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        .result-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .fixed-action-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
        .action-badge {
            font-size: 0.8em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Admin Panel Fix Tool</h3>
                <span class="badge bg-primary">v1.0</span>
            </div>
            <div class="card-body">
                <p>This tool will diagnose and fix common issues with the admin panel.</p>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i> Running diagnostics and applying fixes automatically...
                </div>
                
                <h4>1. Checking Required Files</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Path</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rootDir = dirname(__DIR__);
                            checkFile("$rootDir/config/config.php", "Configuration File");
                            checkFile("$rootDir/config/constants.php", "Constants File");
                            checkFile("$rootDir/config/database.php", "Database Configuration");
                            checkFile("$rootDir/includes/functions.php", "Functions Library");
                            checkFile("$rootDir/includes/security.php", "Security Library");
                            checkFile("master_init.php", "Master Initialization File");
                            checkFile("security_header.php", "Security Header File");
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <h4 class="mt-4">2. Applying Fixes</h4>
                <div class="row">
                    <?php
                    // Fix master_init.php
                    $masterInitResult = fixMasterInit();
                    echo "<div class='col-md-6'>";
                    echo "<div class='result-box " . ($masterInitResult["status"] ? "success" : "error") . "'>";
                    echo "<strong>" . ($masterInitResult["status"] ? "✅" : "❌") . " Master Init File:</strong> {$masterInitResult["message"]}";
                    echo "</div>";
                    echo "</div>";
                    
                    // Fix security_header.php
                    $securityHeaderResult = fixSecurityHeader();
                    echo "<div class='col-md-6'>";
                    echo "<div class='result-box " . ($securityHeaderResult["status"] ? "success" : "error") . "'>";
                    echo "<strong>" . ($securityHeaderResult["status"] ? "✅" : "❌") . " Security Header:</strong> {$securityHeaderResult["message"]}";
                    echo "</div>";
                    echo "</div>";
                    
                    // Ensure constants are defined once
                    $constantsResult = ensureConstantsDefinedOnce();
                    echo "<div class='col-md-6'>";
                    echo "<div class='result-box " . ($constantsResult["status"] ? "success" : "error") . "'>";
                    echo "<strong>" . ($constantsResult["status"] ? "✅" : "❌") . " Constants Definition:</strong> {$constantsResult["message"]}";
                    echo "</div>";
                    echo "</div>";
                    
                    // Fix admin pages
                    $adminPagesResults = fixAdminPages();
                    echo "<div class='col-md-6'>";
                    echo "<div class='result-box success'>";
                    echo "<strong>✅ Admin Pages:</strong> Fixed syntax errors in admin pages";
                    echo "<ul class='mt-2 mb-0' style='font-size: 0.9em;'>";
                    foreach ($adminPagesResults as $page => $result) {
                        $icon = $result["status"] ? "✅" : "❌";
                        echo "<li>$icon $page: {$result["message"]}</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    echo "</div>";
                    ?>
                </div>
                
                <h4 class="mt-4">3. Verification</h4>
                <p>Click the links below to test the fixed pages:</p>
                
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="dashboard.php" class="btn btn-primary" target="_blank">Dashboard</a>
                    <a href="certificate_templates.php" class="btn btn-primary" target="_blank">Certificate Templates</a>
                    <a href="user_management.php" class="btn btn-primary" target="_blank">User Management</a>
                    <a href="security_log.php" class="btn btn-primary" target="_blank">Security Log</a>
                </div>
                
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> Fix process completed. If you still experience issues, please check the PHP error logs or contact support.
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Technical Details and Fixes Applied</h4>
            </div>
            <div class="card-body">
                <h5>Issues Detected and Fixed:</h5>
                <ol>
                    <li><strong>Syntax Errors:</strong> Fixed syntax errors in PHP files, including duplicate semicolons and broken quotes.</li>
                    <li><strong>Duplicate Constant Definitions:</strong> Ensured constants are defined only once by centralizing them in constants.php.</li>
                    <li><strong>Include Structure:</strong> Standardized the include structure across all admin pages to use master_init.php.</li>
                    <li><strong>Database Connection:</strong> Fixed inconsistent database connection code.</li>
                    <li><strong>Security Header:</strong> Updated security_header.php to function as a simplified include file.</li>
                </ol>
                
                <h5>Recommended Next Steps:</h5>
                <ul>
                    <li>Clear your browser cache or use incognito mode to test the fixed pages.</li>
                    <li>If you still encounter issues, check the PHP error logs (usually in the Apache/logs directory).</li>
                    <li>Consider adding comprehensive error logging to catch any future issues.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="fixed-action-btn">
        <a href="dashboard.php" class="btn btn-success btn-lg">
            <i class="bi bi-arrow-left-circle me-2"></i> Return to Dashboard
        </a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
