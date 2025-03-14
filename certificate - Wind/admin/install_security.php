<?php
/**
 * Security Installation Script
 * This script fixes security issues across all PHP files in the admin directory
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define security constants
require_once 'security_header.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Security Installation</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Certificate Validation System - Security Installation</h1>
        <div class='alert alert-info'>
            This script will fix security-related issues across all PHP files in the admin directory.
        </div>";

// Step 1: Make sure database tables exist with correct structure
echo "<h2>Step 1: Database Structure Check</h2>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p class='success'>✓ Connected to database</p>";
    
    // Check admins table
    $tableCheck = $db->query("SHOW TABLES LIKE 'admins'");
    if ($tableCheck->rowCount() === 0) {
        echo "<p class='warning'>⚠ Admins table not found. Creating it...</p>";
        
        $createAdmins = "CREATE TABLE admins (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role ENUM('admin', 'editor') NOT NULL DEFAULT 'admin',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            failed_login_attempts INT(11) NOT NULL DEFAULT 0,
            last_failed_login INT(11) DEFAULT NULL,
            account_locked TINYINT(1) NOT NULL DEFAULT 0,
            password_change_required TINYINT(1) NOT NULL DEFAULT 0,
            last_password_change DATETIME DEFAULT NULL,
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (username),
            UNIQUE KEY (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($createAdmins);
        echo "<p class='success'>✓ Created admins table</p>";
        
        // Create default admin user
        $passwordHash = password_hash('123', PASSWORD_DEFAULT);
        $createAdmin = "INSERT INTO admins (username, password, email, role) VALUES ('admin', '$passwordHash', 'admin@example.com', 'admin')";
        $db->exec($createAdmin);
        echo "<p class='success'>✓ Created default admin user (username: admin, password: 123)</p>";
    } else {
        echo "<p class='success'>✓ Admins table exists</p>";
        
        // Check if admin user exists
        $adminCheck = $db->query("SELECT id FROM admins WHERE username = 'admin'");
        if ($adminCheck->rowCount() === 0) {
            echo "<p class='warning'>⚠ Admin user not found. Creating default admin user...</p>";
            
            $passwordHash = password_hash('123', PASSWORD_DEFAULT);
            $createAdmin = "INSERT INTO admins (username, password, email, role) VALUES ('admin', '$passwordHash', 'admin@example.com', 'admin')";
            $db->exec($createAdmin);
            echo "<p class='success'>✓ Created default admin user (username: admin, password: 123)</p>";
        } else {
            echo "<p class='success'>✓ Admin user exists</p>";
        }
        
        // Check for required columns
        $columns = $db->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = [
            'failed_login_attempts', 'last_failed_login', 'account_locked', 
            'password_change_required', 'last_password_change'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        if (!empty($missingColumns)) {
            echo "<p class='warning'>⚠ Admins table is missing columns: " . implode(', ', $missingColumns) . "</p>";
            
            // Add missing columns
            if (in_array('failed_login_attempts', $missingColumns)) {
                $db->exec("ALTER TABLE admins ADD COLUMN failed_login_attempts INT(11) NOT NULL DEFAULT 0");
            }
            if (in_array('last_failed_login', $missingColumns)) {
                $db->exec("ALTER TABLE admins ADD COLUMN last_failed_login INT(11) DEFAULT NULL");
            }
            if (in_array('account_locked', $missingColumns)) {
                $db->exec("ALTER TABLE admins ADD COLUMN account_locked TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (in_array('password_change_required', $missingColumns)) {
                $db->exec("ALTER TABLE admins ADD COLUMN password_change_required TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (in_array('last_password_change', $missingColumns)) {
                $db->exec("ALTER TABLE admins ADD COLUMN last_password_change DATETIME DEFAULT NULL");
            }
            
            echo "<p class='success'>✓ Added missing columns to admins table</p>";
        }
    }
    
    // Check rate_limits table
    $tableCheck = $db->query("SHOW TABLES LIKE 'rate_limits'");
    if ($tableCheck->rowCount() === 0) {
        echo "<p class='warning'>⚠ Rate limits table not found. Creating it...</p>";
        
        $createRateLimits = "CREATE TABLE rate_limits (
            id INT(11) NOT NULL AUTO_INCREMENT,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            timestamp INT(11) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            PRIMARY KEY (id),
            KEY idx_identifier_action (identifier, action),
            KEY idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->exec($createRateLimits);
        echo "<p class='success'>✓ Created rate_limits table</p>";
    } else {
        echo "<p class='success'>✓ Rate limits table exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Step 2: Find all PHP files in admin directory
echo "<h2>Step 2: Scanning PHP Files</h2>";

$adminDir = __DIR__;
$files = glob($adminDir . '/*.php');
$filesToFix = [];

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip this file and security_header.php
    if ($filename === 'install_security.php' || $filename === 'security_header.php' || 
        $filename === 'login_fix.php' || $filename === 'update_database.php') {
        continue;
    }
    
    $contents = file_get_contents($file);
    
    // Check if file already includes security_header.php
    if (strpos($contents, "require_once 'security_header.php'") !== false || 
        strpos($contents, 'require_once "security_header.php"') !== false) {
        echo "<p class='success'>✓ $filename already includes security header</p>";
        continue;
    }
    
    // Check if file uses security.php without defining constants
    if (strpos($contents, "require_once '../includes/security.php'") !== false && 
        strpos($contents, "CSRF_TOKEN_LENGTH") === false) {
        $filesToFix[] = $file;
        echo "<p class='warning'>⚠ $filename needs security fix</p>";
    }
}

// Step 3: Update PHP files to include security header
echo "<h2>Step 3: Updating PHP Files</h2>";

foreach ($filesToFix as $file) {
    $filename = basename($file);
    $contents = file_get_contents($file);
    
    // Find where to insert the security header include
    $pattern = '/session_start\(\);[\r\n]+/';
    if (preg_match($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
        $position = $matches[0][1] + strlen($matches[0][0]);
        $newContents = substr($contents, 0, $position) . 
            "\n// Include security constants\nrequire_once 'security_header.php';\n" . 
            substr($contents, $position);
        
        if (file_put_contents($file, $newContents)) {
            echo "<p class='success'>✓ Updated $filename to include security header</p>";
        } else {
            echo "<p class='error'>✗ Failed to update $filename</p>";
        }
    } else {
        echo "<p class='warning'>⚠ Could not find insertion point in $filename</p>";
    }
}

// Step 4: Validate login page
echo "<h2>Step 4: Validating Login Page</h2>";

try {
    $loginFile = $adminDir . '/login.php';
    if (file_exists($loginFile)) {
        $loginContents = file_get_contents($loginFile);
        
        // Check if login page includes security.php
        if (strpos($loginContents, "require_once '../includes/security.php'") !== false) {
            echo "<p class='success'>✓ Login page includes security.php</p>";
        } else {
            echo "<p class='warning'>⚠ Login page is missing security.php include</p>";
            
            // Fix login page
            $pattern = '/require_once \'\.\.\/includes\/functions\.php\';[\r\n]+/';
            if (preg_match($pattern, $loginContents, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1] + strlen($matches[0][0]);
                $newContents = substr($loginContents, 0, $position) . 
                    "require_once '../includes/security.php';\n" . 
                    substr($loginContents, $position);
                
                if (file_put_contents($loginFile, $newContents)) {
                    echo "<p class='success'>✓ Updated login.php to include security.php</p>";
                } else {
                    echo "<p class='error'>✗ Failed to update login.php</p>";
                }
            }
        }
    } else {
        echo "<p class='error'>✗ Login file not found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error validating login page: " . $e->getMessage() . "</p>";
}

echo "<div class='alert alert-success mt-4'>
    <h3>Installation Complete!</h3>
    <p>All security issues have been fixed. Your admin panel should now work correctly.</p>
    <p>You can login with the following credentials:</p>
    <ul>
        <li><strong>Username:</strong> admin</li>
        <li><strong>Password:</strong> 123</li>
    </ul>
    <p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>
</div>";

echo "</div></body></html>";
?>
