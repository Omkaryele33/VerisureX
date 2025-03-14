<?php
// Force error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Try including required files one by one
try {
    echo "Starting tests...<br>";
    
    echo "Including config.php: ";
    include_once '../config/config.php';
    echo "OK<br>";
    
    echo "Including database.php: ";
    include_once '../config/database.php';
    echo "OK<br>";
    
    echo "Including functions.php: ";
    include_once '../includes/functions.php';
    echo "OK<br>";
    
    echo "Including security.php: ";
    include_once '../includes/security.php';
    echo "OK<br>";
    
    echo "Testing database connection: ";
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "Success<br>";
    } else {
        echo "Failed<br>";
    }
    
    echo "Checking isLoggedIn function: ";
    if (function_exists('isLoggedIn')) {
        echo "Exists<br>";
    } else {
        echo "Missing<br>";
    }
    
    echo "<hr>All tests completed without fatal errors.";
} catch (Exception $e) {
    echo "<br><strong>Error caught:</strong> " . $e->getMessage();
}
?>
