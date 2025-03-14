<?php
/**
 * Dashboard.php Fix
 * This script fixes session and duplicate constant issues
 */

// Set error reporting for diagnostics
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Project paths
$adminDir = __DIR__;
$rootDir = dirname(__DIR__);

// Backup original files
function backupFile($path) {
    if (file_exists($path)) {
        $backupPath = $path . '.bak.' . time();
        return copy($path, $backupPath) ? $backupPath : false;
    }
    return false;
}

// Fix dashboard.php
function fixDashboardFile($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'Dashboard file not found'];
    }
    
    // Backup original
    $backup = backupFile($path);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // Get content
    $content = file_get_contents($path);
    
    // Fix duplicate include of master_init.php
    $content = preg_replace(
        "/\/\/ Include master initialization file\s*require_once 'master_init\.php';\s*\/\/ Include master initialization file\s*require_once 'master_init\.php';/",
        "// Include master initialization file\nrequire_once 'master_init.php';",
        $content
    );
    
    // Write fixed content
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed duplicate includes in dashboard.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to dashboard.php'];
    }
}

// Fix config.php to move session settings before session start
function fixConfigFile($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'Config file not found'];
    }
    
    // Backup original
    $backup = backupFile($path);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // Create new config content with session settings at the top
    $content = "<?php\n/**\n * Configuration File\n * This file contains database credentials and other configuration settings\n */\n\n// Session settings - Must be before session_start\nini_set(\"session.cookie_httponly\", 1);\nini_set(\"session.cookie_secure\", 0);  // Set to 1 if using HTTPS\nini_set(\"session.cookie_samesite\", \"Strict\");\nini_set(\"session.gc_maxlifetime\", 3600);\nini_set(\"session.use_strict_mode\", 1);\n\n// Include constants first if they haven't been included yet\nrequire_once __DIR__ . \"/constants.php\";\n\n// Database credentials - only define if not already defined\nif (!defined('DB_HOST')) {\n    define(\"DB_HOST\", \"localhost\");\n}\nif (!defined('DB_NAME')) {\n    define(\"DB_NAME\", \"certificate_db\");\n}\nif (!defined('DB_USER')) {\n    define(\"DB_USER\", \"root\");\n}\nif (!defined('DB_PASS')) {\n    define(\"DB_PASS\", \"\");\n}\n\n// Error reporting\nini_set(\"display_errors\", 1);\nini_set(\"display_startup_errors\", 1);\nerror_reporting(E_ALL);\n";
    
    // Write fixed content
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed session settings in config.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to config.php'];
    }
}

// Fix master_init.php to handle session start and db constants correctly
function fixMasterInitFile($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'Master init file not found'];
    }
    
    // Backup original
    $backup = backupFile($path);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // New content with improved session and constant handling
    $content = "<?php\n/**\n * Master Initialization File\n * This file includes all necessary files in the correct order\n */\n\n// Define the root directory path\ndefine(\"ROOT_PATH\", dirname(__DIR__));\n\n// Include config (which contains session settings) before starting session\nrequire_once ROOT_PATH . \"/config/constants.php\";\nrequire_once ROOT_PATH . \"/config/config.php\";\n\n// Start session if not already started\nif (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\n\n// Include other core files\nrequire_once ROOT_PATH . \"/config/database.php\";\nrequire_once ROOT_PATH . \"/includes/functions.php\";\nrequire_once ROOT_PATH . \"/includes/security.php\";\n\n// Connect to database\ntry {\n    $database = new Database();\n    $db = $database->getConnection();\n} catch (Exception $e) {\n    // Handle database connection error gracefully\n    die(\"Database connection error: \" . $e->getMessage());\n}\n";
    
    // Write fixed content
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed master_init.php with proper session handling'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to master_init.php'];
    }
}

// Fix secure_config/db_config.php to be compatible
function fixDbConfigFile($path) {
    if (!file_exists($path)) {
        return ['status' => 'error', 'message' => 'DB config file not found'];
    }
    
    // Backup original
    $backup = backupFile($path);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // Create new content with conditional defines
    $content = "<?php\n/**\n * Secure Database Credentials\n * This file should be placed outside the web root in production\n */\n\n// Database settings - only define if not already defined\nif (!defined('DB_HOST')) {\n    define('DB_HOST', 'localhost');\n}\nif (!defined('DB_NAME')) {\n    define('DB_NAME', 'certificate_system');\n}\nif (!defined('DB_USER')) {\n    define('DB_USER', 'root');\n}\nif (!defined('DB_PASS')) {\n    define('DB_PASS', '');\n}\n\n// Additional security settings\ndefine('HASH_SECRET', '8b7df143d91c716ecfa5fc1730022f6b'); // Random secret for hashing\ndefine('ENCRYPTION_KEY', '4d6783e91af4e3dfa5fc1720022f6ab'); // For encrypting sensitive data\n?>";
    
    // Write fixed content
    if (file_put_contents($path, $content)) {
        return ['status' => 'success', 'message' => 'Fixed secure_config/db_config.php'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to db_config.php'];
    }
}

// Apply all fixes
$dashboardResult = fixDashboardFile(__DIR__ . '/dashboard.php');
$configResult = fixConfigFile($rootDir . '/config/config.php');
$masterInitResult = fixMasterInitFile(__DIR__ . '/master_init.php');
$dbConfigResult = fixDbConfigFile($rootDir . '/secure_config/db_config.php');

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { margin-bottom: 20px; border-radius: 10px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .card-header { border-radius: 10px 10px 0 0; background-color: #f1f8ff; }
        .success { color: #198754; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="my-4 text-center">Dashboard.php Error Fix</h1>
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Fix Results</h4>
        </div>
        <div class="card-body">
            <div class="alert <?php echo $dashboardResult['status'] == 'success' ? 'alert-success' : ($dashboardResult['status'] == 'warning' ? 'alert-warning' : 'alert-danger'); ?>">
                <strong>Dashboard.php:</strong> <?php echo $dashboardResult['message']; ?>
            </div>
            
            <div class="alert <?php echo $configResult['status'] == 'success' ? 'alert-success' : ($configResult['status'] == 'warning' ? 'alert-warning' : 'alert-danger'); ?>">
                <strong>config.php:</strong> <?php echo $configResult['message']; ?>
            </div>
            
            <div class="alert <?php echo $masterInitResult['status'] == 'success' ? 'alert-success' : ($masterInitResult['status'] == 'warning' ? 'alert-warning' : 'alert-danger'); ?>">
                <strong>master_init.php:</strong> <?php echo $masterInitResult['message']; ?>
            </div>
            
            <div class="alert <?php echo $dbConfigResult['status'] == 'success' ? 'alert-success' : ($dbConfigResult['status'] == 'warning' ? 'alert-warning' : 'alert-danger'); ?>">
                <strong>secure_config/db_config.php:</strong> <?php echo $dbConfigResult['message']; ?>
            </div>
            
            <div class="alert alert-info">
                <h5>Issues Fixed:</h5>
                <ol>
                    <li><strong>Session Setting Error:</strong> Moved session settings before session_start in the proper files</li>
                    <li><strong>Duplicate Constants:</strong> Updated DB_HOST, DB_NAME, etc. to only define if not already defined</li>
                    <li><strong>Duplicate Includes:</strong> Removed duplicate include of master_init.php in dashboard.php</li>
                    <li><strong>Initialization Order:</strong> Corrected the order of file includes to prevent errors</li>
                </ol>
            </div>
            
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-primary">Test Dashboard</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
