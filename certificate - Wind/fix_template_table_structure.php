<?php
/**
 * Comprehensive fix for certificate_templates table structure and related files
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'secure_config/db_config.php';

echo "<h2>Fixing Certificate Templates Table Structure and References</h2>";

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Connected to database successfully.</p>";
    
    // Check certificate_templates table structure
    $result = $pdo->query("DESCRIBE certificate_templates");
    $templateColumns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $templateColumns[$row['Field']] = $row;
    }
    
    echo "<p>Current columns in certificate_templates table: " . implode(", ", array_keys($templateColumns)) . "</p>";
    
    // Check if there's a primary key column
    $hasPrimaryKey = false;
    $primaryKeyName = null;
    
    foreach ($templateColumns as $field => $data) {
        if ($data['Key'] === 'PRI') {
            $hasPrimaryKey = true;
            $primaryKeyName = $field;
            break;
        }
    }
    
    if (!$hasPrimaryKey) {
        echo "<p style='color:red'>No primary key found in certificate_templates table. Adding one...</p>";
        
        // Add an id column as primary key if none exists
        $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN template_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
        echo "<p style='color:green'>✅ Added template_id as primary key</p>";
        $primaryKeyName = 'template_id';
    } else {
        echo "<p style='color:green'>✅ Found primary key: $primaryKeyName</p>";
    }
    
    // Check for created_at column which is referenced in the template listing
    if (!isset($templateColumns['created_at'])) {
        echo "<p>No created_at column found. Adding one...</p>";
        $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p style='color:green'>✅ Added created_at column</p>";
    }
    
    // Check for description column 
    if (!isset($templateColumns['description'])) {
        echo "<p>No description column found. Adding one...</p>";
        $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN description TEXT");
        echo "<p style='color:green'>✅ Added description column</p>";
    }
    
    // Now fix the certificate_templates.php file
    $filePath = 'admin/certificate_templates.php';
    $fileContent = file_get_contents($filePath);
    if ($fileContent) {
        // Create backup
        file_put_contents($filePath . '.bak', $fileContent);
        
        // Update the query to use the correct primary key
        $pattern = "/SELECT\s+ct\..*,\s+ct\..*,\s+COUNT\(.*\)\s+AS\s+usage_count\s+FROM\s+certificate_templates\s+ct\s+LEFT\s+JOIN\s+certificates\s+c\s+ON\s+.*\s+GROUP\s+BY\s+.*/s";
        
        $replacement = "SELECT 
    ct.$primaryKeyName AS template_id, 
    ct.template_name AS name, 
    COUNT(c.id) AS usage_count
FROM certificate_templates ct
LEFT JOIN certificates c ON ct.$primaryKeyName = c.template_id
GROUP BY ct.$primaryKeyName";
        
        $updatedContent = preg_replace($pattern, $replacement, $fileContent);
        
        // Update references in DELETE query
        if ($primaryKeyName !== 'id') {
            $updatedContent = str_replace(
                "DELETE FROM certificate_templates WHERE id = :id", 
                "DELETE FROM certificate_templates WHERE $primaryKeyName = :id", 
                $updatedContent
            );
            
            $updatedContent = str_replace(
                "SELECT template_image FROM certificate_templates WHERE id = :id", 
                "SELECT template_image FROM certificate_templates WHERE $primaryKeyName = :id", 
                $updatedContent
            );
        }
        
        // Make sure there are no other uses of 'ct.id' in the file
        if ($primaryKeyName !== 'id') {
            $updatedContent = str_replace("ct.id", "ct.$primaryKeyName", $updatedContent);
        }
        
        if ($updatedContent !== $fileContent) {
            file_put_contents($filePath, $updatedContent);
            echo "<p style='color:green'>✅ Updated certificate_templates.php to use correct column names</p>";
        } else {
            echo "<p style='color:orange'>No changes were needed in certificate_templates.php</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Could not read certificate_templates.php</p>";
    }
    
    // Let's also add a template_id column to certificates table if it doesn't exist
    $result = $pdo->query("DESCRIBE certificates");
    $certificateColumns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $certificateColumns[$row['Field']] = $row;
    }
    
    if (!isset($certificateColumns['template_id'])) {
        echo "<p>No template_id column found in certificates table. Adding one...</p>";
        $pdo->exec("ALTER TABLE certificates ADD COLUMN template_id INT NULL");
        echo "<p style='color:green'>✅ Added template_id column to certificates table</p>";
    }
    
    echo "<h2>All Template Structure Issues Fixed!</h2>";
    echo "<p>The certificate templates listing page should now work correctly.</p>";
    echo "<p><a href='admin/certificate_templates.php' class='btn btn-primary'>View Template Listing</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 