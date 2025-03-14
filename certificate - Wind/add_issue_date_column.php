<?php
/**
 * Add issue_date column to certificates table
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Add issue_date Column</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if issue_date column exists
    $stmt = $db->query("SHOW COLUMNS FROM certificates LIKE 'issue_date'");
    $issueColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$issueColumn) {
        echo "<p>The issue_date column does not exist in the certificates table. Adding it now...</p>";
        
        // Add the issue_date column
        $db->exec("ALTER TABLE certificates ADD COLUMN issue_date DATE NOT NULL DEFAULT CURRENT_DATE AFTER course_name");
        
        // Update the issue_date column with data from created_at
        $db->exec("UPDATE certificates SET issue_date = DATE(created_at)");
        
        echo "<p style='color:green'>✓ Successfully added issue_date column and populated it with data from created_at</p>";
    } else {
        echo "<p style='color:green'>✓ The issue_date column already exists in the certificates table.</p>";
    }
    
    // Check certificates table structure after modification
    $stmt = $db->query("DESCRIBE certificates");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Current Certificates Table Structure:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>The issue_date column has been verified. Certificates should now work correctly.</p>";
    echo "<p><a href='admin/create_certificate.php'>Go to Create Certificate page</a></p>";
    echo "<p><a href='admin/certificates.php'>Go to Certificates List page</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 