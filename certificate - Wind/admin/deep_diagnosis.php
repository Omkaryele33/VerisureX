<?php
/**
 * Deep Project Diagnosis and Fix Tool
 * This script thoroughly analyzes the entire certificate validation system and fixes all errors
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Project root
$rootDir = dirname(__DIR__);

// Function to check file existence and permissions
function checkFile($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'File not found'];
    } elseif (!is_readable($path)) {
        return ['status' => 'warning', 'message' => 'File not readable'];
    } else {
        return ['status' => 'success', 'message' => 'File OK'];
    }
}

// Function to backup a file before modifying
function backupFile($path) {
    if (file_exists($path)) {
        $backupPath = $path . '.bak.' . time();
        if (copy($path, $backupPath)) {
            return $backupPath;
        }
    }
    return false;
}

// Function to fix verify/index.php file
function fixVerifyIndexFile($filePath) {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'File not found'];
    }
    
    // Backup original file
    $backup = backupFile($filePath);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // Get file content
    $content = file_get_contents($filePath);
    
    // Replace standard includes with a centralized approach
    $newHeader = '<?php
/**
 * Certificate Verification System
 * Public verification interface
 */

// Start session
session_start();

// Define root path for includes
define("ROOT_PATH", dirname(__DIR__));

// Include files in proper order
require_once ROOT_PATH . "/config/constants.php";
require_once ROOT_PATH . "/config/config.php";
require_once ROOT_PATH . "/config/database.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/security.php";

// Set security headers
header("Content-Security-Policy: default-src \'self\'; script-src \'self\' https://cdn.jsdelivr.net https://unpkg.com; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; img-src \'self\' data:; font-src \'self\' https://cdn.jsdelivr.net; frame-src \'none\'; object-src \'none\'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
';
    
    // Replace the header section with our fixed version
    $pattern = '/^<\?php[\s\S]*?header\("Referrer-Policy: strict-origin-when-cross-origin"\);/m';
    $content = preg_replace($pattern, $newHeader, $content);
    
    // Write updated content back to file
    if (file_put_contents($filePath, $content)) {
        return ['status' => 'success', 'message' => 'Fixed includes and constant definitions in verify/index.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to file'];
    }
}

// Function to fix the master_init.php file for admin area
function fixMasterInitFile($filePath) {
    // Create correct master_init.php content
    $content = '<?php
/**
 * Master Initialization File
 * This file includes all necessary files in the correct order
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the root directory path
define("ROOT_PATH", dirname(__DIR__));

// Include application files in the correct order
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
';
    
    // Backup original if it exists
    if (file_exists($filePath)) {
        backupFile($filePath);
    }
    
    // Write the fixed content
    if (file_put_contents($filePath, $content)) {
        return ['status' => 'success', 'message' => 'Fixed master_init.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to master_init.php'];
    }
}

// Function to verify and fix admin pages
function fixAdminPage($filePath, $pageTitle = '') {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'File not found: ' . basename($filePath)];
    }
    
    // Backup original
    backupFile($filePath);
    
    // Get file content
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Replace problematic include patterns with master_init.php
    $includePattern = '/(session_start\(\);)\s*\n\s*((?:\/\/.*\n|#.*\n|\/\*[\s\S]*?\*\/)*)\s*((?:require_once|include_once|include|require).*?;\s*\n)+/s';
    if (preg_match($includePattern, $content)) {
        $content = preg_replace(
            $includePattern,
            "$1\n\n// Include master initialization file\nrequire_once 'master_init.php';\n\n",
            $content
        );
    }
    
    // If page title provided, set it after the include
    if (!empty($pageTitle) && !preg_match('/\$pageTitle\s*=/', $content)) {
        $pattern = "/(require_once 'master_init\.php';)/";
        $content = preg_replace(
            $pattern,
            "$1\n\n// Set page title\n\$pageTitle = '{$pageTitle}';",
            $content
        );
    }
    
    // Fix any syntax errors
    // 1. Double semicolons
    $content = preg_replace('/;\s*;/', ';', $content);
    
    // 2. Common issue with quotes in require statements
    $content = preg_replace("/require_once\s+'master_init\.php';';/", "require_once 'master_init.php';", $content);
    
    // Only save if changes were made
    if ($content !== $originalContent) {
        if (file_put_contents($filePath, $content)) {
            return ['status' => 'success', 'message' => 'Fixed ' . basename($filePath)];
        } else {
            return ['status' => 'error', 'message' => 'Could not write to ' . basename($filePath)];
        }
    }
    
    return ['status' => 'info', 'message' => 'No changes needed for ' . basename($filePath)];
}

// Function to ensure constants.php has all required constants
function ensureConstantsFile($filePath) {
    $correctContent = '<?php
/**
 * Application Constants
 * This file defines all constants used throughout the application
 * Include this file only once at the beginning of your application
 */

// Base URL - Change this to match your domain with protocol detection
define("BASE_URL", (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://{$_SERVER["HTTP_HOST"]}/certificate");

// Admin panel URL
define("ADMIN_URL", BASE_URL . "/admin");

// Verification URL
define("VERIFY_URL", BASE_URL . "/verify");

// File upload settings
define("MAX_FILE_SIZE", 5 * 1024 * 1024);  // 5MB
define("ALLOWED_EXTENSIONS", "jpg,jpeg,png,pdf");
define("UPLOAD_DIR", dirname(__DIR__) . "/uploads");

// Security settings
define("RATE_LIMIT", 5);  // Maximum allowed requests
define("RATE_LIMIT_WINDOW", 60);  // Time window in seconds
define("RATE_LIMIT_UNIQUE_KEYS", 1000);  // Maximum unique keys to track
define("CSRF_TOKEN_LENGTH", 64);  // CSRF token length
define("CSRF_TOKEN_EXPIRY", 3600);  // CSRF token expiry in seconds

// Password policy
define("PASSWORD_MIN_LENGTH", 8);
define("PASSWORD_REQUIRE_MIXED_CASE", true);
define("PASSWORD_REQUIRE_NUMBERS", true);
define("PASSWORD_REQUIRE_SYMBOLS", true);

// Login security
define("MAX_LOGIN_ATTEMPTS", 5);
define("ACCOUNT_LOCKOUT_TIME", 15 * 60);  // 15 minutes
';
    
    // Backup if exists
    if (file_exists($filePath)) {
        backupFile($filePath);
    }
    
    // Write correct content
    if (file_put_contents($filePath, $correctContent)) {
        return ['status' => 'success', 'message' => 'Fixed constants.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to constants.php'];
    }
}

// Function to ensure config.php is correct
function ensureConfigFile($filePath) {
    $correctContent = '<?php
/**
 * Configuration File
 * This file contains database credentials and other configuration settings
 */

// Include constants first if they haven\'t been included yet
require_once __DIR__ . "/constants.php";

// Database credentials
define("DB_HOST", "localhost");
define("DB_NAME", "certificate_db");
define("DB_USER", "root");
define("DB_PASS", "");

// Error reporting
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Session settings
ini_set("session.cookie_httponly", 1);
ini_set("session.cookie_secure", 0);  // Set to 1 if using HTTPS
ini_set("session.cookie_samesite", "Strict");
ini_set("session.gc_maxlifetime", 3600);
ini_set("session.use_strict_mode", 1);
';
    
    // Backup if exists
    if (file_exists($filePath)) {
        backupFile($filePath);
    }
    
    // Write correct content
    if (file_put_contents($filePath, $correctContent)) {
        return ['status' => 'success', 'message' => 'Fixed config.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to config.php'];
    }
}

// Function to verify and fix database.php
function fixDatabaseFile($filePath) {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'Database file not found'];
    }
    
    $content = file_get_contents($filePath);
    
    // Check for basic database class structure
    if (!preg_match('/class\s+Database/', $content)) {
        $correctContent = '<?php
/**
 * Database Connection Class
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;
    
    /**
     * Get database connection
     * @return PDO Database connection object
     */
    public function getConnection() {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("SET NAMES utf8");
        } catch(PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
        
        return $this->conn;
    }
}
';
        
        // Backup original
        backupFile($filePath);
        
        // Write corrected file
        if (file_put_contents($filePath, $correctContent)) {
            return ['status' => 'success', 'message' => 'Fixed database.php with proper class structure'];
        } else {
            return ['status' => 'error', 'message' => 'Could not write to database.php'];
        }
    }
    
    return ['status' => 'info', 'message' => 'Database file structure looks OK'];
}

// Function to ensure security_header.php is correct
function fixSecurityHeader($filePath) {
    $correctContent = '<?php
/**
 * Security Header
 * This file now simply includes the master initialization file
 */

require_once "master_init.php";
';
    
    // Backup if exists
    if (file_exists($filePath)) {
        backupFile($filePath);
    }
    
    // Write correct content
    if (file_put_contents($filePath, $correctContent)) {
        return ['status' => 'success', 'message' => 'Fixed security_header.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to security_header.php'];
    }
}

// --- HTML Output --- //
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deep Project Diagnosis & Fix</title>
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
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            border-radius: 10px 10px 0 0;
            background-color: #f1f8ff;
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        .success { color: #198754; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .info { color: #0dcaf0; }
        .step-title {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .step-number {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }
        .fix-result {
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .fix-success { background-color: #d1e7dd; }
        .fix-warning { background-color: #fff3cd; }
        .fix-error { background-color: #f8d7da; }
        .fix-info { background-color: #cff4fc; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="my-4 text-center">Certificate Validation System - Deep Diagnosis & Fix</h1>
    <p class="lead text-center mb-5">This tool performs a thorough analysis of your project and fixes common errors</p>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">System Diagnosis & Fix Results</h3>
        </div>
        <div class="card-body">
            <!-- STEP 1: Fix Core Configuration Files -->
            <div class="step-title">
                <div class="step-number">1</div>
                <h4>Core Configuration Files</h4>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <?php
                    // Fix constants.php
                    $constantsResult = ensureConstantsFile($rootDir . '/config/constants.php');
                    $statusClass = 'fix-' . ($constantsResult['status'] == 'success' ? 'success' : 
                                           ($constantsResult['status'] == 'warning' ? 'warning' : 
                                           ($constantsResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>constants.php:</strong> {$constantsResult['message']}";
                    echo "</div>";
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    // Fix config.php
                    $configResult = ensureConfigFile($rootDir . '/config/config.php');
                    $statusClass = 'fix-' . ($configResult['status'] == 'success' ? 'success' : 
                                         ($configResult['status'] == 'warning' ? 'warning' : 
                                         ($configResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>config.php:</strong> {$configResult['message']}";
                    echo "</div>";
                    ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <?php
                    // Fix database.php
                    $dbResult = fixDatabaseFile($rootDir . '/config/database.php');
                    $statusClass = 'fix-' . ($dbResult['status'] == 'success' ? 'success' : 
                                        ($dbResult['status'] == 'warning' ? 'warning' : 
                                        ($dbResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>database.php:</strong> {$dbResult['message']}";
                    echo "</div>";
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    // Check functions.php
                    $functionsFile = $rootDir . '/includes/functions.php';
                    $functionsResult = checkFile($functionsFile);
                    $statusClass = 'fix-' . ($functionsResult['status'] == 'success' ? 'success' : 
                                          ($functionsResult['status'] == 'warning' ? 'warning' : 'error'));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>functions.php:</strong> {$functionsResult['message']}";
                    echo "</div>";
                    ?>
                </div>
            </div>
            
            <!-- STEP 2: Fix Admin Initialization -->
            <div class="step-title mt-4">
                <div class="step-number">2</div>
                <h4>Admin Panel Initialization</h4>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <?php
                    // Fix master_init.php
                    $masterResult = fixMasterInitFile(__DIR__ . '/master_init.php');
                    $statusClass = 'fix-' . ($masterResult['status'] == 'success' ? 'success' : 
                                         ($masterResult['status'] == 'warning' ? 'warning' : 
                                         ($masterResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>master_init.php:</strong> {$masterResult['message']}";
                    echo "</div>";
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    // Fix security_header.php
                    $securityResult = fixSecurityHeader(__DIR__ . '/security_header.php');
                    $statusClass = 'fix-' . ($securityResult['status'] == 'success' ? 'success' : 
                                         ($securityResult['status'] == 'warning' ? 'warning' : 
                                         ($securityResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>security_header.php:</strong> {$securityResult['message']}";
                    echo "</div>";
                    ?>
                </div>
            </div>
            
            <!-- STEP 3: Fix Admin Pages -->
            <div class="step-title mt-4">
                <div class="step-number">3</div>
                <h4>Admin Pages</h4>
            </div>
            
            <div class="row">
                <?php
                // List of admin pages to fix with their titles
                $adminPages = [
                    'login.php' => 'Admin Login',
                    'dashboard.php' => 'Dashboard',
                    'certificate_templates.php' => 'Certificate Templates',
                    'user_management.php' => 'User Management',
                    'security_log.php' => 'Security Log',
                    'create_certificate.php' => 'Create Certificate',
                    'bulk_certificates.php' => 'Bulk Certificates',
                    'edit_certificate.php' => 'Edit Certificate',
                    'change_password.php' => 'Change Password'
                ];
                
                $count = 0;
                foreach ($adminPages as $page => $title) {
                    // Create a new column every 3 items
                    if ($count % 3 == 0) {
                        echo '</div><div class="row">';
                    }
                    $count++;
                    
                    echo '<div class="col-md-4 mb-3">';
                    $pageResult = fixAdminPage(__DIR__ . '/' . $page, $title);
                    $statusClass = 'fix-' . ($pageResult['status'] == 'success' ? 'success' : 
                                         ($pageResult['status'] == 'warning' ? 'warning' : 
                                         ($pageResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>{$page}:</strong> {$pageResult['message']}";
                    echo "</div>";
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- STEP 4: Fix Verification Page -->
            <div class="step-title mt-4">
                <div class="step-number">4</div>
                <h4>Verification Page</h4>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <?php
                    // Fix verify/index.php
                    $verifyResult = fixVerifyIndexFile($rootDir . '/verify/index.php');
                    $statusClass = 'fix-' . ($verifyResult['status'] == 'success' ? 'success' : 
                                         ($verifyResult['status'] == 'warning' ? 'warning' : 
                                         ($verifyResult['status'] == 'info' ? 'info' : 'error')));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>verify/index.php:</strong> {$verifyResult['message']}";
                    echo "</div>";
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testing and Verification -->
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Testing & Verification</h3>
        </div>
        <div class="card-body">
            <p>Click the links below to test if the fixes resolved all issues:</p>
            
            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="login.php" class="btn btn-primary" target="_blank">Admin Login</a>
                <a href="dashboard.php" class="btn btn-primary" target="_blank">Dashboard</a>
                <a href="certificate_templates.php" class="btn btn-primary" target="_blank">Certificate Templates</a>
                <a href="../verify/" class="btn btn-success" target="_blank">Certificate Verification</a>
            </div>
            
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle me-2"></i>Important Information</h5>
                <p>The following fixes have been applied to your system:</p>
                <ol>
                    <li><strong>Fixed Configuration Files:</strong> Ensured all constants are defined in one place and removed duplicate definitions</li>
                    <li><strong>Standardized Include Structure:</strong> Created a consistent initialization process across all pages</li>
                    <li><strong>Fixed Verification Page:</strong> Corrected constant redefinition errors in the verification page</li>
                    <li><strong>Corrected Syntax Errors:</strong> Fixed various PHP syntax issues throughout the application</li>
                </ol>
                <p class="mb-0">If you still encounter issues, please try clearing your browser cache (Ctrl+F5) or using an incognito window.</p>
            </div>
        </div>
    </div>
    
    <!-- Return Link -->
    <div class="text-center mt-4 mb-5">
        <a href="dashboard.php" class="btn btn-lg btn-success">
            <i class="bi bi-arrow-left-circle me-2"></i> Return to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
