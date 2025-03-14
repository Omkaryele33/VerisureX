<?php
// Set error reporting to maximum
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include security constants
require_once 'security_header.php';

echo "<h1>Certificate System Debug</h1>";

// Display PHP version and extensions
echo "<h2>PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Loaded Extensions:<br>";
$extensions = get_loaded_extensions();
echo "<ul>";
foreach ($extensions as $ext) {
    echo "<li>$ext</li>";
}
echo "</ul>";

// Test includes one by one
echo "<h2>Testing Includes</h2>";

try {
    echo "Testing temp_fix.php... ";
    include_once 'temp_fix.php';
    echo "<span style='color:green'>Success</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>Failed: " . $e->getMessage() . "</span><br>";
}

try {
    echo "Testing ../config/config.php... ";
    include_once '../config/config.php';
    echo "<span style='color:green'>Success</span><br>";
    
    echo "<h3>Defined Constants</h3>";
    echo "<pre>";
    $constants = get_defined_constants(true);
    if (isset($constants['user'])) {
        foreach ($constants['user'] as $name => $value) {
            echo "$name: ";
            if (is_array($value)) {
                print_r($value);
            } else {
                echo var_export($value, true);
            }
            echo "\n";
        }
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<span style='color:red'>Failed: " . $e->getMessage() . "</span><br>";
}

try {
    echo "Testing ../config/database.php... ";
    include_once '../config/database.php';
    echo "<span style='color:green'>Success</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>Failed: " . $e->getMessage() . "</span><br>";
}

try {
    echo "Testing ../includes/functions.php... ";
    include_once '../includes/functions.php';
    echo "<span style='color:green'>Success</span><br>";
    
    // Check for specific functions
    echo "<h3>Checking Key Functions</h3>";
    $functions = ['isLoggedIn', 'redirect', 'sanitizeInput', 'setFlashMessage', 'getFlashMessage'];
    foreach ($functions as $func) {
        echo "Function $func: " . (function_exists($func) ? "<span style='color:green'>Exists</span>" : "<span style='color:red'>Missing</span>") . "<br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color:red'>Failed: " . $e->getMessage() . "</span><br>";
}

try {
    echo "Testing ../includes/security.php... ";
    include_once '../includes/security.php';
    echo "<span style='color:green'>Success</span><br>";
    
    // Check for specific functions
    echo "<h3>Checking Security Functions</h3>";
    $functions = ['generateCSRFToken', 'validateCSRFToken', 'validatePassword', 'isEnhancedRateLimited'];
    foreach ($functions as $func) {
        echo "Function $func: " . (function_exists($func) ? "<span style='color:green'>Exists</span>" : "<span style='color:red'>Missing</span>") . "<br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color:red'>Failed: " . $e->getMessage() . "</span><br>";
}

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<span style='color:green'>Database connection successful</span><br>";
        
        // Test a simple query
        $stmt = $db->query("SELECT 1");
        if ($stmt) {
            echo "Simple query test: <span style='color:green'>Success</span><br>";
        } else {
            echo "Simple query test: <span style='color:red'>Failed</span><br>";
        }
        
        // Test certificate table
        echo "<h3>Testing Certificate Table</h3>";
        try {
            $query = "DESCRIBE certificates";
            $stmt = $db->query($query);
            if ($stmt) {
                echo "<table border='1'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<span style='color:red'>Failed to describe certificates table</span><br>";
            }
        } catch (Exception $e) {
            echo "<span style='color:red'>Error describing table: " . $e->getMessage() . "</span><br>";
        }
        
    } else {
        echo "<span style='color:red'>Database connection failed</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>Database error: " . $e->getMessage() . "</span><br>";
}

// Check for include/path/filesystem errors
echo "<h2>Filesystem Check</h2>";
echo "Current directory: " . getcwd() . "<br>";
echo "Admin directory structure:<br>";
echo "<pre>";
print_r(scandir('.'));
echo "</pre>";

echo "Include paths:<br>";
echo "<pre>";
print_r(get_include_path());
echo "</pre>";

// Check if needed directories exist
echo "<h2>Critical Directories Check</h2>";
$directories = [
    '../uploads',
    '../uploads/qrcodes',
    '../logs',
    '../secure_config'
];

foreach ($directories as $dir) {
    echo "Directory $dir: " . (file_exists($dir) ? "<span style='color:green'>Exists</span>" : "<span style='color:red'>Missing</span>") . "<br>";
}

// Print last errors
echo "<h2>Last PHP Errors</h2>";
echo "<pre>";
print_r(error_get_last());
echo "</pre>";
?>
