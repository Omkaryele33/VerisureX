<?php
/**
 * Database Fix Script
 * This script fixes the database structure issues
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Increase timeouts and memory limits
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');
ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Fix</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        .success { color: green; }
        .warning { color: orange; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Certificate Validation System - Database Fix</h1>
        <div class='alert alert-info'>
            This script will fix database structure issues.
        </div>";

try {
    echo "<h2>Database Connection Test</h2>";
    
    // Try to connect with PDO directly without using the Database class
    $host = 'localhost';
    $dbname = 'certificate_validation';
    $username = 'root';
    $password = '';
    
    echo "<p>Attempting to connect to MySQL...</p>";
    
    // Create PDO connection with extended timeout
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 300,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4;'
    ];
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, $options);
    echo "<p class='success'>✓ Direct PDO connection successful</p>";
    
    // Now include the database class and try that connection
    echo "<h2>Testing Database Class Connection</h2>";
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<p class='success'>✓ Database class connection successful</p>";
    
    // Now fix the admins table
    echo "<h2>Fixing Admins Table</h2>";
    
    // Check if admins table exists
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
        
        // Check for missing columns one by one and add them
        if (!in_array('failed_login_attempts', $columns)) {
            echo "<p>Adding failed_login_attempts column...</p>";
            $db->exec("ALTER TABLE admins ADD COLUMN failed_login_attempts INT(11) NOT NULL DEFAULT 0");
            echo "<p class='success'>✓ Added failed_login_attempts column</p>";
        }
        
        if (!in_array('last_failed_login', $columns)) {
            echo "<p>Adding last_failed_login column...</p>";
            $db->exec("ALTER TABLE admins ADD COLUMN last_failed_login INT(11) DEFAULT NULL");
            echo "<p class='success'>✓ Added last_failed_login column</p>";
        }
        
        if (!in_array('account_locked', $columns)) {
            echo "<p>Adding account_locked column...</p>";
            $db->exec("ALTER TABLE admins ADD COLUMN account_locked TINYINT(1) NOT NULL DEFAULT 0");
            echo "<p class='success'>✓ Added account_locked column</p>";
        }
        
        if (!in_array('password_change_required', $columns)) {
            echo "<p>Adding password_change_required column...</p>";
            $db->exec("ALTER TABLE admins ADD COLUMN password_change_required TINYINT(1) NOT NULL DEFAULT 0");
            echo "<p class='success'>✓ Added password_change_required column</p>";
        }
        
        if (!in_array('last_password_change', $columns)) {
            echo "<p>Adding last_password_change column...</p>";
            $db->exec("ALTER TABLE admins ADD COLUMN last_password_change DATETIME DEFAULT NULL");
            echo "<p class='success'>✓ Added last_password_change column</p>";
        }
    }
    
    // Check rate_limits table
    echo "<h2>Checking Rate Limits Table</h2>";
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
    
    echo "<div class='alert alert-success mt-4'>
        <h3>Database Fix Complete!</h3>
        <p>All database structure issues have been fixed. Your admin panel should now work correctly.</p>
        <p>You can login with the following credentials:</p>
        <ul>
            <li><strong>Username:</strong> admin</li>
            <li><strong>Password:</strong> 123</li>
        </ul>
        <p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>
    </div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h3>Database Error</h3>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), "server has gone away") !== false) {
        echo "<h4>MySQL Server Connection Issues</h4>";
        echo "<p>This error typically occurs when:</p>";
        echo "<ul>";
        echo "<li>The MySQL server timed out (default timeout is usually 60 seconds)</li>";
        echo "<li>MySQL server memory limits are too low</li>";
        echo "<li>Connection packet is too large</li>";
        echo "</ul>";
        
        echo "<h4>Recommended Fixes:</h4>";
        echo "<ol>";
        echo "<li>Check your MySQL server configuration (my.ini or my.cnf)</li>";
        echo "<li>Increase wait_timeout, max_allowed_packet, and max_connections</li>";
        echo "<li>Restart your XAMPP/MySQL service</li>";
        echo "<li>Make sure your MySQL server has enough memory allocated</li>";
        echo "</ol>";
    }
    echo "</div>";
}

echo "</div></body></html>";
?>
