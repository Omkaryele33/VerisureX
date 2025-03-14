<?php
/**
 * Security Fix Script
 * This script addresses common security issues including session problems and CSRF tokens
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Security Fix Tool</h1>";

// Include database configuration
require_once 'secure_config/db_config.php';

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p style='color:green'>Connected to database successfully.</p>";
    
    // Fix 1: Reset admin user account status and login attempts
    echo "<h2>Resetting Admin Account</h2>";
    
    try {
        $stmt = $pdo->prepare("UPDATE admins SET account_status = 'active', login_attempts = 0 WHERE username = 'admin'");
        if ($stmt->execute()) {
            echo "<p style='color:green'>✅ Reset admin account status to active and cleared login attempts.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to reset admin account.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error resetting admin account: " . $e->getMessage() . "</p>";
    }
    
    // Fix 2: Reset admin password
    echo "<h2>Resetting Admin Password</h2>";
    
    try {
        // Generate a secure password hash for 'admin123'
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = 'admin'");
        $stmt->bindParam(':password', $passwordHash);
        
        if ($stmt->execute()) {
            echo "<p style='color:green'>✅ Successfully reset admin password to 'admin123'.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to reset admin password.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error resetting admin password: " . $e->getMessage() . "</p>";
    }
    
    // Fix 3: Check admin table structure
    echo "<h2>Checking Admin Table Structure</h2>";
    
    try {
        $result = $pdo->query("DESCRIBE admins");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'id', 'username', 'email', 'password', 'role', 'account_status', 'login_attempts'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "<p style='color:green'>✅ Admin table structure is complete.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Admin table is missing columns: " . implode(', ', $missingColumns) . "</p>";
            echo "<p>Please run the full database installation script to fix table structure.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error checking admin table structure: " . $e->getMessage() . "</p>";
    }
    
    // Fix 4: Clean up session files
    echo "<h2>Cleaning Session Files</h2>";
    
    session_start();
    session_destroy();
    
    // Get session save path
    $sessionPath = session_save_path();
    if (empty($sessionPath)) {
        $sessionPath = sys_get_temp_dir();
    }
    
    echo "<p>Session save path: " . $sessionPath . "</p>";
    echo "<p style='color:green'>✅ Current session destroyed. Your browser sessions will be refreshed on next login.</p>";
    
    // Fix 5: Check and update security settings
    echo "<h2>Updating Security Settings</h2>";
    
    try {
        // Check if the security_settings table exists
        $result = $pdo->query("SHOW TABLES LIKE 'security_settings'");
        
        if ($result->rowCount() > 0) {
            // Update security settings
            $stmt = $pdo->prepare("UPDATE security_settings SET value = '0' WHERE setting = 'enforce_csrf'");
            $stmt->execute();
            
            $stmt = $pdo->prepare("UPDATE security_settings SET value = '5' WHERE setting = 'max_login_attempts'");
            $stmt->execute();
            
            echo "<p style='color:green'>✅ Security settings updated to be more lenient during testing.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Security settings table not found. This is OK for basic installations.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error updating security settings: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
}

// Provide links to other useful pages
echo "<h2>Next Steps</h2>";
echo "<p>After running this fix:</p>";
echo "<ol>";
echo "<li>Clear your browser cookies and cache</li>";
echo "<li>Try logging in again with username 'admin' and password 'admin123'</li>";
echo "</ol>";
echo "<p><a href='admin/login.php' style='font-weight:bold; margin-right:15px'>Go to Admin Login</a>";
echo "<a href='index.php'>Home Page</a></p>";
?> 