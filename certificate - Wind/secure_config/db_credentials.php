<?php
/**
 * Secure Database Credentials
 * This file should be placed outside the web root in production
 */

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'certificate_system');
define('DB_USER', 'certificate_user'); // Changed from root
define('DB_PASS', 'StrongPassword123!'); // Strong password

// Additional security settings
define('HASH_SECRET', 'ec7e5c39f8b1a62c4a0f92c7d4439926'); // Random secret for hashing
define('ENCRYPTION_KEY', '8a4b6c7d2e9f3a5b8c7d6e2f9a4b3c5d'); // For encrypting sensitive data
?>
