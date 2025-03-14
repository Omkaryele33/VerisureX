<?php
/**
 * Session Debug Tool
 * This script displays current session information for debugging
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once 'master_init.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Session Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 20px; }
        .section { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .hidden { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <h1>Session Debug Tool</h1>";

echo "<div class='section'>";
echo "<h2>Session Status</h2>";

// Check if session is active
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p class='success'>✓ Session is active</p>";
} else {
    echo "<p class='error'>✗ Session is not active</p>";
}

// Display session ID (partially masked)
$session_id = session_id();
$masked_id = substr($session_id, 0, 5) . str_repeat('*', strlen($session_id) - 10) . substr($session_id, -5);
echo "<p>Session ID: {$masked_id}</p>";

// Authentication status
if (isLoggedIn()) {
    echo "<p class='success'>✓ User is logged in</p>";
} else {
    echo "<p class='error'>✗ User is not logged in</p>";
}
echo "</div>";

// Session variables
echo "<div class='section'>";
echo "<h2>Session Variables</h2>";

if (empty($_SESSION)) {
    echo "<p>No session variables found.</p>";
} else {
    echo "<table>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    
    foreach ($_SESSION as $key => $value) {
        $displayValue = $value;
        
        // Mask sensitive data
        if (in_array($key, ['password', 'token', 'csrf']) || strpos($key, 'password') !== false) {
            $displayValue = "<span class='hidden'>[HIDDEN]</span>";
        } else if (is_array($value)) {
            $displayValue = "<pre>" . print_r($value, true) . "</pre>";
        }
        
        echo "<tr><td>{$key}</td><td>{$displayValue}</td></tr>";
    }
    
    echo "</table>";
}
echo "</div>";

// Database check for the user/admin
echo "<div class='section'>";
echo "<h2>Database User Check</h2>";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    try {
        // Check in users table
        $query = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='success'>✓ User found in users table (ID: {$userId})</p>";
            
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            
            foreach ($user as $key => $value) {
                // Mask sensitive data
                if (in_array($key, ['password', 'token', 'csrf']) || strpos($key, 'password') !== false) {
                    $displayValue = "<span class='hidden'>[HIDDEN]</span>";
                } else {
                    $displayValue = $value ?? "<span class='hidden'>NULL</span>";
                }
                
                echo "<tr><td>{$key}</td><td>{$displayValue}</td></tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>User not found in users table with ID: {$userId}</p>";
            
            // Check in admins table
            $query = "SELECT * FROM admins WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p class='success'>✓ User found in admins table (ID: {$userId})</p>";
                
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                
                foreach ($admin as $key => $value) {
                    // Mask sensitive data
                    if (in_array($key, ['password', 'token', 'csrf']) || strpos($key, 'password') !== false) {
                        $displayValue = "<span class='hidden'>[HIDDEN]</span>";
                    } else {
                        $displayValue = $value ?? "<span class='hidden'>NULL</span>";
                    }
                    
                    echo "<tr><td>{$key}</td><td>{$displayValue}</td></tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p class='error'>✗ User not found in admins table with ID: {$userId}</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>No user ID in session to check.</p>";
}
echo "</div>";

// Fix recommendations
echo "<div class='section'>";
echo "<h2>Recommendations</h2>";

if (!isLoggedIn()) {
    echo "<p>Please <a href='login.php'>log in</a> to create certificates.</p>";
} elseif (isset($_SESSION['user_id'])) {
    echo "<p>The session has a valid user_id. Certificate creation should work if:</p>";
    echo "<ol>";
    echo "<li>The user_id in session matches an existing admin ID in the admins table</li>";
    echo "<li>The create_certificate.php file correctly uses the user_id from session</li>";
    echo "</ol>";
    echo "<p><a href='create_certificate.php'>Try creating a certificate</a></p>";
} else {
    echo "<p class='error'>The session is missing critical authentication variables.</p>";
    echo "<p>Try logging out and logging back in.</p>";
    echo "<p><a href='logout.php'>Log out</a></p>";
}
echo "</div>";

echo "</body></html>"; 