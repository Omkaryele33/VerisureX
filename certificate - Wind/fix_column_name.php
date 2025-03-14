<?php
/**
 * Direct fix for column name mismatch in certificates table
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'secure_config/db_config.php';

echo "<h2>Fixing Certificate Table Column Names</h2>";

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Connected to database successfully.</p>";
    
    // 1. Check if the 'name' column exists but 'holder_name' doesn't
    $columns = [];
    $result = $pdo->query("DESCRIBE certificates");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns: " . implode(", ", array_keys($columns)) . "</p>";
    
    // Option 1: Rename 'name' to 'holder_name' if it exists
    if (isset($columns['name']) && !isset($columns['holder_name'])) {
        echo "<p>Found 'name' column but no 'holder_name' column. Renaming...</p>";
        $pdo->exec("ALTER TABLE certificates CHANGE `name` `holder_name` VARCHAR(255) NOT NULL");
        echo "<p style='color:green'>✅ Successfully renamed 'name' column to 'holder_name'</p>";
    }
    // Option 2: Add 'holder_name' column if neither exists
    else if (!isset($columns['name']) && !isset($columns['holder_name'])) {
        echo "<p>Neither 'name' nor 'holder_name' column exists. Adding 'holder_name'...</p>";
        $pdo->exec("ALTER TABLE certificates ADD COLUMN `holder_name` VARCHAR(255) NOT NULL DEFAULT 'Unnamed'");
        echo "<p style='color:green'>✅ Added 'holder_name' column</p>";
    }
    // Option 3: holder_name already exists
    else if (isset($columns['holder_name'])) {
        echo "<p style='color:green'>✅ 'holder_name' column already exists. No changes needed.</p>";
    }
    
    // Check if 'course' exists but 'course_name' doesn't
    if (isset($columns['course']) && !isset($columns['course_name'])) {
        echo "<p>Found 'course' column but no 'course_name' column. Renaming...</p>";
        $pdo->exec("ALTER TABLE certificates CHANGE `course` `course_name` VARCHAR(255) NOT NULL");
        echo "<p style='color:green'>✅ Successfully renamed 'course' column to 'course_name'</p>";
    }
    // Add course_name if neither exists
    else if (!isset($columns['course']) && !isset($columns['course_name'])) {
        echo "<p>Neither 'course' nor 'course_name' column exists. Adding 'course_name'...</p>";
        $pdo->exec("ALTER TABLE certificates ADD COLUMN `course_name` VARCHAR(255) NOT NULL DEFAULT 'Unnamed Course'");
        echo "<p style='color:green'>✅ Added 'course_name' column</p>";
    }
    
    // Check if admin_id exists but created_by doesn't
    if (isset($columns['admin_id']) && !isset($columns['created_by'])) {
        echo "<p>Found 'admin_id' column but no 'created_by' column. Renaming...</p>";
        $pdo->exec("ALTER TABLE certificates CHANGE `admin_id` `created_by` INT");
        echo "<p style='color:green'>✅ Successfully renamed 'admin_id' column to 'created_by'</p>";
    }
    // Add created_by if neither exists
    else if (!isset($columns['admin_id']) && !isset($columns['created_by'])) {
        echo "<p>Neither 'admin_id' nor 'created_by' column exists. Adding 'created_by'...</p>";
        $pdo->exec("ALTER TABLE certificates ADD COLUMN `created_by` INT NULL");
        echo "<p style='color:green'>✅ Added 'created_by' column</p>";
    }
    
    // Verify changes
    echo "<h2>Verifying Changes</h2>";
    $columns = [];
    $result = $pdo->query("DESCRIBE certificates");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Updated columns: " . implode(", ", array_keys($columns)) . "</p>";
    
    // Check if verification_logs table has the right columns
    echo "<h2>Checking Verification Logs Table</h2>";
    $columns = [];
    $result = $pdo->query("DESCRIBE verification_logs");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns: " . implode(", ", array_keys($columns)) . "</p>";
    
    // Add verified_at alias if needed for verification_date
    if (isset($columns['verification_date']) && !isset($columns['verified_at'])) {
        echo "<p>Found 'verification_date' but no 'verified_at'. Creating a view to handle this...</p>";
        try {
            // Create a view that aliases verification_date to verified_at
            $pdo->exec("CREATE OR REPLACE VIEW verification_logs_view AS 
                       SELECT *, verification_date AS verified_at 
                       FROM verification_logs");
            echo "<p style='color:green'>✅ Created a view to alias verification_date as verified_at</p>";
        } catch (PDOException $e) {
            echo "<p>Note: Using 'verification_date AS verified_at' in queries is recommended.</p>";
        }
    }
    
    echo "<h2>All Column Issues Fixed!</h2>";
    echo "<p>Your dashboard should now work correctly. <a href='admin/dashboard.php'>Go to Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 