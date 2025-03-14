<?php
/**
 * Debug script for admin login
 * This will help identify errors in the login process
 */

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Debugging</h1>";

// Start session
echo "<h2>Session Check</h2>";
if (session_status() === PHP_SESSION_NONE) {
    echo "Starting session...<br>";
    session_start();

// Include security constants
require_once 'security_header.php';
    echo "Session started. Session ID: " . session_id() . "<br>";
} else {
    echo "Session already active. Session ID: " . session_id() . "<br>";
}

// Check if required files exist
echo "<h2>Required Files Check</h2>";
$requiredFiles = [
    '../config/config.php',
    '../config/database.php',
    '../includes/functions.php',
    '../includes/security.php',
    '../secure_config/db_config.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = realpath(dirname(__FILE__) . '/' . $file);
    if (file_exists($fullPath)) {
        echo "✅ File exists: $file<br>";
    } else {
        echo "❌ File missing: $file<br>";
    }
}

// Check database connection
echo "<h2>Database Connection Check</h2>";
try {
    // First include DB config file
    require_once dirname(__FILE__) . '/../secure_config/db_config.php';
    
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'Not defined') . "<br>";
    echo "DB_PASS: " . (defined('DB_PASS') ? '[HIDDEN]' : 'Not defined') . "<br>";
    
    // Try connecting
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Successfully connected to database<br>";
    
    // Check if admins table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'admins'")->fetchAll();
    if (count($tables) > 0) {
        echo "✅ Admins table exists<br>";
        
        // Check for admin user
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute(['admin']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ Admin user exists<br>";
            echo "User ID: " . $user['id'] . "<br>";
            echo "Username: " . $user['username'] . "<br>";
            echo "Password hash: " . substr($user['password'], 0, 10) . "...<br>";
            
            // Check if password '123' would match
            if (password_verify('123', $user['password'])) {
                echo "✅ Password '123' matches the hash<br>";
            } else {
                echo "❌ Password '123' does NOT match the hash<br>";
                echo "Creating a hash for '123': " . password_hash('123', PASSWORD_DEFAULT) . "<br>";
            }
        } else {
            echo "❌ Admin user not found<br>";
            
            // Create admin user
            echo "<h3>Creating Admin User</h3>";
            $passwordHash = password_hash('123', PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute(['admin', $passwordHash, 'admin@example.com', 'admin']);
                
                if ($result) {
                    echo "✅ Admin user created successfully<br>";
                } else {
                    echo "❌ Failed to create admin user<br>";
                }
            } catch (PDOException $e) {
                echo "❌ Error creating admin user: " . $e->getMessage() . "<br>";
            }
        }
        
        // Check table structure
        echo "<h3>Admins Table Structure</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . ($value === NULL ? 'NULL' : $value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ Admins table does not exist<br>";
        
        // Create admin table
        echo "<h3>Creating Admins Table</h3>";
        $query = "CREATE TABLE admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role ENUM('admin', 'manager', 'operator') NOT NULL DEFAULT 'operator',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            failed_login_attempts INT NOT NULL DEFAULT 0,
            last_failed_login INT NULL,
            account_locked TINYINT(1) NOT NULL DEFAULT 0,
            last_login DATETIME NULL,
            password_change_required TINYINT(1) NOT NULL DEFAULT 0,
            last_password_change DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        try {
            $pdo->exec($query);
            echo "✅ Admins table created successfully<br>";
            
            // Create admin user
            $passwordHash = password_hash('123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute(['admin', $passwordHash, 'admin@example.com', 'admin']);
            
            if ($result) {
                echo "✅ Admin user created successfully<br>";
            } else {
                echo "❌ Failed to create admin user<br>";
            }
        } catch (PDOException $e) {
            echo "❌ Error creating admins table: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check security.php functions
echo "<h2>Security Functions Check</h2>";
try {
    require_once '../includes/security.php';
    
    echo "Checking if security functions exist:<br>";
    $securityFunctions = [
        'generateCSRFToken',
        'validateCSRFToken',
        'isAccountLocked',
        'trackFailedLogin',
        'resetFailedLoginAttempts'
    ];
    
    foreach ($securityFunctions as $function) {
        if (function_exists($function)) {
            echo "✅ Function exists: $function<br>";
        } else {
            echo "❌ Function missing: $function<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error loading security functions: " . $e->getMessage() . "<br>";
}

// Check for other common issues
echo "<h2>Other Checks</h2>";

// Check PHP version
echo "PHP Version: " . phpversion() . "<br>";

// Check if PHP has required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'session'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension loaded: $ext<br>";
    } else {
        echo "❌ Extension missing: $ext<br>";
    }
}

// Output HTML form to try login directly
echo "<h2>Try Direct Login</h2>";
echo "<form method='post' action='login.php'>";
echo "Username: <input type='text' name='username' value='admin'><br>";
echo "Password: <input type='password' name='password' value='123'><br>";
echo "CSRF Token: <input type='hidden' name='csrf_token' value='" . (function_exists('generateCSRFToken') ? generateCSRFToken() : 'missing-function') . "'><br>";
echo "<input type='submit' value='Login'>";
echo "</form>";

// Return to login page link
echo "<a href='login.php'>Return to normal login page</a>";
