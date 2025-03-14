<?php
/**
 * Database Configuration Fix Script
 * This script updates the database configuration file with the correct credentials
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the path to the database configuration file
$configFile = __DIR__ . '/secure_config/db_config.php';

// Function to display messages
function showMessage($message, $type = 'info') {
    $color = 'blue';
    if ($type === 'success') $color = 'green';
    if ($type === 'error') $color = 'red';
    
    echo "<div style='color:$color; margin:10px 0; padding:10px; border:1px solid $color;'>$message</div>";
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $dbHost = isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost';
    $dbName = isset($_POST['db_name']) ? trim($_POST['db_name']) : 'certificate_system';
    $dbUser = isset($_POST['db_user']) ? trim($_POST['db_user']) : 'root';
    $dbPass = isset($_POST['db_pass']) ? $_POST['db_pass'] : '';
    
    // Validate input
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        showMessage('Database host, name, and user cannot be empty.', 'error');
        exit;
    }
    
    // Test the connection with the provided credentials
    try {
        $dsn = "mysql:host=$dbHost;";
        if (!empty($dbName)) {
            $dsn .= "dbname=$dbName;";
        }
        
        $conn = new PDO($dsn, $dbUser, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        showMessage("Successfully connected to the database with the provided credentials.", 'success');
        
        // Check if database exists, if not try to create it
        if ($dbName && $conn) {
            $stmt = $conn->query("SHOW DATABASES LIKE '$dbName'");
            if ($stmt->rowCount() === 0) {
                // Database doesn't exist, try to create it
                $conn->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                showMessage("Created database '$dbName'.", 'success');
            }
        }
        
        // Backup the original config file
        $backupFile = $configFile . '.bak.' . date('YmdHis');
        if (file_exists($configFile)) {
            if (!copy($configFile, $backupFile)) {
                showMessage("Warning: Failed to create backup of the original configuration file.", 'error');
            } else {
                showMessage("Created backup of the original configuration file at $backupFile", 'success');
            }
        }
        
        // Create the new configuration content
        $configContent = "<?php
/**
 * Secure Database Credentials
 * This file should be placed outside the web root in production
 */

// Database settings
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');

// Additional security settings
define('HASH_SECRET', '8b7df143d91c716ecfa5fc1730022f6b'); // Random secret for hashing
define('ENCRYPTION_KEY', '4d6783e91af4e3dfa5fc1720022f6ab'); // For encrypting sensitive data
?>";
        
        // Ensure the directory exists
        $configDir = dirname($configFile);
        if (!file_exists($configDir)) {
            mkdir($configDir, 0755, true);
            showMessage("Created directory $configDir", 'success');
        }
        
        // Write the new configuration file
        if (file_put_contents($configFile, $configContent) === false) {
            showMessage("Failed to write the new configuration file. Please check file permissions.", 'error');
        } else {
            showMessage("Successfully updated the database configuration file.", 'success');
        }
        
        // Check database tables
        echo "<h2>Checking Database Tables</h2>";
        $conn = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tables = array(
            'admins',
            'certificates',
            'certificate_templates',
            'certificate_verifications',
            'rate_limits',
            'api_keys'
        );
        
        $existingTables = array();
        $missingTables = array();
        
        $stmt = $conn->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Tables in database:</p><ul>";
        if (count($allTables) > 0) {
            foreach ($allTables as $table) {
                echo "<li>$table</li>";
                $existingTables[] = $table;
            }
        } else {
            echo "<li>No tables found</li>";
        }
        echo "</ul>";
        
        foreach ($tables as $table) {
            if (!in_array($table, $existingTables)) {
                $missingTables[] = $table;
            }
        }
        
        if (count($missingTables) > 0) {
            showMessage("Missing tables: " . implode(", ", $missingTables) . ". You may need to import the database schema.", 'error');
        } else {
            showMessage("All required tables exist.", 'success');
        }
        
    } catch (PDOException $e) {
        showMessage("Database connection error: " . $e->getMessage(), 'error');
    }
    
    // Create a button to go back to the admin page
    echo "<div style='margin-top:20px;'>";
    echo "<a href='admin/index.php' style='background-color:#4CAF50; color:white; padding:10px 15px; text-decoration:none; display:inline-block; border-radius:4px;'>Go to Admin Panel</a>";
    echo "</div>";
    
} else {
    // Redirect back to the test page if accessed directly
    header("Location: db_test.php");
    exit;
}
?>
