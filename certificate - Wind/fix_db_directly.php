<?php
/**
 * Direct Database Fix Script
 * This script will directly fix the database issue by creating the users view
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Direct Fix: Users Table</h2>";

try {
    // Connect directly to MySQL without a database first
    $conn = new mysqli('localhost', 'root', '');
    
    if ($conn->connect_error) {
        die("<p>❌ Connection failed: " . $conn->connect_error . "</p>");
    }
    
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Make sure the database exists
    $result = $conn->query("CREATE DATABASE IF NOT EXISTS certificate_system");
    if ($result) {
        echo "<p>✅ Ensured certificate_system database exists</p>";
    } else {
        echo "<p>❌ Failed to create database: " . $conn->error . "</p>";
    }
    
    // Select the database
    $conn->select_db("certificate_system");
    
    // Check if admins table exists
    $result = $conn->query("SHOW TABLES LIKE 'admins'");
    if ($result->num_rows == 0) {
        echo "<p>⚠️ 'admins' table doesn't exist! Creating it now...</p>";
        
        // Create admins table
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
            name VARCHAR(100) NULL,
            password_change_required TINYINT(1) DEFAULT 0,
            account_status VARCHAR(20) DEFAULT 'active',
            login_attempts INT DEFAULT 0,
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "<p>✅ Created 'admins' table</p>";
            
            // Insert default admin if table is empty
            $result = $conn->query("SELECT COUNT(*) as count FROM admins");
            $row = $result->fetch_assoc();
            
            if ($row['count'] == 0) {
                $username = 'admin';
                $password = password_hash('admin123', PASSWORD_DEFAULT);
                $email = 'admin@example.com';
                $name = 'System Administrator';
                $role = 'admin';
                
                $stmt = $conn->prepare("INSERT INTO admins (username, password, email, name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $password, $email, $name, $role);
                
                if ($stmt->execute()) {
                    echo "<p>✅ Created default admin user</p>";
                } else {
                    echo "<p>❌ Failed to create admin user: " . $stmt->error . "</p>"; 
                }
            }
        } else {
            echo "<p>❌ Failed to create 'admins' table: " . $conn->error . "</p>"; 
        }
    } else {
        echo "<p>✅ 'admins' table exists</p>";
        
        // Check if required columns exist in admins
        $result = $conn->query("DESCRIBE admins");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Add missing columns if needed
        if (!in_array('name', $columns)) {
            $conn->query("ALTER TABLE admins ADD COLUMN name VARCHAR(100) AFTER role");
            echo "<p>✅ Added missing 'name' column to admins</p>";
        }
        
        if (!in_array('password_change_required', $columns)) {
            $conn->query("ALTER TABLE admins ADD COLUMN password_change_required TINYINT(1) DEFAULT 0 AFTER role");
            echo "<p>✅ Added missing 'password_change_required' column to admins</p>";
        }
        
        if (!in_array('account_status', $columns)) {
            $conn->query("ALTER TABLE admins ADD COLUMN account_status VARCHAR(20) DEFAULT 'active' AFTER password_change_required");
            echo "<p>✅ Added missing 'account_status' column to admins</p>";
        }
        
        if (!in_array('login_attempts', $columns)) {
            $conn->query("ALTER TABLE admins ADD COLUMN login_attempts INT DEFAULT 0 AFTER account_status");
            echo "<p>✅ Added missing 'login_attempts' column to admins</p>";
        }
    }
    
    // Try to drop the users view if it exists
    $conn->query("DROP VIEW IF EXISTS users");
    echo "<p>✅ Dropped users view if it existed</p>";
    
    // Create the users view - now make sure this executes successfully
    $sql = "CREATE VIEW users AS 
            SELECT id, username, password, email, name, role, 
            password_change_required, account_status, login_attempts, 
            created_at, updated_at 
            FROM admins";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully created users view</p>";
    } else {
        echo "<p>❌ Failed to create users view: " . $conn->error . "</p>";
    }
    
    // Verify the view was created
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "<p>✅ Verified users view exists</p>";
        
        // Double-check users view has rows
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "<p>Users in database: " . $row['count'] . "</p>";
        
        if ($row['count'] == 0) {
            echo "<p>⚠️ Warning: No users in database!</p>";
        } else {
            // Show first user
            $result = $conn->query("SELECT username, role FROM users LIMIT 1");
            $user = $result->fetch_assoc();
            echo "<p>First user: Username = {$user['username']}, Role = {$user['role']}</p>";
        }
    } else {
        echo "<p>❌ Users view still doesn't exist after creation attempt!</p>";
    }
    
    // Display all tables to debug
    echo "<h3>All Tables and Views in Database:</h3>";
    echo "<ul>";
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        echo "<li>{$row[0]}</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='/certificate/admin/login.php'>Try logging in now</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
    h2 { color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    h3 { color: #3498db; }
    p { margin: 10px 0; }
    a { color: #3498db; text-decoration: none; padding: 8px 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; display: inline-block; margin-top: 15px; }
    a:hover { background: #e9ecef; }
</style>
