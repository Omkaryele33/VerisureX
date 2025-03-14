<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostic Information</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test session
require_once __DIR__ . "/../includes/session.php";
echo "<h2>Session Test</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once '../config/config.php';
    echo "Config file loaded successfully.<br>";
    
    require_once '../config/database.php';
    echo "Database config loaded successfully.<br>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "Database connection successful.<br>";
        
        // Test a simple query
        $stmt = $db->query("SELECT 1");
        if ($stmt) {
            echo "Test query executed successfully.<br>";
        } else {
            echo "Test query failed.<br>";
        }
    } else {
        echo "Database connection failed.<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Include file test
echo "<h2>Include Files Test</h2>";
$files = [
    '../includes/functions.php',
    '../includes/security.php',
    'includes/header.php',
    'includes/sidebar.php'
];

foreach ($files as $file) {
    echo "Testing include for: $file... ";
    if (file_exists($file)) {
        echo "File exists. ";
        try {
            include_once $file;
            echo "Included successfully.<br>";
        } catch (Exception $e) {
            echo "Error including file: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "File does not exist!<br>";
    }
}

// Function test
echo "<h2>Function Test</h2>";
$functions = [
    'isLoggedIn',
    'redirect',
    'sanitizeInput',
    'generateCSRFToken',
    'validateCSRFToken'
];

foreach ($functions as $function) {
    echo "Testing function: $function... ";
    if (function_exists($function)) {
        echo "Function exists.<br>";
    } else {
        echo "Function does not exist!<br>";
    }
}

// Security file test
echo "<h2>Security Module Test</h2>";
if (file_exists('../includes/security.php')) {
    echo "Security file exists.<br>";
    
    if (function_exists('generateCSRFToken')) {
        try {
            $token = generateCSRFToken();
            echo "CSRF token generated: " . htmlspecialchars($token) . "<br>";
        } catch (Exception $e) {
            echo "Error generating CSRF token: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "generateCSRFToken function not found!<br>";
    }
} else {
    echo "Security file does not exist!<br>";
}
?>
