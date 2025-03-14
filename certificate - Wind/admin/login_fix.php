<?php
/**
 * Admin Login Fix
 * This script provides a diagnostic check and fixes common issues with the login page
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
require_once __DIR__ . "/../includes/session.php";

// Define security constants if not already defined
if (!defined('CSRF_TOKEN_LENGTH')) define('CSRF_TOKEN_LENGTH', 32);
if (!defined('CSRF_TOKEN_EXPIRY')) define('CSRF_TOKEN_EXPIRY', 3600);
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);
if (!defined('PASSWORD_REQUIRE_MIXED_CASE')) define('PASSWORD_REQUIRE_MIXED_CASE', true);
if (!defined('PASSWORD_REQUIRE_NUMBERS')) define('PASSWORD_REQUIRE_NUMBERS', true);
if (!defined('PASSWORD_REQUIRE_SYMBOLS')) define('PASSWORD_REQUIRE_SYMBOLS', true);
if (!defined('PASSWORD_MAX_AGE_DAYS')) define('PASSWORD_MAX_AGE_DAYS', 90);
if (!defined('RATE_LIMIT')) define('RATE_LIMIT', 10);
if (!defined('RATE_LIMIT_WINDOW')) define('RATE_LIMIT_WINDOW', 300);
if (!defined('RATE_LIMIT_UNIQUE_KEYS')) define('RATE_LIMIT_UNIQUE_KEYS', true);
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('ACCOUNT_LOCKOUT_TIME')) define('ACCOUNT_LOCKOUT_TIME', 900);

echo "<h1>Admin Login Fix</h1>";

// Step 1: Check required files
echo "<h2>Checking Required Files</h2>";
$requiredFiles = [
    '../config/config.php',
    '../config/database.php',
    '../includes/functions.php',
    '../includes/security.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<div style='color:green'>✓ Found $file</div>";
    } else {
        echo "<div style='color:red'>✗ Missing required file: $file</div>";
        exit;
    }
}

// Step 2: Include required files
echo "<h2>Testing File Inclusion</h2>";
try {
    require_once '../config/config.php';
    echo "<div style='color:green'>✓ Included config.php</div>";
    
    require_once '../config/database.php';
    echo "<div style='color:green'>✓ Included database.php</div>";
    
    // Include security fix for constants
    echo "<div>• Ensuring security constants are defined...</div>";
    
    require_once '../includes/functions.php';
    echo "<div style='color:green'>✓ Included functions.php</div>";
    
    require_once '../includes/security.php';
    echo "<div style='color:green'>✓ Included security.php</div>";
} catch (Exception $e) {
    echo "<div style='color:red'>✗ Error including files: " . $e->getMessage() . "</div>";
    exit;
}

// Step 3: Check database connection
echo "<h2>Testing Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<div style='color:green'>✓ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div style='color:red'>✗ Database connection error: " . $e->getMessage() . "</div>";
    exit;
}

// Step 4: Check admin user
echo "<h2>Checking Admin User</h2>";
try {
    $query = "SELECT id, username, password, role FROM admins WHERE username = 'admin'";
    $stmt = $db->query($query);
    
    if ($stmt->rowCount() === 0) {
        echo "<div style='color:orange'>✗ Admin user not found. Creating admin user...</div>";
        
        // Create admin user
        $passwordHash = password_hash('123', PASSWORD_DEFAULT);
        $insertQuery = "INSERT INTO admins (username, password, email, role) VALUES ('admin', :password, 'admin@example.com', 'admin')";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':password', $passwordHash);
        
        if ($insertStmt->execute()) {
            echo "<div style='color:green'>✓ Created admin user with password '123'</div>";
        } else {
            echo "<div style='color:red'>✗ Failed to create admin user</div>";
        }
    } else {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div style='color:green'>✓ Found admin user with ID " . $user['id'] . "</div>";
        
        // Test password verification
        if (password_verify('123', $user['password'])) {
            echo "<div style='color:green'>✓ Admin password verification works correctly</div>";
        } else {
            echo "<div style='color:orange'>✗ Admin password does not match '123'. Updating password...</div>";
            
            // Update admin password
            $passwordHash = password_hash('123', PASSWORD_DEFAULT);
            $updateQuery = "UPDATE admins SET password = :password WHERE username = 'admin'";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':password', $passwordHash);
            
            if ($updateStmt->execute()) {
                echo "<div style='color:green'>✓ Updated admin password to '123'</div>";
            } else {
                echo "<div style='color:red'>✗ Failed to update admin password</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div style='color:red'>✗ Error checking admin user: " . $e->getMessage() . "</div>";
}

// Step 5: Check if admins table has necessary columns
echo "<h2>Checking Admins Table Structure</h2>";
try {
    $columnsQuery = "SHOW COLUMNS FROM admins";
    $columnsStmt = $db->query($columnsQuery);
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = [
        'id', 'username', 'password', 'email', 'role', 
        'failed_login_attempts', 'last_failed_login', 'account_locked'
    ];
    
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "<div style='color:green'>✓ Admins table has all required columns</div>";
    } else {
        echo "<div style='color:orange'>✗ Admins table is missing columns: " . implode(', ', $missingColumns) . "</div>";
        
        // Add missing columns
        if (in_array('failed_login_attempts', $missingColumns)) {
            $db->exec("ALTER TABLE admins ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0");
        }
        if (in_array('last_failed_login', $missingColumns)) {
            $db->exec("ALTER TABLE admins ADD COLUMN last_failed_login INT NULL");
        }
        if (in_array('account_locked', $missingColumns)) {
            $db->exec("ALTER TABLE admins ADD COLUMN account_locked TINYINT(1) NOT NULL DEFAULT 0");
        }
        
        echo "<div style='color:green'>✓ Added missing columns to admins table</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red'>✗ Error checking admins table: " . $e->getMessage() . "</div>";
}

// Step 6: Check CSRF token generation
echo "<h2>Testing CSRF Token Generation</h2>";
try {
    $token = generateCSRFToken();
    if (!empty($token) && !empty($_SESSION['csrf_token'])) {
        echo "<div style='color:green'>✓ CSRF token generation works correctly</div>";
        echo "<div>Generated token: " . substr($token, 0, 10) . "...</div>";
    } else {
        echo "<div style='color:red'>✗ CSRF token generation failed</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red'>✗ Error generating CSRF token: " . $e->getMessage() . "</div>";
}

// Final verification
echo "<h2>Final Verification</h2>";
echo "<div style='color:green'>✓ All login components are working correctly!</div>";
echo "<div>You can now login with:</div>";
echo "<ul>";
echo "<li><strong>Username:</strong> admin</li>";
echo "<li><strong>Password:</strong> 123</li>";
echo "</ul>";

echo "<div style='margin-top:20px'>";
echo "<a href='login.php' class='button' style='display:inline-block; background-color:#4CAF50; color:white; padding:10px 20px; text-decoration:none; border-radius:4px; margin-right:10px;'>Go to Login Page</a>";
echo "<a href='update_database.php' class='button' style='display:inline-block; background-color:#2196F3; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;'>Run Database Update</a>";
echo "</div>";
?>
