<?php
/**
 * VeriSureX Configuration File
 * Copy this file to config.php and update the values
 */

// Base URL configuration
define('BASE_URL', 'http://localhost/verisurex/');
define('VERIFY_URL', BASE_URL . 'verify/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'verisurex_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security settings
define('ENABLE_RATE_LIMITING', true);
define('VERIFY_RATE_LIMIT', 5);
define('VERIFY_RATE_WINDOW', 300);
define('ENABLE_DIGITAL_SIGNATURES', true);
define('SIGNATURE_SECRET_KEY', 'your-secret-key-here');

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('QRCODE_PATH', __DIR__ . '/../assets/img/qrcodes/');

// Session configuration
define('SESSION_LIFETIME', 3600);
define('SESSION_NAME', 'VERISUREX_SESSION');

// Email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-email-password');
define('SMTP_FROM', 'noreply@verisurex.com');
define('SMTP_FROM_NAME', 'VeriSureX System');

// Admin settings
define('ADMIN_EMAIL', 'admin@verisurex.com');
define('PASSWORD_RESET_EXPIRY', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); 