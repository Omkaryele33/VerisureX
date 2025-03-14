<?php
/**
 * Secure Database Credentials
 * This file should be placed outside the web root in production
 */

// Initialize variables to avoid undefined errors
$testConnection = null;
$result = null;
$dbError = null;

// Database settings - only define if not already defined
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

// Check which database exists and use the correct one
try {
    $testConnection = @mysqli_connect('localhost', 'root', '');
    if ($testConnection) {
        $result = mysqli_query($testConnection, "SHOW DATABASES LIKE 'certificate_system'");
        if ($result && mysqli_num_rows($result) > 0) {
            if (!defined('DB_NAME')) {
                define('DB_NAME', 'certificate_system');
            }
        } else {
            // Try to create certificate_system database if it doesn't exist
            if (mysqli_query($testConnection, "CREATE DATABASE IF NOT EXISTS certificate_system")) {
                if (!defined('DB_NAME')) {
                    define('DB_NAME', 'certificate_system');
                }
            } else {
                $dbError = 'Failed to create database: ' . mysqli_error($testConnection);
            }
        }
        mysqli_close($testConnection);
    } else {
        $dbError = 'Failed to connect to MySQL: ' . mysqli_connect_error();
    }
} catch (Exception $e) {
    $dbError = 'Database connection test failed: ' . $e->getMessage();
    error_log($dbError);
}

// Fallback to certificate_db if certificate_system is not available
if (!defined('DB_NAME')) {
    define('DB_NAME', 'certificate_db');
    // Try to create certificate_db as fallback
    try {
        $testConnection = @mysqli_connect('localhost', 'root', '');
        if ($testConnection) {
            mysqli_query($testConnection, "CREATE DATABASE IF NOT EXISTS certificate_db");
            mysqli_close($testConnection);
        }
    } catch (Exception $e) {
        error_log('Failed to create fallback database: ' . $e->getMessage());
    }
}

// Set database credentials
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// Additional security settings
define('HASH_SECRET', '8b7df143d91c716ecfa5fc1730022f6b'); // Random secret for hashing
define('ENCRYPTION_KEY', '4d6783e91af4e3dfa5fc1720022f6ab'); // For encrypting sensitive data

// Log any database errors
if ($dbError) {
    error_log('[Database Config] ' . $dbError);
}
?>