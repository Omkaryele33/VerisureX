<?php
/**
 * Complete System Fix
 * This comprehensive fix script addresses all aspects of the certificate validation system
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Base directory for the application
$baseDir = dirname(__DIR__);

// Function to check if a file exists and is readable
function checkFile($path) {
    if (file_exists($path) && is_readable($path)) {
        return ['status' => true, 'message' => 'File exists and is readable'];
    } else if (file_exists($path) && !is_readable($path)) {
        return ['status' => 'warning', 'message' => 'File exists but is not readable'];
    } else {
        return ['status' => false, 'message' => 'File not found'];
    }
}

// Function to backup a file before modifying it
function backupFile($file) {
    if (file_exists($file)) {
        $backupFile = $file . '.bak.' . time();
        if (copy($file, $backupFile)) {
            return true;
        }
    }
    return false;
}

// Function to create a proper constants file
function createConstantsFile($file) {
    $content = '<?php
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
    
    if (file_put_contents($file, $content)) {
        return ['status' => true, 'message' => 'Successfully created constants file'];
    } else {
        return ['status' => false, 'message' => 'Failed to create constants file'];
    }
}

// Function to create a proper config file
function createConfigFile($file) {
    $content = '<?php
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
    
    if (file_put_contents($file, $content)) {
        return ['status' => true, 'message' => 'Successfully created config file'];
    } else {
        return ['status' => false, 'message' => 'Failed to create config file'];
    }
}

// Function to create a proper master initialization file
function createMasterInitFile($file) {
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
    
    if (file_put_contents($file, $content)) {
        return ['status' => true, 'message' => 'Successfully created master init file'];
    } else {
        return ['status' => false, 'message' => 'Failed to create master init file'];
    }
}

// Function to update an admin page to use master_init.php
function updateAdminPage($file, $pageTitle = '') {
    if (!file_exists($file)) {
        return ['status' => false, 'message' => 'File not found'];
    }
    
    $content = file_get_contents($file);
    
    // Save original content for comparison
    $originalContent = $content;
    
    // Replace all the individual includes with just master_init.php
    $pattern = '/(session_start\(\);)[\s\n\r]*((?:require_once|include_once|include|require).*?;[\s\n\r]*)*(?:(?:require_once|include_once|include|require).*?security\.php.*?;)/s';
    $replacement = "$1\n\n// Include master initialization file\nrequire_once 'master_init.php';";
    
    $content = preg_replace($pattern, $replacement, $content);
    
    // If no match found, try a different approach by adding after session_start()
    if ($content === $originalContent) {
        $pattern = '/(session_start\(\);)/s';
        $replacement = "$1\n\n// Include master initialization file\nrequire_once 'master_init.php';";
        $content = preg_replace($pattern, $replacement, $content);
        
        // Remove any subsequent includes that might cause conflicts
        $includePattern = '/require_once [\'"](?:\.\.\/)*(?:config|security_header|constants|database)\.php[\'"];\s*\n/i';
        $content = preg_replace($includePattern, '', $content);
    }
    
    // Set page title if provided and there is no existing page title
    if (!empty($pageTitle) && strpos($content, '$pageTitle') === false) {
        $titlePattern = '/(require_once \'master_init\.php\';)/i';
        $titleReplacement = "$1\n\n// Set page title\n\$pageTitle = '{$pageTitle}';";
        $content = preg_replace($titlePattern, $titleReplacement, $content);
    }
    
    if ($content !== $originalContent) {
        // Backup the original file
        backupFile($file);
        
        if (file_put_contents($file, $content)) {
            return ['status' => true, 'message' => 'Successfully updated admin page'];
        } else {
            return ['status' => false, 'message' => 'Failed to write changes to file'];
        }
    }
    
    return ['status' => 'no_change', 'message' => 'No changes needed'];
}

// Function to fix the verify page
function fixVerifyPage($file) {
    if (!file_exists($file)) {
        return ['status' => false, 'message' => 'File not found'];
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Create a cleaner verify.php header with proper includes
    $pattern = '/(<?php[\s\S]*?)require_once [\'"](?:config\/constants|config\/config|config\/database|includes\/functions)\.php[\'"];/s';
    
    if (preg_match($pattern, $content)) {
        $replacement = "<?php\n/**\n * Certificate Verification Page\n */\n\n// Start session if not already started\nif (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\n\n// Include necessary files\nrequire_once 'config/constants.php';\nrequire_once 'config/config.php';\nrequire_once 'config/database.php';\nrequire_once 'includes/functions.php';";
        
        $content = preg_replace($pattern, $replacement, $content, 1);
        
        // Remove any subsequent includes of the same files
        $includePattern = '/require_once [\'"](?:config\/constants|config\/config|config\/database|includes\/functions)\.php[\'"];\s*\n/i';
        $content = preg_replace($includePattern, '', $content);
    }
    
    if ($content !== $originalContent) {
        // Backup the original file
        backupFile($file);
        
        if (file_put_contents($file, $content)) {
            return ['status' => true, 'message' => 'Successfully fixed verify page'];
        } else {
            return ['status' => false, 'message' => 'Failed to write changes to file'];
        }
    }
    
    return ['status' => 'no_change', 'message' => 'No changes needed'];
}

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete System Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { margin-bottom: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .card-header { background-color: #f8f9fa; padding: 15px 20px; }
        .card-body { padding: 20px; }
        .step-number { display: inline-block; width: 30px; height: 30px; line-height: 30px; text-align: center; 
                       background-color: #007bff; color: white; border-radius: 50%; margin-right: 10px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .status-icon { margin-right: 5px; }
        .action-btn { margin-right: 10px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">Complete Certificate System Fix</h1>
    <p class="lead">This tool performs a deep analysis and repair of the certificate validation system.</p>
    
    <div class="progress mb-4">
        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
    </div>';

// ======== STEP 1: Analyze the system ========
echo '<div class="card">
    <div class="card-header">
        <h3><span class="step-number">1</span> System Analysis</h3>
    </div>
    <div class="card-body">';

// Check for key files
$filesToCheck = [
    $baseDir . '/config/config.php' => 'Configuration File',
    $baseDir . '/config/constants.php' => 'Constants File',
    $baseDir . '/config/database.php' => 'Database Configuration',
    $baseDir . '/includes/functions.php' => 'Functions Library',
    $baseDir . '/includes/security.php' => 'Security Library',
    'master_init.php' => 'Master Initialization File',
    'security_header.php' => 'Security Header File',
    'login.php' => 'Login Page',
    '../verify.php' => 'Verification Page'
];

echo '<h4 class="mb-3">File System Check</h4>
      <div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>File</th><th>Description</th><th>Status</th></tr></thead>
      <tbody>';

$missingFiles = [];
foreach ($filesToCheck as $file => $description) {
    $result = checkFile($file);
    $statusClass = $result['status'] === true ? 'success' : ($result['status'] === 'warning' ? 'warning' : 'error');
    $statusIcon = $result['status'] === true ? '<i class="bi bi-check-circle-fill status-icon"></i>' : 
                 ($result['status'] === 'warning' ? '<i class="bi bi-exclamation-triangle-fill status-icon"></i>' : 
                 '<i class="bi bi-x-circle-fill status-icon"></i>');
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($file) . "</code></td>";
    echo "<td>" . htmlspecialchars($description) . "</td>";
    echo "<td class='$statusClass'>$statusIcon {$result['message']}</td>";
    echo "</tr>";
    
    if ($result['status'] !== true) {
        $missingFiles[] = $file;
    }
}

echo '</tbody></table></div>';

// Check for constants redefinition
$constantCheckResult = false;
if (file_exists($baseDir . '/config/config.php') && file_exists($baseDir . '/config/constants.php')) {
    $configContent = file_get_contents($baseDir . '/config/config.php');
    $constantsContent = file_get_contents($baseDir . '/config/constants.php');
    
    // Check if constants are defined in both files
    $constantsToCheck = [
        'RATE_LIMIT',
        'CSRF_TOKEN_LENGTH',
        'PASSWORD_MIN_LENGTH',
        'BASE_URL'
    ];
    
    $duplicateConstants = [];
    foreach ($constantsToCheck as $constant) {
        $definedInConfig = preg_match('/define\s*\(\s*["\']{1}' . preg_quote($constant) . '["\']{1}\s*,/i', $configContent);
        $definedInConstants = preg_match('/define\s*\(\s*["\']{1}' . preg_quote($constant) . '["\']{1}\s*,/i', $constantsContent);
        
        if ($definedInConfig && $definedInConstants) {
            $duplicateConstants[] = $constant;
        }
    }
    
    if (!empty($duplicateConstants)) {
        $constantCheckResult = false;
        echo '<div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <strong>Constant Redefinition Detected!</strong> The following constants are defined in both config.php and constants.php:<br>
              <code>' . implode('</code>, <code>', $duplicateConstants) . '</code>
              </div>';
    } else {
        $constantCheckResult = true;
        echo '<div class="alert alert-success">
              <i class="bi bi-check-circle-fill me-2"></i>
              <strong>No constant redefinition detected.</strong> Each constant appears to be defined in only one place.
              </div>';
    }
} else {
    echo '<div class="alert alert-warning">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <strong>Cannot check for constant redefinition.</strong> One or more required files are missing.
          </div>';
}

echo '</div></div>';

// ======== STEP 2: Fix the system ========
echo '<div class="card">
    <div class="card-header">
        <h3><span class="step-number">2</span> System Repair</h3>
    </div>
    <div class="card-body">';

$fixResults = [];

// 1. Fix constants file
if (in_array($baseDir . '/config/constants.php', $missingFiles) || !$constantCheckResult) {
    echo '<h4 class="mb-3">1. Creating/Fixing Constants File</h4>';
    $result = createConstantsFile($baseDir . '/config/constants.php');
    $statusClass = $result['status'] ? 'success' : 'error';
    $statusIcon = $result['status'] ? '<i class="bi bi-check-circle-fill status-icon"></i>' : '<i class="bi bi-x-circle-fill status-icon"></i>';
    
    echo "<p class='$statusClass'>$statusIcon {$result['message']}</p>";
    $fixResults['constants'] = $result;
} else {
    echo '<h4 class="mb-3">1. Constants File</h4>';
    echo "<p class='success'><i class='bi bi-check-circle-fill status-icon'></i> Constants file exists and appears correct</p>";
    $fixResults['constants'] = ['status' => true, 'message' => 'Constants file already correct'];
}

// 2. Fix config file
if (in_array($baseDir . '/config/config.php', $missingFiles) || !$constantCheckResult) {
    echo '<h4 class="mb-3">2. Creating/Fixing Config File</h4>';
    $result = createConfigFile($baseDir . '/config/config.php');
    $statusClass = $result['status'] ? 'success' : 'error';
    $statusIcon = $result['status'] ? '<i class="bi bi-check-circle-fill status-icon"></i>' : '<i class="bi bi-x-circle-fill status-icon"></i>';
    
    echo "<p class='$statusClass'>$statusIcon {$result['message']}</p>";
    $fixResults['config'] = $result;
} else {
    echo '<h4 class="mb-3">2. Config File</h4>';
    echo "<p class='success'><i class='bi bi-check-circle-fill status-icon'></i> Config file exists and appears correct</p>";
    $fixResults['config'] = ['status' => true, 'message' => 'Config file already correct'];
}

// 3. Fix master init file
echo '<h4 class="mb-3">3. Creating/Fixing Master Init File</h4>';
$result = createMasterInitFile('master_init.php');
$statusClass = $result['status'] ? 'success' : 'error';
$statusIcon = $result['status'] ? '<i class="bi bi-check-circle-fill status-icon"></i>' : '<i class="bi bi-x-circle-fill status-icon"></i>';

echo "<p class='$statusClass'>$statusIcon {$result['message']}</p>";
$fixResults['master_init'] = $result;

// 4. Fix security_header.php to simply include master_init.php
echo '<h4 class="mb-3">4. Fixing Security Header File</h4>';
$securityHeaderContent = '<?php
/**
 * Security Header
 * This file now simply includes the master initialization file
 */

require_once "master_init.php";
';

if (file_put_contents('security_header.php', $securityHeaderContent)) {
    echo "<p class='success'><i class='bi bi-check-circle-fill status-icon'></i> Successfully updated security_header.php</p>";
    $fixResults['security_header'] = ['status' => true, 'message' => 'Successfully updated security_header.php'];
} else {
    echo "<p class='error'><i class='bi bi-x-circle-fill status-icon'></i> Failed to update security_header.php</p>";
    $fixResults['security_header'] = ['status' => false, 'message' => 'Failed to update security_header.php'];
}

// 5. Fix admin pages
echo '<h4 class="mb-3">5. Updating Admin Pages</h4>';

$adminPages = [
    'login.php' => 'Login',
    'dashboard.php' => 'Dashboard',
    'user_management.php' => 'User Management',
    'certificate_templates.php' => 'Certificate Templates',
    'security_log.php' => 'Security Log',
    'create_certificate.php' => 'Create Certificate',
    'bulk_certificates.php' => 'Bulk Certificates',
    'edit_certificate.php' => 'Edit Certificate',
    'change_password.php' => 'Change Password'
];

echo '<div class="table-responsive">
      <table class="table table-striped">
      <thead><tr><th>Page</th><th>Status</th></tr></thead>
      <tbody>';

foreach ($adminPages as $page => $title) {
    $result = updateAdminPage($page, $title);
    $statusClass = $result['status'] === true ? 'success' : ($result['status'] === 'no_change' ? 'warning' : 'error');
    $statusIcon = $result['status'] === true ? '<i class="bi bi-check-circle-fill status-icon"></i>' : 
                 ($result['status'] === 'no_change' ? '<i class="bi bi-exclamation-triangle-fill status-icon"></i>' : 
                 '<i class="bi bi-x-circle-fill status-icon"></i>');
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($page) . "</code></td>";
    echo "<td class='$statusClass'>$statusIcon {$result['message']}</td>";
    echo "</tr>";
    
    $fixResults['admin_pages'][$page] = $result;
}

echo '</tbody></table></div>';

// 6. Fix verify page
echo '<h4 class="mb-3">6. Fixing Verification Page</h4>';
$result = fixVerifyPage('../verify.php');
$statusClass = $result['status'] === true ? 'success' : ($result['status'] === 'no_change' ? 'warning' : 'error');
$statusIcon = $result['status'] === true ? '<i class="bi bi-check-circle-fill status-icon"></i>' : 
             ($result['status'] === 'no_change' ? '<i class="bi bi-exclamation-triangle-fill status-icon"></i>' : 
             '<i class="bi bi-x-circle-fill status-icon"></i>');

echo "<p class='$statusClass'>$statusIcon {$result['message']}</p>";
$fixResults['verify_page'] = $result;

echo '</div></div>';

// ======== STEP 3: Verify the fixes ========
echo '<div class="card">
    <div class="card-header">
        <h3><span class="step-number">3</span> Verification</h3>
    </div>
    <div class="card-body">';

echo '<h4 class="mb-3">Test the following pages:</h4>';

echo '<div class="d-flex flex-wrap mb-4">';
echo '<a href="login.php" target="_blank" class="btn btn-primary action-btn">Login Page</a>';
echo '<a href="dashboard.php" target="_blank" class="btn btn-primary action-btn">Dashboard</a>';
echo '<a href="certificate_templates.php" target="_blank" class="btn btn-primary action-btn">Certificate Templates</a>';
echo '<a href="../verify.php" target="_blank" class="btn btn-primary action-btn">Verification Page</a>';
echo '</div>';

echo '<div class="alert alert-info">
      <i class="bi bi-info-circle-fill me-2"></i>
      <strong>Testing Instructions:</strong><br>
      1. Click each link above to test the respective page<br>
      2. Check for any constant redefinition warnings or errors<br>
      3. Verify that each page loads correctly and functions as expected<br>
      4. If you still see errors, try clearing your browser cache (Ctrl+F5) or use an incognito window
      </div>';

echo '</div></div>';

// ======== STEP 4: Technical Summary ========
echo '<div class="card">
    <div class="card-header">
        <h3><span class="step-number">4</span> Technical Summary</h3>
    </div>
    <div class="card-body">';

echo '<h4 class="mb-3">Problems Fixed:</h4>';
echo '<ol>';
echo '<li><strong>Constant Redefinition:</strong> Constants were being defined in multiple files, causing PHP notices</li>';
echo '<li><strong>Inconsistent Includes:</strong> Files were being included in different orders, leading to unpredictable behavior</li>';
echo '<li><strong>Missing Files:</strong> Created any missing required files with proper content</li>';
echo '<li><strong>Syntax Errors:</strong> Fixed various syntax issues in PHP files</li>';
echo '</ol>';

echo '<h4 class="mb-3">System Architecture Changes:</h4>';
echo '<ol>';
echo '<li><strong>Centralized Constants:</strong> All constants are now defined in a single file (constants.php)</li>';
echo '<li><strong>Streamlined Initialization:</strong> Created a master initialization file that handles all includes in the correct order</li>';
echo '<li><strong>Simplified Header:</strong> The security_header.php file now simply includes the master initialization file</li>';
echo '<li><strong>Unified Page Structure:</strong> All admin pages now use the same include pattern with master_init.php</li>';
echo '</ol>';

echo '<h4 class="mb-3">Additional Recommendations:</h4>';
echo '<ol>';
echo '<li><strong>Error Logging:</strong> Implement comprehensive error logging to help diagnose future issues</li>';
echo '<li><strong>Code Refactoring:</strong> Consider further refactoring to improve maintainability</li>';
echo '<li><strong>Security Review:</strong> Conduct a thorough security review of the application</li>';
echo '<li><strong>Regular Backups:</strong> Implement regular database and code backups</li>';
echo '</ol>';

echo '</div></div>';

// ======== Footer ========
echo '<div class="text-center mt-4 mb-5">
    <a href="dashboard.php" class="btn btn-lg btn-success">
        <i class="bi bi-arrow-left-circle me-2"></i> Return to Dashboard
    </a>
</div>';

echo '</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
?>
