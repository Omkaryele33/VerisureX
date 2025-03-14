<?php
// A simpler debug file that just tests the bare minimum

// Set error reporting to maximum
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Simple Debug Check</h1>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection directly without any includes
echo "<h2>Testing Database Connection Directly</h2>";
try {
    $host = "localhost";
    $db_name = "certificate_db";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Direct database connection: <span style='color:green'>Success</span><br>";
    
    // Check if the certificates table exists
    $query = "SHOW TABLES LIKE 'certificates'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Certificates table: <span style='color:green'>Exists</span><br>";
    } else {
        echo "Certificates table: <span style='color:red'>Missing</span><br>";
    }
    
} catch(PDOException $e) {
    echo "Connection error: <span style='color:red'>" . $e->getMessage() . "</span><br>";
}

// Check if the temp_fix.php file exists and can be included
echo "<h2>Testing temp_fix.php inclusion</h2>";
if (file_exists('temp_fix.php')) {
    echo "temp_fix.php: <span style='color:green'>Exists</span><br>";
    try {
        include_once 'temp_fix.php';
        echo "Including temp_fix.php: <span style='color:green'>Success</span><br>";
    } catch (Exception $e) {
        echo "Error including temp_fix.php: <span style='color:red'>" . $e->getMessage() . "</span><br>";
    }
} else {
    echo "temp_fix.php: <span style='color:red'>Missing</span><br>";
}

// Check if functions.php exists and what functions it defines
echo "<h2>Checking functions.php</h2>";
if (file_exists('../includes/functions.php')) {
    echo "functions.php: <span style='color:green'>Exists</span><br>";
    try {
        include_once '../includes/functions.php';
        echo "Including functions.php: <span style='color:green'>Success</span><br>";
        
        // Check if isLoggedIn function exists
        if (function_exists('isLoggedIn')) {
            echo "isLoggedIn function: <span style='color:green'>Exists</span><br>";
        } else {
            echo "isLoggedIn function: <span style='color:red'>Missing</span><br>";
        }
        
    } catch (Exception $e) {
        echo "Error including functions.php: <span style='color:red'>" . $e->getMessage() . "</span><br>";
    }
} else {
    echo "functions.php: <span style='color:red'>Missing</span><br>";
}

// Check if security.php exists
echo "<h2>Checking security.php</h2>";
if (file_exists('../includes/security.php')) {
    echo "security.php: <span style='color:green'>Exists</span><br>";
    try {
        include_once '../includes/security.php';
        echo "Including security.php: <span style='color:green'>Success</span><br>";
    } catch (Exception $e) {
        echo "Error including security.php: <span style='color:red'>" . $e->getMessage() . "</span><br>";
    }
} else {
    echo "security.php: <span style='color:red'>Missing</span><br>";
}

// Print last errors
echo "<h2>Last PHP Error</h2>";
echo "<pre>";
print_r(error_get_last());
echo "</pre>";
?>
