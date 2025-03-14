<?php
/**
 * Final System Correction
 * This script fixes all remaining issues in the system including variable initialization
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Project paths
$adminDir = __DIR__;
$rootDir = dirname(__DIR__);

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

// Fix master_init.php with proper variable declarations
function fixMasterInitFile($filePath) {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'Master init file not found'];
    }
    
    // Backup original
    $backup = backupFile($filePath);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // New properly formatted content
    $content = "<?php\n/**\n * Master Initialization File\n * This file includes all necessary files in the correct order\n */\n\n// Define the root directory path\ndefine(\"ROOT_PATH\", dirname(__DIR__));\n\n// Include core config files first\nrequire_once ROOT_PATH . \"/includes/session.php\";  // Session setup must come first\nrequire_once ROOT_PATH . \"/config/constants.php\";\nrequire_once ROOT_PATH . \"/config/config.php\";\nrequire_once ROOT_PATH . \"/config/database.php\";\nrequire_once ROOT_PATH . \"/includes/functions.php\";\nrequire_once ROOT_PATH . \"/includes/security.php\";\n\n// Connect to database\ntry {\n    \$database = new Database();\n    \$db = \$database->getConnection();\n} catch (Exception \$e) {\n    // Handle database connection error gracefully\n    die(\"Database connection error: \" . \$e->getMessage());\n}";
    
    // Write fixed content
    if (file_put_contents($filePath, $content)) {
        return ['status' => 'success', 'message' => 'Fixed master_init.php with proper variable declarations'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to master_init.php'];
    }
}

// Create a dedicated session configuration file
function createSessionFile($filePath) {
    // Create directory if it doesn't exist
    $dir = dirname($filePath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Create content with proper session handling
    $content = "<?php\n/**\n * Session Configuration\n * This file must be included before any session_start() call\n */\n\n// Session settings\nini_set(\"session.cookie_httponly\", 1);\nini_set(\"session.cookie_secure\", 0);  // Set to 1 if using HTTPS\nini_set(\"session.cookie_samesite\", \"Strict\");\nini_set(\"session.gc_maxlifetime\", 3600);\nini_set(\"session.use_strict_mode\", 1);\n\n// Start session if not already started\nif (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}";
    
    // Save the file
    if (file_put_contents($filePath, $content)) {
        return ['status' => 'success', 'message' => 'Created session configuration file'];
    } else {
        return ['status' => 'error', 'message' => 'Could not create session file'];
    }
}

// Fix config.php to properly handle database detection
function fixConfigFile($filePath) {
    if (!file_exists($filePath)) {
        return ['status' => 'error', 'message' => 'Config file not found'];
    }
    
    // Backup original
    $backup = backupFile($filePath);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // Improved content with proper variable initialization
    $content = "<?php\n/**\n * Configuration File\n * This file contains database credentials and other configuration settings\n */\n\n// Include constants first if they haven't been included yet\nif (!defined('BASE_URL')) {\n    require_once __DIR__ . \"/constants.php\";\n}\n\n// Database credentials - only define if not already defined\nif (!defined('DB_HOST')) {\n    define(\"DB_HOST\", \"localhost\");\n}\n\n// Check which database exists and use the correct one\n\$testConnection = null;\n\$result = null;\ntry {\n    \$testConnection = mysqli_connect(\"localhost\", \"root\", \"\");\n    if (\$testConnection) {\n        \$result = mysqli_query(\$testConnection, \"SHOW DATABASES LIKE 'certificate_system'\");\n        if (\$result && mysqli_num_rows(\$result) > 0) {\n            if (!defined('DB_NAME')) {\n                define(\"DB_NAME\", \"certificate_system\");\n            }\n        } else {\n            if (!defined('DB_NAME')) {\n                define(\"DB_NAME\", \"certificate_db\");\n            }\n        }\n        mysqli_close(\$testConnection);\n    } else {\n        // Default fallback\n        if (!defined('DB_NAME')) {\n            define(\"DB_NAME\", \"certificate_system\");\n        }\n    }\n} catch (Exception \$e) {\n    // Handle any connection errors silently and use default\n    if (!defined('DB_NAME')) {\n        define(\"DB_NAME\", \"certificate_system\");\n    }\n}\n\nif (!defined('DB_USER')) {\n    define(\"DB_USER\", \"root\");\n}\nif (!defined('DB_PASS')) {\n    define(\"DB_PASS\", \"\");\n}\n\n// Error reporting\nini_set(\"display_errors\", 1);\nini_set(\"display_startup_errors\", 1);\nerror_reporting(E_ALL);";
    
    // Save the file
    if (file_put_contents($filePath, $content)) {
        return ['status' => 'success', 'message' => 'Fixed config.php with proper variable handling'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to config.php'];
    }
}

// Create or verify database existence
function createDatabase() {
    // Initialize variables to avoid undefined errors
    $conn = null;
    $result = null;
    
    try {
        $conn = mysqli_connect("localhost", "root", "");
        if (!$conn) {
            return ['status' => 'error', 'message' => 'Could not connect to MySQL server'];
        }
        
        // First try certificate_system
        $result = mysqli_query($conn, "SHOW DATABASES LIKE 'certificate_system'");
        if ($result && mysqli_num_rows($result) === 0) {
            // Create certificate_system database
            if (mysqli_query($conn, "CREATE DATABASE certificate_system")) {
                mysqli_close($conn);
                return ['status' => 'success', 'message' => 'Created certificate_system database'];
            }
        } else {
            mysqli_close($conn);
            return ['status' => 'info', 'message' => 'certificate_system database already exists'];
        }
        
        // Then try certificate_db
        $conn = mysqli_connect("localhost", "root", "");
        $result = mysqli_query($conn, "SHOW DATABASES LIKE 'certificate_db'");
        if ($result && mysqli_num_rows($result) === 0) {
            // Create certificate_db database
            if (mysqli_query($conn, "CREATE DATABASE certificate_db")) {
                mysqli_close($conn);
                return ['status' => 'success', 'message' => 'Created certificate_db database'];
            }
        } else {
            mysqli_close($conn);
            return ['status' => 'info', 'message' => 'certificate_db database already exists'];
        }
        
        if ($conn) {
            mysqli_close($conn);
        }
        return ['status' => 'warning', 'message' => 'Could not create any database'];
    } catch (Exception $e) {
        if ($conn) {
            mysqli_close($conn);
        }
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Fix db_config.php in secure_config
function fixSecureDbConfig($filePath) {
    if (!file_exists($filePath)) {
        return ['status' => 'info', 'message' => 'File does not exist, not fixing'];
    }
    
    // Backup file
    $backup = backupFile($filePath);
    if (!$backup) {
        return ['status' => 'warning', 'message' => 'Could not create backup'];
    }
    
    // Read existing file to keep encryption keys
    $existing = file_get_contents($filePath);
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
    
    // Create fixed content with proper variable initialization
    $content = "<?php\n/**\n * Secure Database Credentials\n * This file should be placed outside the web root in production\n */\n\n// Initialize variables to avoid undefined errors\n\$testConnection = null;\n\$result = null;\n\n// Check if constants are already defined\nif (!defined('DB_HOST')) {\n    define('DB_HOST', 'localhost');\n}\n\n// Check which database exists and use the correct one\ntry {\n    \$testConnection = mysqli_connect('localhost', 'root', '');\n    if (\$testConnection) {\n        \$result = mysqli_query(\$testConnection, \"SHOW DATABASES LIKE 'certificate_system'\");\n        if (\$result && mysqli_num_rows(\$result) > 0 && !defined('DB_NAME')) {\n            define('DB_NAME', 'certificate_system');\n        }\n        mysqli_close(\$testConnection);\n    }\n} catch (Exception \$e) {\n    // Silently handle any errors\n}\n\n// Fallback to certificate_db if neither is defined\nif (!defined('DB_NAME')) {\n    define('DB_NAME', 'certificate_db');\n}\n\nif (!defined('DB_USER')) {\n    define('DB_USER', 'root');\n}\nif (!defined('DB_PASS')) {\n    define('DB_PASS', '');\n}\n\n// Additional security settings\ndefine('HASH_SECRET', '{$hashSecret}'); // Secret for hashing\ndefine('ENCRYPTION_KEY', '{$encryptionKey}'); // For encrypting sensitive data\n?>";
    
    // Save the file
    if (file_put_contents($filePath, $content)) {
        return ['status' => 'success', 'message' => 'Fixed secure database configuration file'];
    } else {
        return ['status' => 'error', 'message' => 'Could not write to secure config file'];
    }
}

// Apply all fixes
$masterInitResult = fixMasterInitFile($adminDir . '/master_init.php');
$sessionResult = createSessionFile($rootDir . '/includes/session.php');
$configResult = fixConfigFile($rootDir . '/config/config.php');
$dbResult = createDatabase();
$secureConfigResult = fixSecureDbConfig($rootDir . '/secure_config/db_config.php');

// Display results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Fix - Variable Correction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .step-number { background-color: #0d6efd; color: white; display: inline-block; width: 30px; height: 30px; text-align: center; border-radius: 50%; margin-right: 10px; font-weight: bold; line-height: 30px; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center mb-5">System Fix - Variable Correction <i class="bi bi-check-circle-fill text-success"></i></h1>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0"><i class="bi bi-tools me-2"></i>Applied System Fixes</h3>
        </div>
        <div class="card-body">
            <h4><span class="step-number">1</span>Core Files</h4>
            <div class="row mb-4">
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
            </div>
            
            <h4><span class="step-number">2</span>Configuration Files</h4>
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
            
            <h4><span class="step-number">3</span>Database</h4>
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
                <h5 class="d-flex align-items-center"><i class="bi bi-info-circle-fill me-2"></i>Issues Fixed:</h5>
                <ol>
                    <li><strong>Undefined Variables:</strong> All variables properly initialized before use to eliminate PHP warnings</li>
                    <li><strong>Session Handling:</strong> Improved session settings with proper initialization sequence</li>
                    <li><strong>Database Configuration:</strong> Fixed error handling for database connections and improved detection</li>
                    <li><strong>Syntax Error:</strong> Corrected syntax in master_init.php for database connections</li>
                </ol>
            </div>
            
            <div class="alert alert-warning">
                <h5 class="d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>Important Note:</h5>
                <p>After this fix, you should clear your browser cache (Ctrl+F5) before testing the system. Additionally, you may need to restart your PHP server to ensure all changes take effect.</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>Test Your System</h3>
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
                <a href="../verify/" class="btn btn-info text-white">
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
