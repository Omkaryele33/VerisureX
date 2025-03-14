<?php
/**
 * Database Schema Fix Script
 * This script updates the certificates table to match the original schema used in the application
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing Certificate Table Schema</h1>";

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
    
    // Check certificates table structure
    echo "<h2>Checking Current Table Structure</h2>";
    
    // Check certificates table fields
    $result = $pdo->query("DESCRIBE certificates");
    $columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns: " . implode(", ", array_keys($columns)) . "</p>";
    
    // Check if the table needs to be updated
    $needsUpdate = false;
    $missingColumns = [];
    
    // Required columns based on original schema
    $requiredColumns = [
        'id', 'certificate_id', 'holder_name', 'course_name', 'issue_date', 
        'expiry_date', 'is_active', 'revocation_reason', 'certificate_image'
    ];
    
    foreach ($requiredColumns as $column) {
        if (!isset($columns[$column])) {
            $needsUpdate = true;
            $missingColumns[] = $column;
        }
    }
    
    if ($needsUpdate) {
        echo "<p style='color:orange'>⚠️ Table structure needs to be updated. Missing columns: " . implode(", ", $missingColumns) . "</p>";
        
        // Update the table based on what's missing
        echo "<h2>Updating Table Structure</h2>";
        
        // If holder_name column is missing but name exists, rename it
        if (!isset($columns['holder_name']) && isset($columns['name'])) {
            $pdo->exec("ALTER TABLE certificates CHANGE name holder_name VARCHAR(255) NOT NULL");
            echo "<p style='color:green'>✅ Renamed 'name' column to 'holder_name'</p>";
        }
        
        // If course_name is missing but course exists, rename it
        if (!isset($columns['course_name']) && isset($columns['course'])) {
            $pdo->exec("ALTER TABLE certificates CHANGE course course_name VARCHAR(255) NOT NULL");
            echo "<p style='color:green'>✅ Renamed 'course' column to 'course_name'</p>";
        }
        
        // Add other missing columns
        if (!isset($columns['is_active'])) {
            $pdo->exec("ALTER TABLE certificates ADD COLUMN is_active TINYINT(1) DEFAULT 1");
            echo "<p style='color:green'>✅ Added 'is_active' column</p>";
        }
        
        if (!isset($columns['revocation_reason'])) {
            $pdo->exec("ALTER TABLE certificates ADD COLUMN revocation_reason TEXT");
            echo "<p style='color:green'>✅ Added 'revocation_reason' column</p>";
        }
        
        if (!isset($columns['certificate_image'])) {
            $pdo->exec("ALTER TABLE certificates ADD COLUMN certificate_image VARCHAR(255)");
            echo "<p style='color:green'>✅ Added 'certificate_image' column</p>";
        }
        
        // Add created_by column if missing (for joining with admins)
        if (!isset($columns['created_by']) && !isset($columns['admin_id'])) {
            $pdo->exec("ALTER TABLE certificates ADD COLUMN created_by INT");
            echo "<p style='color:green'>✅ Added 'created_by' column</p>";
        } else if (isset($columns['admin_id']) && !isset($columns['created_by'])) {
            $pdo->exec("ALTER TABLE certificates CHANGE admin_id created_by INT");
            echo "<p style='color:green'>✅ Renamed 'admin_id' column to 'created_by'</p>";
        }
        
        echo "<p style='color:green'>✅ Table structure updated successfully.</p>";
    } else {
        echo "<p style='color:green'>✅ Table structure is already correct.</p>";
    }
    
    // Check verification_logs table structure
    echo "<h2>Checking Verification Logs Table</h2>";
    
    $result = $pdo->query("DESCRIBE verification_logs");
    $columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns: " . implode(", ", array_keys($columns)) . "</p>";
    
    // If verification_date exists but verified_at doesn't, add an alias in queries
    if (isset($columns['verification_date']) && !isset($columns['verified_at'])) {
        echo "<p style='color:orange'>⚠️ Note: Use 'verification_date AS verified_at' in queries.</p>";
    }
    
    echo "<p style='color:green'>✅ All database schema issues fixed.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database Error: " . $e->getMessage() . "</p>";
}

// Provide link to admin dashboard
echo "<p style='margin-top:20px'><a href='admin/dashboard.php' style='display:inline-block; padding:10px 20px; background-color:#007bff; color:white; text-decoration:none; border-radius:5px;'>Go to Dashboard</a></p>";
?> 