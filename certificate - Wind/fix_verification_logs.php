<?php
/**
 * Direct fix for verification_logs table column name issue
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'secure_config/db_config.php';

echo "<h2>Fixing Verification Logs Table Structure</h2>";

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Connected to database successfully.</p>";
    
    // Check the structure of the verification_logs table
    $result = $pdo->query("DESCRIBE verification_logs");
    $columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns in verification_logs: " . implode(", ", array_keys($columns)) . "</p>";
    
    // Check if verification_date exists
    if (!isset($columns['verification_date'])) {
        // Look for verified_at or other potential date columns
        if (isset($columns['verified_at'])) {
            echo "<p>Found 'verified_at' column. Adding an alias for 'verification_date'...</p>";
            
            // Fix dashboard.php to use verified_at instead of verification_date
            $dashboardFile = file_get_contents('admin/dashboard.php');
            if ($dashboardFile) {
                // Replace verification_date with verified_at in the queries
                $updatedFile = str_replace('verification_date', 'verified_at', $dashboardFile);
                
                if ($updatedFile !== $dashboardFile) {
                    // Create a backup
                    file_put_contents('admin/dashboard.php.bak', $dashboardFile);
                    
                    // Save the updated file
                    file_put_contents('admin/dashboard.php', $updatedFile);
                    echo "<p style='color:green'>✅ Updated dashboard.php to use 'verified_at' instead of 'verification_date'</p>";
                } else {
                    echo "<p style='color:red'>❌ No changes made to dashboard.php</p>";
                }
            } else {
                echo "<p style='color:red'>❌ Could not read dashboard.php</p>";
            }
        } 
        else if (isset($columns['created_at'])) {
            echo "<p>Found 'created_at' column. Adding an alias for 'verification_date'...</p>";
            
            // Fix dashboard.php to use created_at instead of verification_date
            $dashboardFile = file_get_contents('admin/dashboard.php');
            if ($dashboardFile) {
                // Replace verification_date with created_at in the queries
                $updatedFile = str_replace('verification_date', 'created_at', $dashboardFile);
                
                if ($updatedFile !== $dashboardFile) {
                    // Create a backup
                    file_put_contents('admin/dashboard.php.bak', $dashboardFile);
                    
                    // Save the updated file
                    file_put_contents('admin/dashboard.php', $updatedFile);
                    echo "<p style='color:green'>✅ Updated dashboard.php to use 'created_at' instead of 'verification_date'</p>";
                } else {
                    echo "<p style='color:red'>❌ No changes made to dashboard.php</p>";
                }
            } else {
                echo "<p style='color:red'>❌ Could not read dashboard.php</p>";
            }
        }
        else {
            // No suitable column found, so add verification_date column
            echo "<p>No suitable date column found. Adding 'verification_date' column...</p>";
            $pdo->exec("ALTER TABLE verification_logs ADD COLUMN verification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "<p style='color:green'>✅ Added 'verification_date' column to verification_logs table</p>";
            
            // If there's another timestamp column, update verification_date to match it
            if (isset($columns['created_at'])) {
                $pdo->exec("UPDATE verification_logs SET verification_date = created_at");
                echo "<p style='color:green'>✅ Populated 'verification_date' with values from 'created_at'</p>";
            }
        }
    } else {
        echo "<p style='color:green'>✅ 'verification_date' column already exists in verification_logs table.</p>";
    }
    
    // Check other files that might reference verification_date to ensure consistency
    echo "<h2>Checking Verification Logs Views</h2>";
    
    // Check verification_logs.php file
    $verificationLogsFile = file_get_contents('admin/verification_logs.php');
    if ($verificationLogsFile) {
        $dateColumn = isset($columns['verification_date']) ? 'verification_date' : 
                     (isset($columns['verified_at']) ? 'verified_at' : 
                     (isset($columns['created_at']) ? 'created_at' : null));
        
        if ($dateColumn && $dateColumn !== 'verification_date') {
            $updatedFile = str_replace('verification_date', $dateColumn, $verificationLogsFile);
            $updatedFile = str_replace("$dateColumn AS verified_at", "$dateColumn AS verified_at", $updatedFile); // Fix double replacement
            
            if ($updatedFile !== $verificationLogsFile) {
                file_put_contents('admin/verification_logs.php.bak', $verificationLogsFile);
                file_put_contents('admin/verification_logs.php', $updatedFile);
                echo "<p style='color:green'>✅ Updated verification_logs.php to use '$dateColumn'</p>";
            }
        }
    }
    
    echo "<h2>All Column Name Issues Fixed!</h2>";
    echo "<p>Your dashboard should now work correctly. <a href='admin/dashboard.php'>Go to Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 