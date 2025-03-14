<?php
/**
 * Login Fix Script
 * This script will fix the login issues by implementing the necessary security constants
 * and updating file references
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Fix Tool</h1>";

// Step 1: Check if security_patched.php exists
$patchedFile = __DIR__ . '/includes/security_patched.php';
if (!file_exists($patchedFile)) {
    echo "<div style='color:red'>Error: security_patched.php not found. Please try again.</div>";
    exit;
}

// Step 2: Backup the original security.php
$securityFile = __DIR__ . '/includes/security.php';
$backupFile = $securityFile . '.bak.' . date('YmdHis');

if (!file_exists($securityFile)) {
    echo "<div style='color:red'>Error: Original security.php not found.</div>";
    exit;
}

if (!copy($securityFile, $backupFile)) {
    echo "<div style='color:red'>Error: Could not create backup of security.php.</div>";
    exit;
}

echo "<div style='color:green'>Created backup of security.php at {$backupFile}</div>";

// Step 3: Replace security.php with patched version
if (copy($patchedFile, $securityFile)) {
    echo "<div style='color:green'>Successfully replaced security.php with fixed version</div>";
} else {
    echo "<div style='color:red'>Error: Could not replace security.php. Please check file permissions.</div>";
    exit;
}

// Step 4: Create a test admin user
echo "<h2>Checking Database Connection</h2>";
try {
    require_once __DIR__ . '/secure_config/db_config.php';
    
    echo "Connecting to database with:<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "User: " . DB_USER . "<br>";
    
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='color:green'>Successfully connected to database</div>";
    
    // Check if admins table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'admins'");
    if ($tableCheck->rowCount() === 0) {
        echo "<div style='color:orange'>Admins table does not exist. Creating it now...</div>";
        
        // Create admins table
        $createTable = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
            failed_login_attempts INT NOT NULL DEFAULT 0,
            last_failed_login INT NULL,
            account_locked TINYINT(1) NOT NULL DEFAULT 0,
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($createTable);
        echo "<div style='color:green'>Created admins table</div>";
    }
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    
    if ($stmt->rowCount() === 0) {
        echo "<div style='color:orange'>Admin user does not exist. Creating admin user with password '123'...</div>";
        
        // Create admin user
        $passwordHash = password_hash('123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $passwordHash, 'admin@example.com', 'admin']);
        
        echo "<div style='color:green'>Created admin user with username 'admin' and password '123'</div>";
    } else {
        echo "<div style='color:blue'>Admin user already exists. Updating password to '123'...</div>";
        
        // Update admin password
        $passwordHash = password_hash('123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $stmt->execute([$passwordHash, 'admin']);
        
        echo "<div style='color:green'>Updated admin password to '123'</div>";
    }
    
    // Create rate_limits table if it doesn't exist
    $tableCheck = $db->query("SHOW TABLES LIKE 'rate_limits'");
    if ($tableCheck->rowCount() === 0) {
        echo "<div style='color:orange'>Rate limits table does not exist. Creating it now...</div>";
        
        $createTable = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            timestamp INT NOT NULL,
            ip VARCHAR(45) NOT NULL,
            KEY idx_identifier_action (identifier, action),
            KEY idx_timestamp (timestamp)
        )";
        
        $db->exec($createTable);
        echo "<div style='color:green'>Created rate_limits table</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color:red'>Database error: " . $e->getMessage() . "</div>";
}

echo "<h2>Fix Complete</h2>";
echo "<p>You should now be able to login with:</p>";
echo "<ul>";
echo "<li><strong>Username:</strong> admin</li>";
echo "<li><strong>Password:</strong> 123</li>";
echo "</ul>";

echo "<p><a href='admin/login.php' style='display:inline-block; padding:10px 15px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:4px;'>Go to Admin Login</a></p>";
?>
