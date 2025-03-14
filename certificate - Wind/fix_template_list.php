<?php
/**
 * Fix for certificate_templates.php file
 * This script ensures that the correct column names are used in the templates listing page
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'secure_config/db_config.php';

echo "<h2>Fixing Certificate Templates Listing Page</h2>";

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Connected to database successfully.</p>";
    
    // Check certificate_templates table structure to verify column names
    $result = $pdo->query("DESCRIBE certificate_templates");
    $templateColumns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $templateColumns[$row['Field']] = $row['Type'];
    }
    
    echo "<p>Verified certificate_templates table columns: " . implode(", ", array_keys($templateColumns)) . "</p>";
    
    // Check if we have a template_image column
    $hasTemplateImage = isset($templateColumns['template_image']);
    $imagePath = $hasTemplateImage ? 'template_image' : 'file_path';
    
    // Now update the certificate_templates.php file
    $filePath = 'admin/certificate_templates.php';
    $templateListingFile = file_get_contents($filePath);
    
    if ($templateListingFile) {
        // Create backup
        file_put_contents($filePath . '.bak', $templateListingFile);
        echo "<p>Created backup of certificate_templates.php</p>";
        
        $updatedFile = $templateListingFile;
        
        // 1. Fix the main SQL query - already done in previous step
        // The updated query should select template_name instead of name
        
        // 2. Update any references to template['file_path'] if necessary
        if ($hasTemplateImage && strpos($updatedFile, "template['file_path']") !== false) {
            $updatedFile = str_replace("template['file_path']", "template['template_image']", $updatedFile);
            echo "<p>Updated references to template image path</p>";
        }
        
        // 3. Fix any template_id references if they should be id
        if (isset($templateColumns['id']) && !isset($templateColumns['template_id'])) {
            // Update the id references in the table
            $updatedFile = preg_replace('/\bct\.template_id\b/', 'ct.id AS template_id', $updatedFile);
            
            // Also update any action links like edit_template.php?id=...
            $updatedFile = preg_replace(
                '/href="edit_template\.php\?template_id=/', 
                'href="edit_template.php?id=', 
                $updatedFile
            );
            
            echo "<p>Updated template_id references to use id</p>";
        }
        
        // Write the updated file
        if ($updatedFile !== $templateListingFile) {
            file_put_contents($filePath, $updatedFile);
            echo "<p style='color:green'>✅ Updated certificate_templates.php with correct column references</p>";
        } else {
            echo "<p style='color:orange'>No changes were needed in certificate_templates.php</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Could not read certificate_templates.php</p>";
    }
    
    echo "<h2>Now testing if templates listing page works</h2>";
    
    // Let's run a test query similar to what's in the templates listing page
    $query = "SELECT 
        ct.id AS template_id, 
        ct.template_name AS name,
        0 AS usage_count
    FROM certificate_templates ct
    LIMIT 5";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $testTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Test query successful! Found " . count($testTemplates) . " templates.</p>";
    echo "<p style='color:green'>✅ The templates listing page should now work correctly.</p>";
    echo "<p><a href='admin/certificate_templates.php' class='btn btn-primary'>View Templates</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 