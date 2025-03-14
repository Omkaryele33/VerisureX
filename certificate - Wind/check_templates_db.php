<?php
/**
 * Template Database Diagnostic
 * This script checks the certificate_templates table structure and contents
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load required files
require_once 'includes/config.php';
require_once 'includes/database.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Function to display data in a readable format
function displayData($title, $data) {
    echo "<h3>$title</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    echo "<hr>";
}

echo "<h1>Certificate Templates Table Diagnostic</h1>";

// Check if table exists
try {
    $query = "SHOW TABLES LIKE 'certificate_templates'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h2>Table Check</h2>";
    if ($tableExists) {
        echo "<p style='color: green;'>✓ certificate_templates table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ certificate_templates table does not exist</p>";
        exit("Table does not exist. Please run database installation script.");
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error checking table: " . $e->getMessage() . "</p>";
    exit();
}

// Get table structure
try {
    $query = "DESCRIBE certificate_templates";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    displayData("Table Structure", $columns);
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error getting table structure: " . $e->getMessage() . "</p>";
}

// Count templates
try {
    $query = "SELECT COUNT(*) as count FROM certificate_templates";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Template Count</h2>";
    echo "<p>There are <strong>{$count['count']}</strong> templates in the database.</p>";
    
    if ($count['count'] === 0) {
        echo "<p style='color: orange;'>⚠ No templates found. This explains why no templates show in the dropdown.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error counting templates: " . $e->getMessage() . "</p>";
}

// List all templates
try {
    $query = "SELECT * FROM certificate_templates";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    displayData("All Templates", $templates);
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error listing templates: " . $e->getMessage() . "</p>";
}

// Column name mismatch check
echo "<h2>Column Name Check</h2>";
echo "<p>Checking for potential column name mismatches between database and code...</p>";

$foundName = false;
$foundTemplateName = false;

foreach ($columns as $column) {
    if ($column['Field'] === 'name') {
        $foundName = true;
        echo "<p style='color: green;'>✓ Found 'name' column</p>";
    }
    if ($column['Field'] === 'template_name') {
        $foundTemplateName = true;
        echo "<p style='color: green;'>✓ Found 'template_name' column</p>";
    }
}

if (!$foundName && !$foundTemplateName) {
    echo "<p style='color: red;'>✗ Neither 'name' nor 'template_name' column found in the database</p>";
} elseif ($foundName && $foundTemplateName) {
    echo "<p style='color: orange;'>⚠ Both 'name' and 'template_name' columns exist which could cause confusion</p>";
} elseif ($foundName && !$foundTemplateName) {
    echo "<p style='color: orange;'>⚠ Found 'name' but not 'template_name' while create_certificate.php expects 'name'</p>";
} elseif (!$foundName && $foundTemplateName) {
    echo "<p style='color: red;'>✗ Found 'template_name' but not 'name', but create_certificate.php expects 'name'</p>";
    echo "<p>This explains why the dropdown is empty - we need to fix the SQL query in create_certificate.php</p>";
}

echo "<h2>Create Certificate SQL Check</h2>";
echo "<p>The SQL query in create_certificate.php is:<br>";
echo "<code>SELECT template_id, name FROM certificate_templates ORDER BY name</code></p>";

// Attempt a compatible query
try {
    $compatQuery = "SELECT template_id, template_name as name FROM certificate_templates ORDER BY template_name";
    $stmt = $db->prepare($compatQuery);
    $stmt->execute();
    $compatResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✓ This compatible query works: <code>$compatQuery</code></p>";
    displayData("Compatible Query Results", $compatResult);
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error with compatible query: " . $e->getMessage() . "</p>";
} 