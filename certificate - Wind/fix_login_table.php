<?php
/**
 * Login Table Fix Script
 * This script addresses the issue where login.php is looking for a 'users' table but we have 'admins'
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Table Fix Tool</h1>";

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
    
    // Check if users table exists
    $usersTableExists = false;
    $adminsTableExists = false;
    
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'users'");
        $usersTableExists = ($result->rowCount() > 0);
        
        $result = $pdo->query("SHOW TABLES LIKE 'admins'");
        $adminsTableExists = ($result->rowCount() > 0);
        
        echo "<p>Users table exists: " . ($usersTableExists ? "Yes" : "No") . "</p>";
        echo "<p>Admins table exists: " . ($adminsTableExists ? "Yes" : "No") . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking tables: " . $e->getMessage() . "</p>";
    }
    
    // Fix approach based on table existence
    if ($usersTableExists) {
        echo "<h2>Users Table Exists - Updating Admin Credentials</h2>";
        
        // Check if admin user exists in users table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminExists = ($stmt->fetchColumn() > 0);
        
        if ($adminExists) {
            // Update admin user
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password, account_status = 'active', login_attempts = 0 WHERE username = 'admin'");
            $stmt->bindParam(':password', $passwordHash);
            
            if ($stmt->execute()) {
                echo "<p style='color:green'>✅ Updated admin user in users table.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update admin user.</p>";
            }
        } else {
            // Create admin user
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, account_status) VALUES ('admin', 'admin@example.com', :password, 'admin', 'active')");
            $stmt->bindParam(':password', $passwordHash);
            
            if ($stmt->execute()) {
                echo "<p style='color:green'>✅ Created admin user in users table.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to create admin user.</p>";
            }
        }
    } else if ($adminsTableExists) {
        echo "<h2>Only Admins Table Exists - Creating Users Table</h2>";
        
        // Create users table with same structure as admins
        try {
            // Get admins table structure
            $result = $pdo->query("SHOW CREATE TABLE admins");
            $tableDefinition = $result->fetch(PDO::FETCH_ASSOC);
            
            if (isset($tableDefinition['Create Table'])) {
                // Create users table with same structure but different name
                $createUsersSQL = str_replace('CREATE TABLE `admins`', 'CREATE TABLE `users`', $tableDefinition['Create Table']);
                $pdo->exec($createUsersSQL);
                echo "<p style='color:green'>✅ Created users table with same structure as admins.</p>";
                
                // Copy data from admins to users
                $pdo->exec("INSERT INTO users SELECT * FROM admins");
                echo "<p style='color:green'>✅ Copied admin data to users table.</p>";
            } else {
                echo "<p style='color:red'>❌ Could not get admins table structure.</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error creating users table: " . $e->getMessage() . "</p>";
            
            // Alternative approach: Create a basic users table
            try {
                $pdo->exec("CREATE TABLE users (
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
                )");
                
                echo "<p style='color:green'>✅ Created basic users table.</p>";
                
                // Insert admin user
                $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, account_status) VALUES ('admin', 'admin@example.com', :password, 'admin', 'active')");
                $stmt->bindParam(':password', $passwordHash);
                
                if ($stmt->execute()) {
                    echo "<p style='color:green'>✅ Created admin user in users table.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to create admin user.</p>";
                }
            } catch (PDOException $e2) {
                echo "<p style='color:red'>❌ Error creating basic users table: " . $e2->getMessage() . "</p>";
            }
        }
    } else {
        echo "<h2>No User Tables Found - Creating Both</h2>";
        
        // Create both tables
        try {
            // Create admins table
            $pdo->exec("CREATE TABLE admins (
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
            )");
            
            echo "<p style='color:green'>✅ Created admins table.</p>";
            
            // Create users table with same structure
            $pdo->exec("CREATE TABLE users LIKE admins");
            echo "<p style='color:green'>✅ Created users table.</p>";
            
            // Insert admin user into both tables
            $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role, account_status) VALUES ('admin', 'admin@example.com', :password, 'admin', 'active')");
            $stmt->bindParam(':password', $passwordHash);
            $stmt->execute();
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, account_status) VALUES ('admin', 'admin@example.com', :password, 'admin', 'active')");
            $stmt->bindParam(':password', $passwordHash);
            $stmt->execute();
            
            echo "<p style='color:green'>✅ Created admin user in both tables.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error creating tables: " . $e->getMessage() . "</p>";
        }
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