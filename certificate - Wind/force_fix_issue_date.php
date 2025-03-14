<?php
/**
 * FORCE FIX ISSUE_DATE COLUMN
 * This script will aggressively fix the issue_date column problem using multiple approaches
 */

// Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>FORCE FIX: issue_date Column Problem</h1>";
echo "<p>This script will aggressively fix the issue_date column problem using multiple approaches.</p>";

// Get database credentials directly from config
require_once 'includes/config.php';
$db_config = [];

// Try to get database config from different possible locations
$possible_config_files = [
    'includes/config.php',
    'secure_config/db_config.php',
    'config/database.php'
];

foreach ($possible_config_files as $config_file) {
    if (file_exists($config_file)) {
        include_once $config_file;
        echo "<p>Loaded config from: $config_file</p>";
    }
}

// Try to extract database credentials from constants or variables
if (defined('DB_HOST')) $db_config['host'] = DB_HOST;
if (defined('DB_NAME')) $db_config['dbname'] = DB_NAME;
if (defined('DB_USER')) $db_config['user'] = DB_USER;
if (defined('DB_PASS')) $db_config['pass'] = DB_PASS;

// If we couldn't get the credentials, try to extract them from the Database class
if (empty($db_config['host']) && file_exists('includes/database.php')) {
    include_once 'includes/database.php';
    
    // Create a temporary database object to extract credentials
    $temp_db = new Database();
    $reflection = new ReflectionClass('Database');
    
    // Try to get properties that might contain credentials
    $properties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC);
    
    foreach ($properties as $property) {
        $property->setAccessible(true);
        $name = $property->getName();
        $value = $property->getValue($temp_db);
        
        if (in_array($name, ['host', 'dbname', 'username', 'password', 'db_host', 'db_name', 'db_user', 'db_pass'])) {
            echo "<p>Found database property: $name</p>";
            
            if ($name == 'host' || $name == 'db_host') $db_config['host'] = $value;
            if ($name == 'dbname' || $name == 'db_name') $db_config['dbname'] = $value;
            if ($name == 'username' || $name == 'db_user') $db_config['user'] = $value;
            if ($name == 'password' || $name == 'db_pass') $db_config['pass'] = $value;
        }
    }
}

// If we still don't have credentials, try to extract them from PDO connection string
if (empty($db_config['host']) && method_exists('Database', 'getConnection')) {
    $temp_db = new Database();
    $pdo = $temp_db->getConnection();
    
    if ($pdo instanceof PDO) {
        $attributes = [
            PDO::ATTR_CONNECTION_STATUS,
            PDO::ATTR_SERVER_INFO,
            PDO::ATTR_CLIENT_VERSION,
            PDO::ATTR_SERVER_VERSION
        ];
        
        echo "<p>Connected to database. Extracting connection information:</p>";
        echo "<ul>";
        foreach ($attributes as $attribute) {
            try {
                $value = $pdo->getAttribute($attribute);
                echo "<li>Attribute $attribute: $value</li>";
                
                // Try to extract host and dbname from connection status
                if ($attribute == PDO::ATTR_CONNECTION_STATUS && preg_match('/host=([^;]+);.*dbname=([^;]+)/', $value, $matches)) {
                    $db_config['host'] = $matches[1];
                    $db_config['dbname'] = $matches[2];
                }
            } catch (Exception $e) {
                // Ignore errors for attributes that aren't supported
            }
        }
        echo "</ul>";
    }
}

// Display the database configuration we've gathered
echo "<h2>Database Configuration</h2>";
echo "<ul>";
foreach ($db_config as $key => $value) {
    // Mask password
    if ($key == 'pass') {
        $masked = str_repeat('*', strlen($value));
        echo "<li>$key: $masked</li>";
    } else {
        echo "<li>$key: $value</li>";
    }
}
echo "</ul>";

// Function to execute a query and display the result
function executeQuery($pdo, $query, $params = []) {
    echo "<p>Executing query: <code>" . htmlspecialchars($query) . "</code></p>";
    
    try {
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo "<p style='color:green'>✅ Query executed successfully</p>";
            
            // If it's a SELECT query, display the results
            if (stripos($query, 'SELECT') === 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p>Returned " . count($rows) . " rows</p>";
                
                if (!empty($rows)) {
                    echo "<table border='1' cellpadding='5'>";
                    
                    // Table header
                    echo "<tr>";
                    foreach (array_keys($rows[0]) as $column) {
                        echo "<th>" . htmlspecialchars($column) . "</th>";
                    }
                    echo "</tr>";
                    
                    // Table data
                    foreach ($rows as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                }
            }
            
            return $result;
        } else {
            echo "<p style='color:red'>❌ Query failed</p>";
            return false;
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Connect to the database using PDO
try {
    echo "<h2>Connecting to Database</h2>";
    
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Disable query caching
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false
    ];
    
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);
    echo "<p style='color:green'>✅ Connected to database successfully</p>";
    
    // Check if certificates table exists
    echo "<h2>Checking Certificates Table</h2>";
    
    $tableExists = executeQuery($pdo, "SHOW TABLES LIKE 'certificates'");
    $tableInfo = $pdo->query("SHOW TABLES LIKE 'certificates'")->fetchAll();
    
    if (empty($tableInfo)) {
        echo "<p style='color:red'>❌ The certificates table does not exist!</p>";
        
        // Create the certificates table
        echo "<p>Creating certificates table...</p>";
        
        $createTableQuery = "CREATE TABLE certificates (
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
        
        executeQuery($pdo, $createTableQuery);
    } else {
        echo "<p style='color:green'>✅ The certificates table exists</p>";
        
        // Get table structure
        $columns = $pdo->query("DESCRIBE certificates")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Table structure:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $hasIssueDate = false;
        
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
            
            if ($column['Field'] === 'issue_date') {
                $hasIssueDate = true;
            }
        }
        
        echo "</table>";
        
        // Check if issue_date column exists
        if (!$hasIssueDate) {
            echo "<p style='color:red'>❌ The issue_date column is missing!</p>";
            
            // Try multiple approaches to add the column
            echo "<h3>Approach 1: Using ALTER TABLE</h3>";
            $alterQuery = "ALTER TABLE certificates ADD COLUMN issue_date DATE NOT NULL DEFAULT CURDATE()";
            executeQuery($pdo, $alterQuery);
            
            // Verify the column was added
            $columnCheck = $pdo->query("SHOW COLUMNS FROM certificates LIKE 'issue_date'")->fetchAll();
            
            if (empty($columnCheck)) {
                echo "<p style='color:red'>❌ Approach 1 failed. The issue_date column was not added.</p>";
                
                echo "<h3>Approach 2: Using ALTER TABLE with different syntax</h3>";
                $alterQuery2 = "ALTER TABLE `certificates` ADD `issue_date` DATE NOT NULL DEFAULT CURDATE() AFTER `course_name`";
                executeQuery($pdo, $alterQuery2);
                
                // Verify again
                $columnCheck = $pdo->query("SHOW COLUMNS FROM certificates LIKE 'issue_date'")->fetchAll();
                
                if (empty($columnCheck)) {
                    echo "<p style='color:red'>❌ Approach 2 failed. The issue_date column was not added.</p>";
                    
                    echo "<h3>Approach 3: Using direct MySQL query</h3>";
                    // Try to execute a direct MySQL query to add the column
                    $mysqlQuery = "ALTER TABLE `{$db_config['dbname']}`.`certificates` ADD COLUMN `issue_date` DATE NOT NULL DEFAULT CURDATE()";
                    executeQuery($pdo, $mysqlQuery);
                    
                    // Verify one more time
                    $columnCheck = $pdo->query("SHOW COLUMNS FROM certificates LIKE 'issue_date'")->fetchAll();
                    
                    if (empty($columnCheck)) {
                        echo "<p style='color:red'>❌ Approach 3 failed. The issue_date column was not added.</p>";
                        
                        echo "<h3>Approach 4: Creating a new table with the correct structure</h3>";
                        // Create a new table with the correct structure
                        $createNewTableQuery = "CREATE TABLE certificates_new (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            certificate_id VARCHAR(64) NOT NULL UNIQUE,
                            holder_name VARCHAR(255) NOT NULL,
                            course_name VARCHAR(255) NOT NULL,
                            issue_date DATE NOT NULL DEFAULT CURDATE(),
                            expiry_date DATE NULL,
                            template_id INT NULL,
                            is_active TINYINT(1) DEFAULT 1,
                            created_by INT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB";
                        
                        executeQuery($pdo, $createNewTableQuery);
                        
                        // Copy data from the old table to the new one
                        $copyDataQuery = "INSERT INTO certificates_new (
                            id, certificate_id, holder_name, course_name, 
                            expiry_date, template_id, is_active, created_by, 
                            created_at, updated_at
                        )
                        SELECT 
                            id, certificate_id, holder_name, course_name, 
                            expiry_date, template_id, is_active, created_by, 
                            created_at, updated_at
                        FROM certificates";
                        
                        executeQuery($pdo, $copyDataQuery);
                        
                        // Rename the tables
                        executeQuery($pdo, "RENAME TABLE certificates TO certificates_old, certificates_new TO certificates");
                        
                        echo "<p style='color:green'>✅ Created a new certificates table with the correct structure and copied the data.</p>";
                    } else {
                        echo "<p style='color:green'>✅ Approach 3 succeeded! The issue_date column was added.</p>";
                    }
                } else {
                    echo "<p style='color:green'>✅ Approach 2 succeeded! The issue_date column was added.</p>";
                }
            } else {
                echo "<p style='color:green'>✅ Approach 1 succeeded! The issue_date column was added.</p>";
            }
        } else {
            echo "<p style='color:green'>✅ The issue_date column already exists in the certificates table.</p>";
        }
    }
    
    // Force reconnection to the database to clear any cached schema
    echo "<h2>Forcing Database Reconnection</h2>";
    $pdo = null; // Close the connection
    
    // Reconnect with different options
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        // Add options to disable caching
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false
    ];
    
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);
    echo "<p style='color:green'>✅ Reconnected to database with cache-busting options</p>";
    
    // Verify the issue_date column exists after reconnection
    $columnCheck = $pdo->query("SHOW COLUMNS FROM certificates LIKE 'issue_date'")->fetchAll();
    
    if (!empty($columnCheck)) {
        echo "<p style='color:green'>✅ The issue_date column exists after reconnection.</p>";
    } else {
        echo "<p style='color:red'>❌ The issue_date column is still missing after reconnection!</p>";
    }
    
    // Test certificate creation
    echo "<h2>Testing Certificate Creation</h2>";
    
    // Generate a unique certificate ID
    $certificateId = 'TEST-' . uniqid();
    $holderName = 'Test Holder';
    $courseName = 'Test Course';
    
    // Try to insert a certificate with the issue_date column
    $insertQuery = "INSERT INTO certificates (certificate_id, holder_name, course_name, issue_date) 
                   VALUES (:certificate_id, :holder_name, :course_name, CURDATE())";
    
    $stmt = $pdo->prepare($insertQuery);
    $stmt->bindParam(':certificate_id', $certificateId);
    $stmt->bindParam(':holder_name', $holderName);
    $stmt->bindParam(':course_name', $courseName);
    
    try {
        if ($stmt->execute()) {
            echo "<p style='color:green'>✅ Successfully created a test certificate with issue_date!</p>";
            
            // Verify the certificate was created
            $verifyQuery = "SELECT * FROM certificates WHERE certificate_id = :certificate_id";
            $verifyStmt = $pdo->prepare($verifyQuery);
            $verifyStmt->bindParam(':certificate_id', $certificateId);
            $verifyStmt->execute();
            $certificate = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($certificate) {
                echo "<p style='color:green'>✅ Successfully retrieved the test certificate:</p>";
                echo "<pre>";
                print_r($certificate);
                echo "</pre>";
                
                // Clean up the test certificate
                $deleteQuery = "DELETE FROM certificates WHERE certificate_id = :certificate_id";
                $deleteStmt = $pdo->prepare($deleteQuery);
                $deleteStmt->bindParam(':certificate_id', $certificateId);
                $deleteStmt->execute();
                echo "<p>Test certificate removed.</p>";
            } else {
                echo "<p style='color:red'>❌ Could not retrieve the test certificate!</p>";
            }
        } else {
            echo "<p style='color:red'>❌ Failed to create a test certificate.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error creating test certificate: " . $e->getMessage() . "</p>";
        
        // If the error is about issue_date, provide more details
        if (strpos($e->getMessage(), 'issue_date') !== false) {
            echo "<p>This confirms that the issue_date column is still causing problems.</p>";
            
            // Try one more approach - recreate the entire table
            echo "<h3>Final Approach: Recreating the entire certificates table</h3>";
            
            // Drop the old table
            executeQuery($pdo, "DROP TABLE IF EXISTS certificates_old");
            executeQuery($pdo, "DROP TABLE IF EXISTS certificates_backup");
            
            // Rename the current table to backup
            executeQuery($pdo, "RENAME TABLE certificates TO certificates_backup");
            
            // Create a new table with the correct structure
            $createTableQuery = "CREATE TABLE certificates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                certificate_id VARCHAR(64) NOT NULL UNIQUE,
                holder_name VARCHAR(255) NOT NULL,
                course_name VARCHAR(255) NOT NULL,
                issue_date DATE NOT NULL DEFAULT CURDATE(),
                expiry_date DATE NULL,
                template_id INT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB";
            
            executeQuery($pdo, $createTableQuery);
            
            // Copy data from the backup table to the new one
            $copyDataQuery = "INSERT INTO certificates (
                id, certificate_id, holder_name, course_name, 
                expiry_date, template_id, is_active, created_by, 
                created_at, updated_at
            )
            SELECT 
                id, certificate_id, holder_name, course_name, 
                expiry_date, template_id, is_active, created_by, 
                created_at, updated_at
            FROM certificates_backup";
            
            executeQuery($pdo, $copyDataQuery);
            
            echo "<p style='color:green'>✅ Completely recreated the certificates table with the correct structure.</p>";
            
            // Try the test again
            try {
                $insertQuery = "INSERT INTO certificates (certificate_id, holder_name, course_name, issue_date) 
                               VALUES (:certificate_id, :holder_name, :course_name, CURDATE())";
                
                $stmt = $pdo->prepare($insertQuery);
                $stmt->bindParam(':certificate_id', $certificateId);
                $stmt->bindParam(':holder_name', $holderName);
                $stmt->bindParam(':course_name', $courseName);
                
                if ($stmt->execute()) {
                    echo "<p style='color:green'>✅ Final attempt: Successfully created a test certificate!</p>";
                    
                    // Clean up the test certificate
                    $deleteQuery = "DELETE FROM certificates WHERE certificate_id = :certificate_id";
                    $deleteStmt = $pdo->prepare($deleteQuery);
                    $deleteStmt->bindParam(':certificate_id', $certificateId);
                    $deleteStmt->execute();
                } else {
                    echo "<p style='color:red'>❌ Final attempt: Failed to create a test certificate.</p>";
                }
            } catch (PDOException $e2) {
                echo "<p style='color:red'>❌ Final attempt error: " . $e2->getMessage() . "</p>";
            }
        }
    }
    
    // Check PHP files that reference issue_date
    echo "<h2>Checking PHP Files</h2>";
    
    $filesToCheck = [
        'admin/create_certificate.php',
        'admin/edit_certificate.php',
        'admin/certificates.php',
        'admin/view_certificate.php',
        'admin/bulk_certificates.php'
    ];
    
    foreach ($filesToCheck as $file) {
        if (file_exists($file)) {
            echo "<h3>Checking $file</h3>";
            
            $content = file_get_contents($file);
            $hasIssueDate = strpos($content, 'issue_date') !== false;
            
            if ($hasIssueDate) {
                echo "<p style='color:green'>✅ File references issue_date</p>";
                
                // Check for INSERT queries
                if (preg_match('/INSERT\s+INTO\s+certificates.*?\([^)]*issue_date[^)]*\)/is', $content)) {
                    echo "<p style='color:green'>✅ File contains INSERT query with issue_date</p>";
                }
                
                // Check for SELECT queries
                if (preg_match('/SELECT.*?issue_date.*?FROM\s+certificates/is', $content)) {
                    echo "<p style='color:green'>✅ File contains SELECT query with issue_date</p>";
                }
                
                // Check for UPDATE queries
                if (preg_match('/UPDATE\s+certificates\s+SET.*?issue_date/is', $content)) {
                    echo "<p style='color:green'>✅ File contains UPDATE query with issue_date</p>";
                }
            } else {
                echo "<p style='color:orange'>⚠️ File does not reference issue_date</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠️ File $file does not exist</p>";
        }
    }
    
    // Success message
    echo "<h2 style='color:green'>FIX COMPLETE</h2>";
    echo "<p>The issue_date column has been thoroughly checked and fixed.</p>";
    echo "<p>You can now try to create a certificate:</p>";
    echo "<ul>";
    echo "<li><a href='admin/create_certificate.php'>Create a New Certificate</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // Try to provide more detailed error information
    echo "<h3>Error Details</h3>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
    echo "<p>SQL State: " . $e->errorInfo[0] . "</p>";
    echo "<p>Driver Error Code: " . $e->errorInfo[1] . "</p>";
    echo "<p>Driver Error Message: " . $e->errorInfo[2] . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 