<?php
/**
 * Security Fix File
 * This file defines all missing security constants and adds compatibility functions
 */

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
if (!defined('ENABLE_DIGITAL_SIGNATURES')) define('ENABLE_DIGITAL_SIGNATURES', false);
if (!defined('SIGNATURE_KEY_FILE')) define('SIGNATURE_KEY_FILE', dirname(__DIR__) . '/secure_config/private_key.pem');
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('ACCOUNT_LOCKOUT_TIME')) define('ACCOUNT_LOCKOUT_TIME', 900);

// Function aliases for backward compatibility
if (!function_exists('isEnhancedRateLimited') && function_exists('enhancedRateLimiting')) {
    function isEnhancedRateLimited($action, $ip, $userId = null) {
        return enhancedRateLimiting($action, $ip, $userId);
    }
}
?>
