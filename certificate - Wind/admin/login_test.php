<?php
/**
 * Admin Login Test
 * This file tests the login functionality with security fixes applied
 */

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
require_once __DIR__ . "/../includes/session.php";

// Define security constants if not already defined
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_EXPIRY', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_MIXED_CASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', true);
define('PASSWORD_MAX_AGE_DAYS', 90);
define('RATE_LIMIT', 10);
define('RATE_LIMIT_WINDOW', 300);
define('RATE_LIMIT_UNIQUE_KEYS', true);
define('ENABLE_DIGITAL_SIGNATURES', false);
define('SIGNATURE_KEY_FILE', dirname(__DIR__) . '/secure_config/private_key.pem');
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCKOUT_TIME', 900);

echo "<h1>Admin Login Test</h1>";

// Include configuration files
echo "<h2>Loading Required Files</h2>";
try {
    echo "Loading config.php... ";
    require_once '../config/config.php';
    echo "OK<br>";
    
    echo "Loading database.php... ";
    require_once '../config/database.php';
    echo "OK<br>";
    
    echo "Loading functions.php... ";
    require_once '../includes/functions.php';
    echo "OK<br>";
    
    echo "Loading security.php... ";
    require_once '../includes/security.php';
    echo "OK<br>";
} catch (Exception $e) {
    echo "<div style='color:red'>ERROR: " . $e->getMessage() . "</div>";
    exit;
}

// Connect to database
echo "<h2>Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Successfully connected to database<br>";
    
    // Check if user exists
    echo "<h2>Checking Admin User</h2>";
    $query = "SELECT id, username, password FROM admins WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->execute(['admin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Admin user exists<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Password Hash: " . substr($user['password'], 0, 10) . "...<br>";
        
        echo "<h3>Password Verification</h3>";
        $password = '123';
        if (password_verify($password, $user['password'])) {
            echo "<div style='color:green'>Password '123' is correct</div>";
        } else {
            echo "<div style='color:red'>Password '123' is incorrect</div>";
            
            // Update admin password for testing
            echo "<h3>Updating Admin Password</h3>";
            $passwordHash = password_hash('123', PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE admins SET password = ? WHERE username = ?");
            $updateResult = $updateStmt->execute([$passwordHash, 'admin']);
            
            if ($updateResult) {
                echo "<div style='color:green'>Admin password updated to '123'</div>";
            } else {
                echo "<div style='color:red'>Failed to update admin password</div>";
            }
        }
    } else {
        echo "Admin user does not exist. Creating one...<br>";
        
        $passwordHash = password_hash('123', PASSWORD_DEFAULT);
        $insertStmt = $db->prepare("INSERT INTO admins (username, password, email, role) VALUES (?, ?, ?, ?)");
        $insertResult = $insertStmt->execute(['admin', $passwordHash, 'admin@example.com', 'admin']);
        
        if ($insertResult) {
            echo "<div style='color:green'>Admin user created successfully</div>";
        } else {
            echo "<div style='color:red'>Failed to create admin user</div>";
        }
    }
    
    // Test CSRF functions
    echo "<h2>Testing CSRF Functions</h2>";
    echo "Generating CSRF token... ";
    $token = generateCSRFToken();
    echo "OK<br>";
    echo "Token: " . $token . "<br>";
    echo "Token in Session: " . $_SESSION['csrf_token'] . "<br>";
    
    echo "<h2>Login Form</h2>";
    echo "<form method='post' action='login.php' style='border:1px solid #ccc; padding:20px; max-width:400px'>";
    echo "<div style='margin-bottom:10px'><label>Username: <input type='text' name='username' value='admin'></label></div>";
    echo "<div style='margin-bottom:10px'><label>Password: <input type='password' name='password' value='123'></label></div>";
    echo "<div><input type='hidden' name='csrf_token' value='" . $token . "'></div>";
    echo "<div><button type='submit' style='padding:5px 10px'>Login</button></div>";
    echo "</form>";
    
    echo "<p>Or try the <a href='login.php'>regular login page</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color:red'>ERROR: " . $e->getMessage() . "</div>";
}
?>
