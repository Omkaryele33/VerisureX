<?php
/**
 * Session Configuration
 * This must be included before any output or other session operations
 */

// Start the session if it hasn't been started
if (session_status() === PHP_SESSION_NONE) {
    // Session settings
    ini_set("session.cookie_httponly", 1);
    ini_set("session.cookie_secure", 0);  // Set to 1 if using HTTPS
    ini_set("session.cookie_samesite", "Strict");
    ini_set("session.gc_maxlifetime", 3600);
    ini_set("session.use_strict_mode", 1);
    
    // Start the session
    session_start();
} else {
    // Session already started elsewhere - no need to modify settings
}

// Function to set session messages
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

// Function to get and clear session message
function getMessage() {
    $message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
    unset($_SESSION['message']);
    return $message;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
