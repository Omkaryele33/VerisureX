<?php
/**
 * Central Constants Definition
 * Include this file once at the beginning of your application
 * to ensure constants are defined only once.
 */

// Security settings
if (!defined("RATE_LIMIT")) define("RATE_LIMIT", 10);
if (!defined("RATE_LIMIT_WINDOW")) define("RATE_LIMIT_WINDOW", 60);
if (!defined("RATE_LIMIT_UNIQUE_KEYS")) define("RATE_LIMIT_UNIQUE_KEYS", true);

// CSRF Protection
if (!defined("CSRF_TOKEN_LENGTH")) define("CSRF_TOKEN_LENGTH", 64);
if (!defined("CSRF_TOKEN_EXPIRY")) define("CSRF_TOKEN_EXPIRY", 1800);

// Password Policy
if (!defined("PASSWORD_MIN_LENGTH")) define("PASSWORD_MIN_LENGTH", 12);
if (!defined("PASSWORD_REQUIRE_MIXED_CASE")) define("PASSWORD_REQUIRE_MIXED_CASE", true);
if (!defined("PASSWORD_REQUIRE_NUMBERS")) define("PASSWORD_REQUIRE_NUMBERS", true);
if (!defined("PASSWORD_REQUIRE_SYMBOLS")) define("PASSWORD_REQUIRE_SYMBOLS", true);
if (!defined("MAX_LOGIN_ATTEMPTS")) define("MAX_LOGIN_ATTEMPTS", 5);
if (!defined("ACCOUNT_LOCKOUT_TIME")) define("ACCOUNT_LOCKOUT_TIME", 900);
