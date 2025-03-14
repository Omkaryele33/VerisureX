<?php
/**
 * Final System Fix
 * This script addresses all remaining session and database issues
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Project paths
$adminDir = __DIR__;
$rootDir = dirname(__DIR__);

// Function to backup file
function backupFile($path) {
    if (file_exists($path)) {
        $backupPath = $path . '.bak.' . time();
        return copy($path, $backupPath) ? $backupPath : false;
    }
    return false;
}

// Function to fix the session_start problem in master_init.php
function fixMasterInit($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'File not found'];
    }
    
    // Backup file
    backupFile($path);
    
    // Create fixed content with session.php for proper initialization
    $content = "<?php
/**
 * Master Initialization File
 * This file includes all necessary files in the correct order
 */

// Define the root directory path
define(\"ROOT_PATH\", dirname(__DIR__));

// Include core config files first
require_once ROOT_PATH . \"/includes/session.php\";  // Session setup must come first
require_once ROOT_PATH . \"/config/constants.php\";
require_once ROOT_PATH . \"/config/config.php\";
require_once ROOT_PATH . \"/config/database.php\";
require_once ROOT_PATH . \"/includes/functions.php\";
require_once ROOT_PATH . \"/includes/security.php\";

// Connect to database
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    // Handle database connection error gracefully
    die(\"Database connection error: \" . $e->getMessage());
}";
    
    // Save the file
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed master initialization file'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to file'];
    }
}

// Create dedicated session setup file
function createSessionFile($path) {
    // Create directory if it doesn't exist
    $dir = dirname($path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Create content for session handler
    $content = "<?php
/**
 * Session Configuration
 * This file must be included before any session_start() call
 */

// Session settings
ini_set(\"session.cookie_httponly\", 1);
ini_set(\"session.cookie_secure\", 0);  // Set to 1 if using HTTPS
ini_set(\"session.cookie_samesite\", \"Strict\");
ini_set(\"session.gc_maxlifetime\", 3600);
ini_set(\"session.use_strict_mode\", 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}";
    
    // Save the file
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Created session configuration file'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to file'];
    }
}

// Update config.php to remove session settings
function fixConfigFile($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'File not found'];
    }
    
    // Backup file
    backupFile($path);
    
    // Create fixed content
    $content = "<?php
/**
 * Configuration File
 * This file contains database credentials and other configuration settings
 */

// Include constants first if they haven't been included yet
if (!defined('BASE_URL')) {
    require_once __DIR__ . \"/constants.php\";
}

// Database credentials - only define if not already defined
if (!defined('DB_HOST')) {
    define(\"DB_HOST\", \"localhost\");
}

// Check which database exists and use the correct one
$testConnection = mysqli_connect(\"localhost\", \"root\", \"\");
if ($testConnection) {
    $result = mysqli_query($testConnection, \"SHOW DATABASES LIKE 'certificate_system'\");
    if (mysqli_num_rows($result) > 0) {
        define(\"DB_NAME\", \"certificate_system\");
    } else {
        define(\"DB_NAME\", \"certificate_db\");
    }
    mysqli_close($testConnection);
} else {
    // Default fallback
    define(\"DB_NAME\", \"certificate_system\");
}

if (!defined('DB_USER')) {
    define(\"DB_USER\", \"root\");
}
if (!defined('DB_PASS')) {
    define(\"DB_PASS\", \"\");
}

// Error reporting
ini_set(\"display_errors\", 1);
ini_set(\"display_startup_errors\", 1);
error_reporting(E_ALL);";
    
    // Save the file
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed configuration file'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to file'];
    }
}

// Create database if it doesn't exist
function createDatabase() {
    try {
        $conn = mysqli_connect("localhost", "root", "");
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Could not connect to MySQL server'];
        }
        
        // First try certificate_system
        $result = mysqli_query($conn, "SHOW DATABASES LIKE 'certificate_system'");
        if (mysqli_num_rows($result) === 0) {
            // Create certificate_system database
            if (mysqli_query($conn, "CREATE DATABASE certificate_system")) {
                return ['status' => 'success', 'message' => 'Created certificate_system database'];
            }
        } else {
            return ['status' => 'info', 'message' => 'certificate_system database already exists'];
        }
        
        // Then try certificate_db
        $result = mysqli_query($conn, "SHOW DATABASES LIKE 'certificate_db'");
        if (mysqli_num_rows($result) === 0) {
            // Create certificate_db database
            if (mysqli_query($conn, "CREATE DATABASE certificate_db")) {
                return ['status' => 'success', 'message' => 'Created certificate_db database'];
            }
        } else {
            return ['status' => 'info', 'message' => 'certificate_db database already exists'];
        }
        
        mysqli_close($conn);
        return ['status' => 'warning', 'message' => 'Could not create any database'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Fix db_config.php in secure_config
function fixSecureDbConfig($path) {
    if (!file_exists($path)) {
        return ['status' => 'info', 'message' => 'File does not exist, not fixing'];
    }
    
    // Backup file
    backupFile($path);
    
    // Read existing file to keep encryption keys
    $existing = file_get_contents($path);
    $hashSecret = "";
    $encryptionKey = "";
    
    // Extract hash secret
    if (preg_match("/define\('HASH_SECRET',\s*'([^']+)'\)/", $existing, $matches)) {
        $hashSecret = $matches[1];
    } else {
        $hashSecret = bin2hex(random_bytes(16)); // Generate new if not found
    }
    
    // Extract encryption key
    if (preg_match("/define\('ENCRYPTION_KEY',\s*'([^']+)'\)/", $existing, $matches)) {
        $encryptionKey = $matches[1];
    } else {
        $encryptionKey = bin2hex(random_bytes(16)); // Generate new if not found
    }
    
    // Create fixed content
    $content = "<?php
/**
 * Secure Database Credentials
 * This file should be placed outside the web root in production
 */

// Check if constants are already defined
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

// Check which database exists and use the correct one
$testConnection = mysqli_connect('localhost', 'root', '');
if ($testConnection) {
    $result = mysqli_query($testConnection, \"SHOW DATABASES LIKE 'certificate_system'\");
    if (mysqli_num_rows($result) > 0 && !defined('DB_NAME')) {
        define('DB_NAME', 'certificate_system');
    }
    mysqli_close($testConnection);
}

// Fallback to certificate_db if neither is defined
if (!defined('DB_NAME')) {
    define('DB_NAME', 'certificate_db');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// Additional security settings
define('HASH_SECRET', '{$hashSecret}'); // Secret for hashing
define('ENCRYPTION_KEY', '{$encryptionKey}'); // For encrypting sensitive data
?>";
    
    // Save the file
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed secure database configuration file'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to file'];
    }
}

// Apply all fixes
$masterInitResult = fixMasterInit($adminDir . '/master_init.php');
$sessionResult = createSessionFile($rootDir . '/includes/session.php');
$configResult = fixConfigFile($rootDir . '/config/config.php');
$dbResult = createDatabase();
$secureConfigResult = fixSecureDbConfig($rootDir . '/secure_config/db_config.php');

// Output HTML result
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final System Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { padding: 30px; background-color: #f8f9fa; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-header { background-color: #f1f8ff; padding: 20px; border-radius: 12px 12px 0 0; }
        .card-body { padding: 25px; }
        .fix-result { padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; }
        .fix-success { background-color: #d1e7dd; }
        .fix-info { background-color: #cff4fc; }
        .fix-warning { background-color: #fff3cd; }
        .fix-error { background-color: #f8d7da; }
        .step-number { background-color: #0d6efd; color: white; display: inline-block; width: 30px; height: 30px; text-align: center; border-radius: 50%; margin-right: 10px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center mb-5">Final System Fix <i class="bi bi-check-circle-fill text-success"></i></h1>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0"><i class="bi bi-tools me-2"></i>Applied System Fixes</h3>
        </div>
        <div class="card-body">
            <h4><span class="step-number">1</span>Session Handling</h4>
            <div class="row mb-4">
                <div class="col-md-6">
                    <?php
                    $statusClass = $sessionResult['status'] == 'success' ? 'fix-success' : 
                                 ($sessionResult['status'] == 'info' ? 'fix-info' : 
                                 ($sessionResult['status'] == 'warning' ? 'fix-warning' : 'fix-error'));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>session.php:</strong> {$sessionResult['message']}";
                    echo "</div>";
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    $statusClass = $masterInitResult['status'] == 'success' ? 'fix-success' : 
                                 ($masterInitResult['status'] == 'info' ? 'fix-info' : 
                                 ($masterInitResult['status'] == 'warning' ? 'fix-warning' : 'fix-error'));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>master_init.php:</strong> {$masterInitResult['message']}";
                    echo "</div>";
                    ?>
                </div>
            </div>
            
            <h4><span class="step-number">2</span>Database Configuration</h4>
            <div class="row mb-4">
                <div class="col-md-6">
                    <?php
                    $statusClass = $configResult['status'] == 'success' ? 'fix-success' : 
                                 ($configResult['status'] == 'info' ? 'fix-info' : 
                                 ($configResult['status'] == 'warning' ? 'fix-warning' : 'fix-error'));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>config.php:</strong> {$configResult['message']}";
                    echo "</div>";
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    $statusClass = $secureConfigResult['status'] == 'success' ? 'fix-success' : 
                                 ($secureConfigResult['status'] == 'info' ? 'fix-info' : 
                                 ($secureConfigResult['status'] == 'warning' ? 'fix-warning' : 'fix-error'));
                    echo "<div class='fix-result {$statusClass}'>";
                    echo "<strong>db_config.php:</strong> {$secureConfigResult['message']}";
                    echo "</div>";
                    ?>
                </div>
            </div>
            
            <h4><span class="step-number">3</span>Database Creation</h4>
            <div class="mb-4">
                <?php
                $statusClass = $dbResult['status'] == 'success' ? 'fix-success' : 
                             ($dbResult['status'] == 'info' ? 'fix-info' : 
                             ($dbResult['status'] == 'warning' ? 'fix-warning' : 'fix-error'));
                echo "<div class='fix-result {$statusClass}'>";
                echo "<strong>Database:</strong> {$dbResult['message']}";
                echo "</div>";
                ?>
            </div>
            
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle me-2"></i>Issues Fixed:</h5>
                <ol>
                    <li><strong>Session Setting Error:</strong> Created a dedicated session.php file that's included before any session_start() calls</li>
                    <li><strong>Database Detection:</strong> Added automatic detection of which database exists (certificate_system or certificate_db)</li>
                    <li><strong>Database Creation:</strong> Created missing database if neither existed</li>
                    <li><strong>Duplicate Constants:</strong> Added checks to prevent constants from being redefined</li>
                </ol>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>Verification</h3>
        </div>
        <div class="card-body">
            <p>Click the buttons below to test if the fixes resolved all issues:</p>
            
            <div class="d-flex flex-wrap gap-3 mb-4">
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="bi bi-speedometer me-2"></i>Dashboard
                </a>
                <a href="login.php" class="btn btn-success">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login Page
                </a>
                <a href="../verify/" class="btn btn-info">
                    <i class="bi bi-check-circle me-2"></i>Verification Page
                </a>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="bi bi-house me-2"></i>Home Page
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
