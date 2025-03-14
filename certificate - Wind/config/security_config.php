<?php
/**
 * Security Configuration Settings
 * Defines security-related constants for the certificate validation system
 */

// CSRF protection settings
define('CSRF_TOKEN_LENGTH', 32); // Length of token in characters
define('CSRF_TOKEN_EXPIRY', 3600); // Token expiry time in seconds (1 hour)

// Password policy settings
define('PASSWORD_MIN_LENGTH', 8); // Minimum password length
define('PASSWORD_REQUIRE_MIXED_CASE', true); // Require both upper and lowercase letters
define('PASSWORD_REQUIRE_NUMBERS', true); // Require at least one number
define('PASSWORD_REQUIRE_SYMBOLS', true); // Require at least one special character
define('PASSWORD_MAX_AGE_DAYS', 90); // Password expires after 90 days

// Rate limiting settings
define('RATE_LIMIT', 10); // Maximum number of requests per time window
define('RATE_LIMIT_WINDOW', 300); // Time window in seconds (5 minutes)
define('RATE_LIMIT_UNIQUE_KEYS', true); // Use unique identifiers for rate limiting

// Digital signature settings
define('ENABLE_DIGITAL_SIGNATURES', false); // Enable/disable digital signatures
define('SIGNATURE_KEY_FILE', __DIR__ . '/../secure_config/private_key.pem'); // Location of private key file

// Account security settings
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum failed login attempts before locking account
define('ACCOUNT_LOCKOUT_TIME', 900); // Account lockout time in seconds (15 minutes)
?>
