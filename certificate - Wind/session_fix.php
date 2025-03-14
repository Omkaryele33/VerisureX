<?php
/**
 * Session Fix
 * This script will fix all session-related issues in the application
 */

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to find all PHP files recursively
function findPhpFiles($dir) {
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $result = array_merge($result, findPhpFiles($path));
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $result[] = $path;
        }
    }
    
    return $result;
}

// Find all session_start() calls that don't check session status
function findSessionStartCalls($baseDir) {
    $phpFiles = findPhpFiles($baseDir);
    $sessionStartFiles = [];
    
    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        
        // Skip our session.php file
        if (strpos($file, 'includes/session.php') !== false) {
            continue;
        }
        
        // Look for direct session_start calls
        if (preg_match('/session_start\(\)/', $content) && 
            !preg_match('/if\s*\(\s*session_status\(\)\s*===\s*PHP_SESSION_NONE\s*\)/', $content)) {
            $sessionStartFiles[] = $file;
        }
    }
    
    return $sessionStartFiles;
}

// Create a proper session_start function that checks status first
function createSessionHelper($baseDir) {
    $helperDir = $baseDir . '/includes';
    if (!file_exists($helperDir)) {
        mkdir($helperDir, 0755, true);
    }
    
    $sessionFile = $helperDir . '/session.php';
    $backupFile = $sessionFile . '.bak.' . time();
    
    // Backup existing file if it exists
    if (file_exists($sessionFile)) {
        copy($sessionFile, $backupFile);
    }
    
    // Create new session.php file
    $content = "<?php\n/**\n * Session Configuration\n * This must be included before any output or other session operations\n */\n\n// Only set session parameters if session hasn't started yet\nif (session_status() === PHP_SESSION_NONE) {\n    // Session settings\n    ini_set(\"session.cookie_httponly\", 1);\n    ini_set(\"session.cookie_secure\", 0);  // Set to 1 if using HTTPS\n    ini_set(\"session.cookie_samesite\", \"Strict\");\n    ini_set(\"session.gc_maxlifetime\", 3600);\n    ini_set(\"session.use_strict_mode\", 1);\n    \n    // Start the session\n    session_start();\n} else {\n    // Session already started elsewhere - no need to modify settings\n}\n";
    
    if (file_put_contents($sessionFile, $content)) {
        return ["status" => "success", "message" => "Created fixed session handler", "backup" => $backupFile];
    } else {
        return ["status" => "error", "message" => "Failed to create session handler"];
    }
}

// Fix all files that call session_start directly
function fixSessionStartCalls($files) {
    $results = [];
    
    foreach ($files as $file) {
        $backup = $file . '.bak.' . time();
        copy($file, $backup); // Create backup
        
        $content = file_get_contents($file);
        
        // Replace direct session_start() with an include of our session file
        $newContent = preg_replace(
            '/session_start\(\);/',
            'require_once __DIR__ . "/../includes/session.php";',
            $content
        );
        
        if ($newContent !== $content) {
            if (file_put_contents($file, $newContent)) {
                $results[] = ["file" => $file, "status" => "success", "backup" => $backup];
            } else {
                $results[] = ["file" => $file, "status" => "error", "message" => "Could not write to file"];
            }
        } else {
            $results[] = ["file" => $file, "status" => "warning", "message" => "No changes made"];
        }
    }
    
    return $results;
}

// Update master_init.php to use our session handler correctly
function fixMasterInit($baseDir) {
    $file = $baseDir . '/admin/master_init.php';
    if (!file_exists($file)) {
        return ["status" => "error", "message" => "File not found"];
    }
    
    $backup = $file . '.bak.' . time();
    copy($file, $backup); // Create backup
    
    $content = "<?php\n/**\n * Master Initialization File\n * This file includes all necessary files in the correct order\n */\n\n// Define the root directory path\ndefine(\"ROOT_PATH\", dirname(__DIR__));\n\n// Include session handler first - must be before any output\nrequire_once ROOT_PATH . \"/includes/session.php\";\n\n// Include other config files\nrequire_once ROOT_PATH . \"/config/constants.php\";\nrequire_once ROOT_PATH . \"/config/config.php\";\nrequire_once ROOT_PATH . \"/config/database.php\";\nrequire_once ROOT_PATH . \"/includes/functions.php\";\nrequire_once ROOT_PATH . \"/includes/security.php\";\n\n// Connect to database\ntry {\n    $database = new Database();\n    $db = $database->getConnection();\n} catch (Exception $e) {\n    // Handle database connection error gracefully\n    die(\"Database connection error: \" . $e->getMessage());\n}\n";
    
    if (file_put_contents($file, $content)) {
        return ["status" => "success", "message" => "Fixed master_init.php file", "backup" => $backup];
    } else {
        return ["status" => "error", "message" => "Could not write to master_init.php"];
    }
}

// Execute all fixes
$baseDir = dirname(__FILE__);
$sessionFiles = findSessionStartCalls($baseDir);
$sessionHelperResult = createSessionHelper($baseDir);
$fixSessionResult = fixSessionStartCalls($sessionFiles);
$masterInitResult = fixMasterInit($baseDir);

// Output results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Fix - Certificate System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 30px; background-color: #f8f9fa; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-header { background-color: #f1f8ff; padding::20px; border-radius: 12px 12px 0 0; }
        .card-body { padding: 25px; }
        .fix-result { padding: 12px 15px; border-radius: 8px; margin-bottom:.15px; }
        .fix-success { background-color: #d1e7dd; }
        .fix-warning { background-color: #fff3cd; }
        .fix-error { background-color: #f8d7da; }
        pre { background-color: #f7f7f9; padding: 15px; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center mb-5">Session Initialization Fix</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="mb-0">Session Handler</h3>
        </div>
        <div class="card-body">
            <?php
            $class = $sessionHelperResult["status"] === "success" ? "fix-success" : "fix-error";
            echo "<div class='fix-result {$class}'>";
            echo $sessionHelperResult["message"];
            if (isset($sessionHelperResult["backup"])) {
                echo " <small>(Backup created: " . basename($sessionHelperResult["backup"]) . ")</small>";
            }
            echo "</div>";
            ?>
            
            <h5 class="mt-4">New Session Handler Code:</h5>
            <pre><code>// Only set session parameters if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set("session.cookie_httponly", 1);
    ini_set("session.cookie_secure", 0);
    ini_set("session.cookie_samesite", "Strict");
    ini_set("session.gc_maxlifetime", 3600);
    ini_set("session.use_strict_mode", 1);
    
    // Start the session
    session_start();
}</code></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="mb-0">Master Init Fix</h3>
        </div>
        <div class="card-body">
            <?php
            $class = $masterInitResult["status"] === "success" ? "fix-success" : "fix-error";
            echo "<div class='fix-result {$class}'>";
            echo $masterInitResult["message"];
            if (isset($masterInitResult["backup"])) {
                echo " <small>(Backup created: " . basename($masterInitResult["backup"]) . ")</small>";
            }
            echo "</div>";
            ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Files Fixed</h3>
        </div>
        <div class="card-body">
            <p>The following files were checked for direct session_start() calls:</p>
            
            <?php if (empty($sessionFiles)): ?>
                <div class="alert alert-info">No files with direct session_start() calls were found.</div>
            <?php else: ?>
                <ul class="list-group mb-4">
                    <?php foreach ($fixSessionResult as $result): ?>
                        <?php 
                        $class = $result["status"] === "success" ? "list-group-item-success" : 
                                ($result["status"] === "warning" ? "list-group-item-warning" : "list-group-item-danger");
                        $icon = $result["status"] === "success" ? "✅" : 
                               ($result["status"] === "warning" ? "⚠️" : "❌");
                        ?>
                        <li class="list-group-item <?php echo $class; ?>">
                            <?php echo $icon; ?> 
                            <?php echo basename($result["file"]); ?>
                            <?php if (isset($result["message"])): ?>
                                - <?php echo $result["message"]; ?>
                            <?php endif; ?>
                            <?php if (isset($result["backup"])): ?>
                                <small>(Backup created: <?php echo basename($result["backup"]); ?>)</small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div class="alert alert-success">
                <h5>What Was Fixed:</h5>
                <ol>
                    <li><strong>Session Initialization:</strong> Created proper session initialization that checks if a session is already active</li>
                    <li><strong>Direct session_start() Calls:</strong> Replaced direct calls with includes to our session handler</li>
                    <li><strong>Order of Operations:</strong> Ensured session settings are always set before starting a session</li>
                </ol>
                <p>These changes will eliminate the "session settings cannot be changed when session is active" warnings.</p>
            </div>
            
            <div class="mt-4">
                <a href="admin/login.php" class="btn btn-primary">Test Login Page</a>
                <a href="admin/dashboard.php" class="btn btn-success">Test Dashboard</a>
                <a href="index.php" class="btn btn-secondary">Home Page</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
