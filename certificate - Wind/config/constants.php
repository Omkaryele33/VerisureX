<?php
/**
 * Application Constants
 * This file defines all constants used throughout the application
 * Include this file only once at the beginning of your application
 */

// Base URL - Change this to match your domain with protocol detection
define("BASE_URL", (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://{$_SERVER["HTTP_HOST"]}/certificate");

// Admin panel URL
define("ADMIN_URL", BASE_URL . "/admin");

// Verification URL
define("VERIFY_URL", BASE_URL . "/verify");

// File upload settings
define("MAX_FILE_SIZE", 5 * 1024 * 1024);  // 5MB
define("ALLOWED_EXTENSIONS", "jpg,jpeg,png,pdf");
define("UPLOAD_DIR", dirname(__DIR__) . "/uploads");
define("QR_DIR", dirname(__DIR__) . "/qr_codes");  // Directory for storing QR codes

// Security settings
define("RATE_LIMIT", 5);  // Maximum allowed requests
define("RATE_LIMIT_WINDOW", 60);  // Time window in seconds
define("RATE_LIMIT_UNIQUE_KEYS", 1000);  // Maximum unique keys to track
define("CSRF_TOKEN_LENGTH", 64);  // CSRF token length
define("CSRF_TOKEN_EXPIRY", 3600);  // CSRF token expiry in seconds

// Password policy
define("PASSWORD_MIN_LENGTH", 8);
define("PASSWORD_REQUIRE_MIXED_CASE", true);
define("PASSWORD_REQUIRE_NUMBERS", true);
define("PASSWORD_REQUIRE_SYMBOLS", true);

// Login security
define("MAX_LOGIN_ATTEMPTS", 5);
define("ACCOUNT_LOCKOUT_TIME", 15 * 60);  // 15 minutes
