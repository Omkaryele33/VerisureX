<?php
/**
 * Security functions for the certificate validation system
 * This file includes advanced security features to protect against common vulnerabilities
 */

// Define security constants if they don't exist
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
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('ACCOUNT_LOCKOUT_TIME')) define('ACCOUNT_LOCKOUT_TIME', 900);

/**
 * Generate a new CSRF token
 * @return string The generated token
 */
function generateCSRFToken() {
    // Generate a cryptographically secure random token
    $token = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2)); // Each byte becomes 2 hex chars
    
    // Store the token in the session
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Validate CSRF token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    // Check if token exists and matches
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check token expiry
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    // Validate token
    if (hash_equals($_SESSION['csrf_token'], $token)) {
        // Generate a new token for the next request (one-time use)
        generateCSRFToken();
        return true;
    }
    
    return false;
}

/**
 * Get CSRF token form field
 * @return string HTML for a hidden input field with the CSRF token
 */
function getCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Enhanced password validation according to policy
 * @param string $password The password to validate
 * @return array Array with 'valid' (bool) and 'message' (string) keys
 */
function validatePassword($password) {
    $result = [
        'valid' => true,
        'message' => 'Password meets requirements.'
    ];
    
    // Check password length
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $result['valid'] = false;
        $result['message'] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long.";
        return $result;
    }
    
    // Check for mixed case if required
    if (PASSWORD_REQUIRE_MIXED_CASE && !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = "Password must contain both uppercase and lowercase letters.";
        return $result;
    }
    
    // Check for numbers if required
    if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = "Password must contain at least one number.";
        return $result;
    }
    
    // Check for symbols if required
    if (PASSWORD_REQUIRE_SYMBOLS && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = "Password must contain at least one special character.";
        return $result;
    }
    
    return $result;
}

/**
 * Enhanced rate limiting using multiple factors
 * @param string $action The action being rate limited
 * @param string $ip IP address of the requester
 * @param string $userId Optional user ID for authenticated users
 * @return bool True if rate limited, false otherwise
 */
function enhancedRateLimiting($action, $ip, $userId = null) {
    global $db;
    
    // Create a unique identifier based on IP and user-agent (if enabled)
    $identifier = $ip;
    
    if (defined('RATE_LIMIT_UNIQUE_KEYS') && RATE_LIMIT_UNIQUE_KEYS) {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        $sessionId = session_id();
        $identifier = hash('sha256', $ip . $userAgent . $sessionId . ($userId ?? ''));
    }
    
    // Get current timestamp
    $now = time();
    $timeWindow = $now - RATE_LIMIT_WINDOW;
    
    try {
        // Check if rate_limits table exists
        try {
            $db->query("SELECT 1 FROM rate_limits LIMIT 1");
        } catch (PDOException $e) {
            // Table doesn't exist, log and bypass rate limiting
            error_log("Rate limits table is missing: " . $e->getMessage());
            return false; // Bypass rate limiting if table doesn't exist
        }
        
        // Query for recent requests
        $query = "SELECT COUNT(*) as count FROM rate_limits 
                WHERE identifier = :identifier 
                AND action = :action 
                AND timestamp > :time_window";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':time_window', $timeWindow);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];
        
        // Check if limit exceeded
        if ($count >= RATE_LIMIT) {
            // Log rate limit hit
            error_log("Rate limit exceeded for {$action} by {$ip}");
            return true; // Rate limited
        }
        
        // Log this request
        $query = "INSERT INTO rate_limits (identifier, action, timestamp, ip) 
                VALUES (:identifier, :action, :timestamp, :ip)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':timestamp', $now);
        $stmt->bindParam(':ip', $ip);
        $stmt->execute();
        
        // Clean up old entries occasionally (1% chance to avoid doing this on every request)
        if (rand(1, 100) === 1) {
            $cleanupTime = $now - (RATE_LIMIT_WINDOW * 2); // Keep a bit of history
            $query = "DELETE FROM rate_limits WHERE timestamp < :cleanup_time";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':cleanup_time', $cleanupTime);
            $stmt->execute();
        }
        
        return false; // Not rate limited
    } catch (Exception $e) {
        // Log the error but don't disrupt the application
        error_log("Rate limiting error: " . $e->getMessage());
        return false; // On any error, bypass rate limiting
    }
}

/**
 * Compatibility function
 */
function isEnhancedRateLimited($action, $ip, $userId = null) {
    return enhancedRateLimiting($action, $ip, $userId);
}

/**
 * Track failed login attempts and handle account lockouts
 * @param string $username The username that failed login
 * @return bool True if account is locked, false otherwise
 */
function trackFailedLogin($username) {
    global $db;
    
    try {
        // Get current failed attempts
        $query = "SELECT failed_login_attempts, last_failed_login, account_locked 
                  FROM admins WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $attempts = (int)$user['failed_login_attempts'] + 1;
            $now = time();
            
            // Update failed attempts
            $query = "UPDATE admins SET 
                      failed_login_attempts = :attempts, 
                      last_failed_login = :time";
            
            // Check if we should lock the account
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $query .= ", account_locked = 1";
            }
            
            $query .= " WHERE username = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':attempts', $attempts);
            $stmt->bindParam(':time', $now);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            // Log excessive attempts
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                error_log("Account '{$username}' locked due to {$attempts} failed login attempts");
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error tracking failed login: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if account is locked
 * @param string $username The username to check
 * @return bool True if account is locked, false otherwise
 */
function isAccountLocked($username) {
    global $db;
    
    try {
        $query = "SELECT account_locked, last_failed_login 
                  FROM admins WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If account is locked but lock time has expired, unlock it
            if ($user['account_locked'] == 1) {
                $lockTime = (int)$user['last_failed_login'];
                $now = time();
                
                if ($now - $lockTime > ACCOUNT_LOCKOUT_TIME) {
                    // Unlock account
                    $query = "UPDATE admins SET 
                              account_locked = 0, 
                              failed_login_attempts = 0 
                              WHERE username = :username";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->execute();
                    
                    return false;
                }
                
                // Calculate remaining lockout time
                $remainingTime = ceil(($lockTime + ACCOUNT_LOCKOUT_TIME - $now) / 60);
                return "Account is locked. Try again in {$remainingTime} minutes.";
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking account lock: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset failed login attempts after successful login
 * @param string $username The username that logged in successfully
 */
function resetFailedLoginAttempts($username) {
    global $db;
    
    try {
        $query = "UPDATE admins SET 
                  failed_login_attempts = 0, 
                  last_failed_login = NULL, 
                  account_locked = 0 
                  WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error resetting failed login attempts: " . $e->getMessage());
    }
}

/**
 * Increment failed login attempts
 * @param string $username The username to increment failed login attempts for
 */
function incrementFailedLoginAttempts($username) {
    global $db;
    
    try {
        $query = "UPDATE users SET login_attempts = login_attempts + 1 WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Increment failed login attempts error: " . $e->getMessage());
    }
}

/**
 * Log security events
 * @param string $event_type Type of security event
 * @param string $username Username associated with the event
 * @param string $details Additional details about the event
 */
function logSecurityEvent($event_type, $username, $details = '') {
    global $db;
    
    try {
        $query = "INSERT INTO security_logs (event_type, username, ip_address, user_agent, details) 
                 VALUES (:event_type, :username, :ip_address, :user_agent, :details)";
        $stmt = $db->prepare($query);
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt->bindParam(':event_type', $event_type);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':ip_address', $ip);
        $stmt->bindParam(':user_agent', $userAgent);
        $stmt->bindParam(':details', $details);
        
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Security event logging error: " . $e->getMessage());
    }
}
?>
