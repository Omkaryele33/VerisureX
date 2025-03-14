<?php
// Set error reporting to maximum
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Connection Test</h1>";

// First test with current configuration
echo "<h2>Testing with current configuration</h2>";
echo "DB_HOST: localhost<br>";
echo "DB_NAME: certificate_system<br>";
echo "DB_USER: certificate_user<br>";
echo "DB_PASS: [hidden for security]<br><br>";

try {
    $conn1 = new PDO("mysql:host=localhost;dbname=certificate_system", "certificate_user", "StrongPassword123!");
    $conn1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color:green; font-weight:bold;'>SUCCESS: Connected with current configuration</div><br>";
} catch(PDOException $e) {
    echo "<div style='color:red; font-weight:bold;'>ERROR: " . $e->getMessage() . "</div><br>";
    
    // Now try with the default XAMPP credentials
    echo "<h2>Testing with default XAMPP credentials</h2>";
    echo "DB_HOST: localhost<br>";
    echo "DB_NAME: certificate_system<br>";
    echo "DB_USER: root<br>";
    echo "DB_PASS: [empty]<br><br>";
    
    try {
        $conn2 = new PDO("mysql:host=localhost;dbname=certificate_system", "root", "");
        $conn2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div style='color:green; font-weight:bold;'>SUCCESS: Connected with default XAMPP credentials</div><br>";
        
        // Check if database exists
        $stmt = $conn2->query("SHOW DATABASES LIKE 'certificate_system'");
        if ($stmt->rowCount() > 0) {
            echo "Database 'certificate_system' exists<br>";
        } else {
            echo "Database 'certificate_system' does not exist. Checking if it might have a different name:<br>";
            
            // List all databases
            $stmt = $conn2->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<ul>";
            foreach ($databases as $db) {
                echo "<li>$db</li>";
            }
            echo "</ul>";
        }
        
    } catch(PDOException $e) {
        echo "<div style='color:red; font-weight:bold;'>ERROR: " . $e->getMessage() . "</div><br>";
        
        // Try connecting without a specific database
        echo "<h2>Testing base connection (without database)</h2>";
        try {
            $conn3 = new PDO("mysql:host=localhost", "root", "");
            $conn3->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div style='color:green; font-weight:bold;'>SUCCESS: Connected to MySQL server</div><br>";
            
            // List all databases
            $stmt = $conn3->query("SHOW DATABASES");
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "Available databases:<br><ul>";
            foreach ($databases as $db) {
                echo "<li>$db</li>";
            }
            echo "</ul>";
            
        } catch(PDOException $e) {
            echo "<div style='color:red; font-weight:bold;'>ERROR: Cannot connect to MySQL server: " . $e->getMessage() . "</div><br>";
        }
    }
}

// Show MySQL server info
echo "<h2>MySQL Server Information</h2>";
echo "PHP PDO Drivers available: ";
echo "<ul>";
foreach(PDO::getAvailableDrivers() as $driver) {
    echo "<li>$driver</li>";
}
echo "</ul>";

if (function_exists('mysqli_get_client_info')) {
    echo "MySQLi Client Info: " . mysqli_get_client_info() . "<br>";
}

// Check phpinfo for database section
echo "<h2>PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Show server information
echo "<h2>Server Information</h2>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Check if XAMPP is running
echo "<h2>XAMPP Status Check</h2>";
echo "Attempting to check XAMPP services status...<br>";

// Try to ping MySQL
$mysqlRunning = @fsockopen('localhost', 3306, $errno, $errstr, 1);
echo "MySQL Service: " . ($mysqlRunning ? "<span style='color:green'>Running</span>" : "<span style='color:red'>Not running</span>") . "<br>";
if ($mysqlRunning) fclose($mysqlRunning);

// Check MySQL error log if accessible
$mysqlLogPath = "C:/xampp/mysql/data/mysql_error.log";
if (file_exists($mysqlLogPath) && is_readable($mysqlLogPath)) {
    echo "<h2>Recent MySQL Error Log</h2>";
    echo "<pre>" . htmlspecialchars(shell_exec("type $mysqlLogPath | findstr /C:\"Error\" /C:\"ERROR\" | tail -n 20")) . "</pre>";
}

// Provide recommended fix
echo "<h2>Recommended Fix</h2>";
echo "Based on the tests above, you should update the db_config.php file with the correct credentials.<br>";
echo "Create a form below to automatically update the configuration:<br>";
?>

<form method="post" action="fix_db_config.php">
    <div style="margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; max-width: 500px;">
        <h3>Update Database Configuration</h3>
        <div style="margin-bottom: 10px;">
            <label for="db_host" style="display: block; margin-bottom: 5px;">Database Host:</label>
            <input type="text" id="db_host" name="db_host" value="localhost" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 10px;">
            <label for="db_name" style="display: block; margin-bottom: 5px;">Database Name:</label>
            <input type="text" id="db_name" name="db_name" value="certificate_system" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 10px;">
            <label for="db_user" style="display: block; margin-bottom: 5px;">Database User:</label>
            <input type="text" id="db_user" name="db_user" value="root" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-bottom: 10px;">
            <label for="db_pass" style="display: block; margin-bottom: 5px;">Database Password:</label>
            <input type="password" id="db_pass" name="db_pass" value="" style="width: 100%; padding: 8px;">
        </div>
        <div style="margin-top: 15px;">
            <button type="submit" style="background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">Update Configuration</button>
        </div>
    </div>
</form>
