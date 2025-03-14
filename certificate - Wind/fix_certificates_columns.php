<?php
/**
 * Fix Certificates Columns
 * This script checks and fixes issues with the certificates table columns
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Certificates Table Columns</h1>";

try {
    // Connect to database
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get current table structure
    $stmt = $db->query("DESCRIBE certificates");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<h2>Current Table Structure</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h2>Checking Critical Columns</h2>";
    
    // Check for issue_date column
    if (!isset($columns['issue_date'])) {
        echo "<p style='color:orange'>Issue date column is missing. Checking alternatives...</p>";
        
        if (isset($columns['created_at'])) {
            // Option 1: Use created_at as issue_date
            echo "<p>Found 'created_at' column. We will use this for issue_date.</p>";
            
            // Read certificates.php
            $certificatesFile = file_get_contents('admin/certificates.php');
            
            // Replace issue_date with created_at in the SQL query
            $updatedFile = str_replace('c.issue_date', 'c.created_at AS issue_date', $certificatesFile);
            
            // Only save if changes were made
            if ($updatedFile !== $certificatesFile) {
                // Backup original file
                file_put_contents('admin/certificates.php.bak', $certificatesFile);
                
                // Save updated file
                file_put_contents('admin/certificates.php', $updatedFile);
                echo "<p style='color:green'>✅ Updated certificates.php to use 'created_at' instead of 'issue_date'</p>";
            } else {
                echo "<p style='color:orange'>⚠️ No changes made to certificates.php (may already be fixed)</p>";
            }
        } else {
            // Option 2: Add issue_date column
            echo "<p>No suitable column found. Adding 'issue_date' column...</p>";
            $db->exec("ALTER TABLE certificates ADD COLUMN issue_date DATE");
            
            // Update issue_date to match created_at if it exists
            if (isset($columns['created_at'])) {
                $db->exec("UPDATE certificates SET issue_date = DATE(created_at)");
                echo "<p style='color:green'>✅ Added 'issue_date' column and populated with created_at dates</p>";
            } else {
                $db->exec("UPDATE certificates SET issue_date = CURDATE()");
                echo "<p style='color:green'>✅ Added 'issue_date' column and set to current date</p>";
            }
        }
    } else {
        echo "<p style='color:green'>✅ 'issue_date' column already exists.</p>";
    }
    
    // Check for expiry_date column
    if (!isset($columns['expiry_date'])) {
        echo "<p style='color:orange'>Expiry date column is missing. Adding it...</p>";
        $db->exec("ALTER TABLE certificates ADD COLUMN expiry_date DATE NULL");
        
        // Set expiry_date to 1 year after issue_date or created_at
        if (isset($columns['issue_date'])) {
            $db->exec("UPDATE certificates SET expiry_date = DATE_ADD(issue_date, INTERVAL 1 YEAR)");
        } elseif (isset($columns['created_at'])) {
            $db->exec("UPDATE certificates SET expiry_date = DATE_ADD(created_at, INTERVAL 1 YEAR)");
        }
        
        echo "<p style='color:green'>✅ Added 'expiry_date' column and set default values</p>";
    } else {
        echo "<p style='color:green'>✅ 'expiry_date' column already exists.</p>";
    }
    
    // Success message
    echo "<h2 style='color:green'>Table columns check completed</h2>";
    echo "<p>The certificates table has been checked and updated. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/certificates.php'>View Certificates List</a></li>";
    echo "<li><a href='admin/create_certificate.php'>Create a New Certificate</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 