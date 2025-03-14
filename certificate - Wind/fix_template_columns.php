<?php
/**
 * Direct fix for certificate_templates table structure
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database configuration
require_once 'secure_config/db_config.php';

echo "<h2>Fixing Certificate Templates Table Structure</h2>";

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p>Connected to database successfully.</p>";
    
    // First, check if the table exists
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    // If certificate_templates table doesn't exist, create it
    if (!in_array('certificate_templates', $tables)) {
        echo "<p>Certificate templates table doesn't exist. Creating now...</p>";
        
        $pdo->exec("CREATE TABLE certificate_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(255) NOT NULL,
            description TEXT,
            template_image VARCHAR(255),
            name_position_x INT,
            name_position_y INT,
            course_position_x INT,
            course_position_y INT,
            date_position_x INT,
            date_position_y INT,
            qr_position_x INT,
            qr_position_y INT,
            font_family VARCHAR(100),
            font_size INT,
            font_color VARCHAR(20),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        )");
        
        echo "<p style='color:green'>✅ Created certificate_templates table with all required columns.</p>";
    } else {
        // Table exists, check if template_name column exists
        $columns = [];
        $result = $pdo->query("DESCRIBE certificate_templates");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        
        echo "<p>Current columns in certificate_templates table: " . implode(", ", array_keys($columns)) . "</p>";
        
        // Check for template_name column
        if (!isset($columns['template_name'])) {
            echo "<p>template_name column missing. Checking for alternatives...</p>";
            
            // Option 1: Check if there's a name column
            if (isset($columns['name'])) {
                echo "<p>Found 'name' column. Renaming to 'template_name'...</p>";
                $pdo->exec("ALTER TABLE certificate_templates CHANGE `name` `template_name` VARCHAR(255) NOT NULL");
                echo "<p style='color:green'>✅ Renamed 'name' column to 'template_name'</p>";
            }
            // Option 2: Check if there's a title column
            else if (isset($columns['title'])) {
                echo "<p>Found 'title' column. Renaming to 'template_name'...</p>";
                $pdo->exec("ALTER TABLE certificate_templates CHANGE `title` `template_name` VARCHAR(255) NOT NULL");
                echo "<p style='color:green'>✅ Renamed 'title' column to 'template_name'</p>";
            }
            // Option 3: Add template_name column
            else {
                echo "<p>No suitable column found. Adding 'template_name' column...</p>";
                $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN `template_name` VARCHAR(255) NOT NULL DEFAULT 'Default Template'");
                echo "<p style='color:green'>✅ Added 'template_name' column with default value</p>";
            }
        } else {
            echo "<p style='color:green'>✅ 'template_name' column already exists.</p>";
        }
        
        // Check for other required columns
        $requiredColumns = [
            'description' => 'TEXT',
            'template_image' => 'VARCHAR(255)',
            'name_position_x' => 'INT',
            'name_position_y' => 'INT',
            'course_position_x' => 'INT',
            'course_position_y' => 'INT',
            'date_position_x' => 'INT',
            'date_position_y' => 'INT',
            'qr_position_x' => 'INT',
            'qr_position_y' => 'INT',
            'font_family' => 'VARCHAR(100)',
            'font_size' => 'INT',
            'font_color' => 'VARCHAR(20)',
            'created_by' => 'INT',
            'created_at' => 'TIMESTAMP'
        ];
        
        foreach ($requiredColumns as $column => $type) {
            if (!isset($columns[$column])) {
                echo "<p>Column '$column' missing. Adding...</p>";
                
                if ($type == 'TIMESTAMP') {
                    $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN `$column` $type DEFAULT CURRENT_TIMESTAMP");
                } else if ($type == 'INT') {
                    $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN `$column` $type DEFAULT 0");
                } else if ($type == 'VARCHAR(255)' || $type == 'VARCHAR(100)' || $type == 'VARCHAR(20)') {
                    $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN `$column` $type DEFAULT ''");
                } else {
                    $pdo->exec("ALTER TABLE certificate_templates ADD COLUMN `$column` $type");
                }
                
                echo "<p style='color:green'>✅ Added '$column' column</p>";
            }
        }
    }
    
    echo "<h2>All Template Table Issues Fixed!</h2>";
    echo "<p>The template creation form should now work correctly. <a href='admin/create_template.php'>Create Template</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<h3>Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?> 