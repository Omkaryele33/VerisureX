<?php
// This file adds the missing constants needed by security.php
// Include this at the top of your admin pages until you can update config.php

// Only define if not already defined
if (!defined('VERIFY_RATE_LIMIT')) {
    define('VERIFY_RATE_LIMIT', 20); // 20 verification attempts per window
}

if (!defined('VERIFY_RATE_WINDOW')) {
    define('VERIFY_RATE_WINDOW', 300); // 5 minutes window
}

if (!defined('API_RATE_LIMIT')) {
    define('API_RATE_LIMIT', 100); // 100 API requests per window
}

if (!defined('API_RATE_WINDOW')) {
    define('API_RATE_WINDOW', 60); // 1 minute window
}

if (!defined('ENABLE_DIGITAL_SIGNATURES')) {
    define('ENABLE_DIGITAL_SIGNATURES', true);
}

if (!defined('SIGNATURE_ALGORITHM')) {
    define('SIGNATURE_ALGORITHM', 'sha256WithRSAEncryption');
}

if (!defined('PRIVATE_KEY_PATH')) {
    define('PRIVATE_KEY_PATH', dirname(__DIR__) . '/secure_config/private.key');
}

if (!defined('PUBLIC_KEY_PATH')) {
    define('PUBLIC_KEY_PATH', dirname(__DIR__) . '/secure_config/public.key');
}

// Function to alias enhancedRateLimiting if needed
if (!function_exists('isEnhancedRateLimited') && function_exists('enhancedRateLimiting')) {
    function isEnhancedRateLimited($action, $ip, $userId = null) {
        return enhancedRateLimiting($action, $ip, $userId);
    }
}

// Turn on error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
