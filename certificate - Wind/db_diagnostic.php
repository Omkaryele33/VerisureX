<?php
/**
 * Database Diagnostic Script
 * This script will check for common database issues and attempt to fix them
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Diagnostic Tool</h1>";

// Step 1: Check if MySQL service is running
echo "<h2>Step 1: Checking MySQL Service</h2>";
$mysqlRunning = false;
try {
    $conn = @mysqli_connect('localhost', 'root', '');
    if ($conn) {
        echo "<p style='color:green'>✅ MySQL is running and accepting connections.</p>";
        $mysqlRunning = true;
        mysqli_close($conn);
    } else {
        echo "<p style='color:red'>❌ Cannot connect to MySQL: " . mysqli_connect_error() . "</p>";
        echo "<p>Please ensure that the MySQL service is running in XAMPP Control Panel.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error checking MySQL: " . $e->getMessage() . "</p>";
}

// Only proceed if MySQL is running
if ($mysqlRunning) {
    // Step 2: Check for database existence
    echo "<h2>Step 2: Checking Databases</h2>";
    try {
        $conn = mysqli_connect('localhost', 'root', '');
        
        // Check for certificate_system database
        $result = mysqli_query($conn, "SHOW DATABASES LIKE 'certificate_system'");
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<p style='color:green'>✅ Database 'certificate_system' exists.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Database 'certificate_system' does not exist. Attempting to create it...</p>";
            
            if (mysqli_query($conn, "CREATE DATABASE certificate_system")) {
                echo "<p style='color:green'>✅ Successfully created database 'certificate_system'.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to create database: " . mysqli_error($conn) . "</p>";
            }
        }
        
        // Check for certificate_db (fallback) database
        $result = mysqli_query($conn, "SHOW DATABASES LIKE 'certificate_db'");
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<p style='color:green'>✅ Database 'certificate_db' exists.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Database 'certificate_db' does not exist. Attempting to create it...</p>";
            
            if (mysqli_query($conn, "CREATE DATABASE certificate_db")) {
                echo "<p style='color:green'>✅ Successfully created database 'certificate_db'.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to create database: " . mysqli_error($conn) . "</p>";
            }
        }
        
        mysqli_close($conn);
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error checking databases: " . $e->getMessage() . "</p>";
    }
    
    // Step 3: Check PDO extension
    echo "<h2>Step 3: Checking PHP Extensions</h2>";
    if (extension_loaded('pdo_mysql')) {
        echo "<p style='color:green'>✅ PDO MySQL extension is loaded.</p>";
    } else {
        echo "<p style='color:red'>❌ PDO MySQL extension is not loaded. This is required for database connections.</p>";
        echo "<p>Please ensure that the PDO MySQL extension is enabled in your PHP configuration.</p>";
    }
    
    // Step 4: Test connection with config values
    echo "<h2>Step 4: Testing Connection with Configuration</h2>";
    
    // Include the configuration files
    require_once 'secure_config/db_config.php';
    
    echo "<p>Using the following configuration:</p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>Username: " . DB_USER . "</li>";
    echo "<li>Password: " . (empty(DB_PASS) ? "(empty)" : "(set)") . "</li>";
    echo "</ul>";
    
    try {
        $testConn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
            DB_USER, 
            DB_PASS, 
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<p style='color:green'>✅ Successfully connected to database using configuration values.</p>";
        $testConn = null;
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Failed to connect using configuration: " . $e->getMessage() . "</p>";
        
        // Try to determine if it's a credentials issue or a non-existent database
        if (strpos($e->getMessage(), "Unknown database") !== false) {
            echo "<p>The database exists but tables may not be created. Please run the install_database.php script.</p>";
            echo "<p><a href='install_database.php' style='color:blue'>Run Database Installation Script</a></p>";
        }
    }
}

// Provide links to other useful pages
echo "<h2>Next Steps</h2>";
echo "<p><a href='install_database.php' style='margin-right:15px'>Run Database Installation</a>";
echo "<a href='admin/login.php' style='margin-right:15px'>Admin Login</a>";
echo "<a href='index.php'>Home Page</a></p>";
?> 