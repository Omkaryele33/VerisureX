<?php
/**
 * CSRF Token Fix Script
 * This script addresses issues with CSRF token generation and validation
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>CSRF Token Fix Tool</h1>";

// Include necessary files
require_once 'includes/session.php';
require_once 'includes/security.php';

// Fix 1: Check if CSRF token functions exist
echo "<h2>Checking CSRF Functions</h2>";

if (function_exists('generateCSRFToken') && function_exists('validateCSRFToken')) {
    echo "<p style='color:green'>✅ CSRF token functions exist.</p>";
} else {
    echo "<p style='color:red'>❌ CSRF token functions are missing. Please check includes/security.php.</p>";
}

// Fix 2: Test CSRF token generation
echo "<h2>Testing CSRF Token Generation</h2>";

try {
    $token = generateCSRFToken();
    echo "<p style='color:green'>✅ Successfully generated CSRF token: " . substr($token, 0, 8) . "...</p>";
    
    if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
        echo "<p style='color:green'>✅ Token correctly stored in session.</p>";
    } else {
        echo "<p style='color:red'>❌ Token not properly stored in session.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error generating CSRF token: " . $e->getMessage() . "</p>";
}

// Fix 3: Check login.php for CSRF token generation
echo "<h2>Checking Login Page</h2>";

$loginFile = file_get_contents('admin/login.php');
if ($loginFile) {
    // Check if CSRF token is generated before the form
    if (strpos($loginFile, 'generateCSRFToken()') !== false) {
        echo "<p style='color:green'>✅ Login page already generates CSRF token.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Login page may not be generating CSRF token before the form.</p>";
        echo "<p>Adding CSRF token generation to login.php...</p>";
        
        // Create a backup of the original file
        file_put_contents('admin/login.php.bak', $loginFile);
        
        // Add CSRF token generation after the error variable initialization
        $pattern = '// Initialize variables
$username = "";
$error = "";';
        $replacement = '// Initialize variables
$username = "";
$error = "";

// Generate CSRF token
$csrf_token = generateCSRFToken();';
        
        $modifiedFile = str_replace($pattern, $replacement, $loginFile);
        
        if ($modifiedFile !== $loginFile) {
            file_put_contents('admin/login.php', $modifiedFile);
            echo "<p style='color:green'>✅ Added CSRF token generation to login.php.</p>";
        } else {
            echo "<p style='color:red'>❌ Could not modify login.php. Please add CSRF token generation manually.</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ Could not read login.php file.</p>";
}

// Fix 4: Clear session data to ensure fresh start
echo "<h2>Clearing Session Data</h2>";

// Backup important session data
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Clear session
session_unset();
session_destroy();
session_start();

// Restore important data if user was logged in
if ($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    echo "<p style='color:green'>✅ Cleared session data while preserving login state.</p>";
} else {
    echo "<p style='color:green'>✅ Cleared all session data for fresh start.</p>";
}

// Generate a new CSRF token
$csrf_token = generateCSRFToken();
echo "<p style='color:green'>✅ Generated new CSRF token: " . substr($csrf_token, 0, 8) . "...</p>";

// Provide links to other useful pages
echo "<h2>Next Steps</h2>";
echo "<p>After running this fix:</p>";
echo "<ol>";
echo "<li>Clear your browser cookies and cache</li>";
echo "<li>Try logging in again with username 'admin' and password 'admin123'</li>";
echo "</ol>";
echo "<p><a href='admin/login.php' style='font-weight:bold; margin-right:15px'>Go to Admin Login</a>";
echo "<a href='index.php'>Home Page</a></p>";
?> 