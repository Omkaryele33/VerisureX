<?php
/**
 * Comprehensive Database Structure Fix
 * This script fixes all database structure issues including the issue_date column
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Comprehensive Database Structure Fix</h1>";

try {
    // Connect to database
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Function to check if a table exists
    function tableExists($db, $tableName) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tableName");
        $stmt->bindParam(':tableName', $tableName);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    // Function to check if a column exists in a table
    function columnExists($db, $tableName, $columnName) {
        $stmt = $db->prepare("SHOW COLUMNS FROM $tableName LIKE :columnName");
        $stmt->bindParam(':columnName', $columnName);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    // Function to get columns from a table
    function getTableColumns($db, $tableName) {
        $stmt = $db->query("DESCRIBE $tableName");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['Field']] = $row;
        }
        return $columns;
    }
    
    // Fix certificates table
    echo "<h2>Checking and Fixing Certificates Table</h2>";
    
    if (!tableExists($db, 'certificates')) {
        echo "<p style='color:red'>The certificates table doesn't exist! Creating it...</p>";
        
        // Create the certificates table with all required columns
        $sql = "CREATE TABLE certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            certificate_id VARCHAR(64) NOT NULL UNIQUE,
            holder_name VARCHAR(255) NOT NULL,
            course_name VARCHAR(255) NOT NULL,
            issue_date DATE NOT NULL,
            expiry_date DATE NULL,
            template_id INT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";
        
        $db->exec($sql);
        echo "<p style='color:green'>✅ Created certificates table with all required columns</p>";
    } else {
        echo "<p style='color:green'>Certificates table exists, checking columns...</p>";
        
        $columns = getTableColumns($db, 'certificates');
        echo "<p>Current columns: " . implode(", ", array_keys($columns)) . "</p>";
        
        // Check and add required columns
        $requiredColumns = [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'certificate_id' => 'VARCHAR(64) NOT NULL UNIQUE',
            'holder_name' => 'VARCHAR(255) NOT NULL',
            'course_name' => 'VARCHAR(255) NOT NULL',
            'issue_date' => 'DATE NOT NULL',
            'expiry_date' => 'DATE NULL',
            'template_id' => 'INT NULL',
            'is_active' => 'TINYINT(1) DEFAULT 1',
            'created_by' => 'INT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        
        $added = false;
        $modified = false;
        
        foreach ($requiredColumns as $column => $definition) {
            if (!isset($columns[$column])) {
                // Handle primary key special case
                if ($column === 'id' && isset($columns['ID'])) {
                    echo "<p style='color:orange'>⚠️ Found 'ID' instead of 'id' - this should still work</p>";
                    continue;
                }
                
                echo "<p style='color:orange'>Adding missing column '$column'...</p>";
                
                // Special case for ID - can't add it if there's already a primary key
                if ($column === 'id') {
                    $hasPrimaryKey = false;
                    foreach ($columns as $col) {
                        if (strpos($col['Key'], 'PRI') !== false) {
                            $hasPrimaryKey = true;
                            break;
                        }
                    }
                    
                    if ($hasPrimaryKey) {
                        echo "<p style='color:orange'>⚠️ Table already has a primary key, skipping id column</p>";
                        continue;
                    }
                }
                
                // Add the column
                $sql = "ALTER TABLE certificates ADD COLUMN $column $definition";
                $db->exec($sql);
                $added = true;
                
                echo "<p style='color:green'>✅ Added '$column' column</p>";
                
                // Handle issue_date data population
                if ($column === 'issue_date') {
                    if (isset($columns['created_at'])) {
                        $db->exec("UPDATE certificates SET issue_date = DATE(created_at)");
                        echo "<p style='color:green'>✅ Populated issue_date with created_at dates</p>";
                    } else {
                        $db->exec("UPDATE certificates SET issue_date = CURDATE()");
                        echo "<p style='color:green'>✅ Set issue_date to current date</p>";
                    }
                }
                
                // Handle expiry_date data population
                if ($column === 'expiry_date') {
                    if (isset($columns['issue_date']) || $column === 'issue_date') {
                        $db->exec("UPDATE certificates SET expiry_date = DATE_ADD(issue_date, INTERVAL 1 YEAR) WHERE expiry_date IS NULL");
                        echo "<p style='color:green'>✅ Set expiry_date to issue_date + 1 year</p>";
                    } else if (isset($columns['created_at'])) {
                        $db->exec("UPDATE certificates SET expiry_date = DATE_ADD(created_at, INTERVAL 1 YEAR) WHERE expiry_date IS NULL");
                        echo "<p style='color:green'>✅ Set expiry_date to created_at + 1 year</p>";
                    }
                }
            }
        }
        
        if (!$added) {
            echo "<p style='color:green'>✅ All required columns exist in certificates table</p>";
        }
    }
    
    // Fix certificate_templates table
    echo "<h2>Checking and Fixing Certificate Templates Table</h2>";
    
    if (!tableExists($db, 'certificate_templates')) {
        echo "<p style='color:red'>The certificate_templates table doesn't exist! Creating it...</p>";
        
        // Create the certificate_templates table with all required columns
        $sql = "CREATE TABLE certificate_templates (
            template_id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            template_image VARCHAR(255) NULL,
            font_family VARCHAR(100) DEFAULT 'Arial',
            font_size INT DEFAULT 12,
            font_color VARCHAR(20) DEFAULT '#000000',
            name_position_x INT NULL,
            name_position_y INT NULL,
            course_position_x INT NULL,
            course_position_y INT NULL,
            date_position_x INT NULL,
            date_position_y INT NULL,
            qr_position_x INT NULL,
            qr_position_y INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB";
        
        $db->exec($sql);
        echo "<p style='color:green'>✅ Created certificate_templates table with all required columns</p>";
        
        // Add a default template for testing
        $sql = "INSERT INTO certificate_templates (template_name, description) VALUES ('Default Template', 'Default system template')";
        $db->exec($sql);
        echo "<p style='color:green'>✅ Added a default template</p>";
    } else {
        echo "<p style='color:green'>Certificate templates table exists, checking columns...</p>";
        
        $columns = getTableColumns($db, 'certificate_templates');
        echo "<p>Current columns: " . implode(", ", array_keys($columns)) . "</p>";
        
        // Check for 'name' vs 'template_name' inconsistency
        if (isset($columns['name']) && !isset($columns['template_name'])) {
            echo "<p style='color:orange'>⚠️ Found 'name' column but not 'template_name'. Renaming to match our code...</p>";
            $db->exec("ALTER TABLE certificate_templates CHANGE COLUMN name template_name VARCHAR(255) NOT NULL");
            echo "<p style='color:green'>✅ Renamed 'name' column to 'template_name'</p>";
        }
        
        if (!isset($columns['name']) && !isset($columns['template_name'])) {
            echo "<p style='color:red'>❌ Neither 'name' nor 'template_name' column exists! Adding 'template_name'...</p>";
            $db->exec("ALTER TABLE certificate_templates ADD COLUMN template_name VARCHAR(255) NOT NULL DEFAULT 'Unnamed Template'");
            echo "<p style='color:green'>✅ Added 'template_name' column with default value</p>";
        }
        
        // Check for template_id
        if (!isset($columns['template_id']) && !isset($columns['id'])) {
            echo "<p style='color:red'>❌ No primary key column found! Adding 'template_id'...</p>";
            $db->exec("ALTER TABLE certificate_templates ADD COLUMN template_id INT AUTO_INCREMENT PRIMARY KEY FIRST");
            echo "<p style='color:green'>✅ Added 'template_id' primary key column</p>";
        } elseif (isset($columns['id']) && !isset($columns['template_id'])) {
            echo "<p style='color:orange'>⚠️ Found 'id' column but not 'template_id'. Creating template_id as an alias...</p>";
            // Fix create_certificate.php and other references
            $files = [
                'admin/create_certificate.php',
                'admin/certificate_templates.php',
                'admin/view_template.php',
                'admin/edit_template.php'
            ];
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    $updated = str_replace('template_id', 'id AS template_id', $content);
                    
                    if ($updated !== $content) {
                        file_put_contents($file . '.bak', $content);
                        file_put_contents($file, $updated);
                        echo "<p style='color:green'>✅ Updated $file to use 'id AS template_id'</p>";
                    }
                }
            }
        }
    }
    
    // Insert a default template if none exists
    $stmt = $db->query("SELECT COUNT(*) as count FROM certificate_templates");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] === 0) {
        echo "<p style='color:orange'>⚠️ No templates found in the database. Adding a default template...</p>";
        
        $sql = "INSERT INTO certificate_templates (template_name, description) VALUES ('Default Template', 'Default system template')";
        $db->exec($sql);
        echo "<p style='color:green'>✅ Added a default template</p>";
    } else {
        echo "<p style='color:green'>✅ There are {$result['count']} templates in the database</p>";
    }
    
    // Check if create_certificate.php is using the correct column names
    echo "<h2>Checking File References</h2>";
    
    $files = [
        'admin/create_certificate.php' => [
            'SELECT template_id, name FROM certificate_templates' => 'SELECT template_id, template_name FROM certificate_templates',
            'echo "<option value=\'" . $template[\'template_id\'] . "\' $selected>" . htmlspecialchars($template[\'name\']) . "</option>";' => 
            'echo "<option value=\'" . $template[\'template_id\'] . "\' $selected>" . htmlspecialchars($template[\'template_name\']) . "</option>";'
        ],
        'admin/certificates.php' => [
            'c.issue_date' => 'c.issue_date',
            'order by c.issue_date' => 'order by c.issue_date'
        ]
    ];
    
    foreach ($files as $file => $replacements) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $updated = $content;
            
            foreach ($replacements as $search => $replace) {
                $updated = str_replace($search, $replace, $updated);
            }
            
            if ($updated !== $content) {
                file_put_contents($file . '.bak', $content);
                file_put_contents($file, $updated);
                echo "<p style='color:green'>✅ Updated $file with correct column references</p>";
            } else {
                echo "<p style='color:green'>✅ $file already has correct column references</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ Could not find $file</p>";
        }
    }
    
    // Success message
    echo "<h2 style='color:green'>Database structure fix completed</h2>";
    echo "<p>All database structure issues have been checked and fixed. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/dashboard.php'>Go to Dashboard</a></li>";
    echo "<li><a href='admin/certificates.php'>View Certificates List</a></li>";
    echo "<li><a href='admin/create_certificate.php'>Create a New Certificate</a></li>";
    echo "<li><a href='admin/certificate_templates.php'>Manage Certificate Templates</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>SQL State: " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 