<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'secure_config/db_config.php';

echo "<h1>Certificate Templates Table Structure</h1>";

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
    echo "<h2>Certificate Templates Table Columns:</h2>";
    echo "<ul>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<li><strong>" . $row['Field'] . "</strong> - " . $row['Type'] . "</li>";
    }
    echo "</ul>";
    
    // Check certificates table structure
    $result = $pdo->query("DESCRIBE certificates");
    echo "<h2>Certificates Table Columns:</h2>";
    echo "<ul>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<li><strong>" . $row['Field'] . "</strong> - " . $row['Type'] . "</li>";
    }
    echo "</ul>";
    
    // List sample data from certificate_templates
    $result = $pdo->query("SELECT * FROM certificate_templates LIMIT 5");
    echo "<h2>Sample Certificate Templates Data:</h2>";
    $templates = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($templates) > 0) {
        echo "<table border='1' cellpadding='5'>";
        // Headers
        echo "<tr>";
        foreach (array_keys($templates[0]) as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($templates as $template) {
            echo "<tr>";
            foreach ($template as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No template data found.</p>";
    }
    
    // Check if there is a template_id column in certificates table
    $result = $pdo->query("DESCRIBE certificates");
    $hasTemplateId = false;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Field'] == 'template_id') {
            $hasTemplateId = true;
            break;
        }
    }
    
    if (!$hasTemplateId) {
        echo "<p style='color:red'>Warning: No template_id column found in certificates table!</p>";
    } else {
        echo "<p style='color:green'>Good: template_id column exists in certificates table.</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 