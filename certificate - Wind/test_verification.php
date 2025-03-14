<?php
/**
 * Test Certificate Verification
 * This script tests the certificate verification functionality
 * to ensure the rate limiting and verification logging works properly
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Certificate Verification Test</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    require_once 'includes/security.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Database Connection Successful</h2>";
    
    // Get a valid certificate ID for testing
    $certificateId = null;
    $query = "SELECT certificate_id FROM certificates LIMIT 1";
    $stmt = $db->query($query);
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $certificateId = $row['certificate_id'];
        echo "<p>Found certificate ID for testing: $certificateId</p>";
    } else {
        echo "<p style='color:red'>No certificates found in the database for testing.</p>";
        exit;
    }
    
    // Test rate limiting
    echo "<h2>Testing Rate Limiting</h2>";
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $rateLimitKey = 'test_' . md5($ipAddress . '_' . $certificateId);
    
    echo "<p>Testing rate limiting with key: $rateLimitKey</p>";
    
    for ($i = 1; $i <= 6; $i++) {
        $isLimited = isEnhancedRateLimited($rateLimitKey, 5, 60);
        echo "<p>Attempt $i: " . ($isLimited ? "Rate limited ⚠️" : "Not rate limited ✓") . "</p>";
    }
    
    // Test verification tables
    echo "<h2>Testing Verification Tables</h2>";
    
    // Check if certificate_verifications table exists
    $certificateVerificationsExists = false;
    try {
        $db->query("SELECT 1 FROM certificate_verifications LIMIT 1");
        $certificateVerificationsExists = true;
        echo "<p style='color:green'>✓ certificate_verifications table exists</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>✗ certificate_verifications table doesn't exist: " . $e->getMessage() . "</p>";
    }
    
    // Check if rate_limits table exists
    $rateLimitsExists = false;
    try {
        $db->query("SELECT 1 FROM rate_limits LIMIT 1");
        $rateLimitsExists = true;
        echo "<p style='color:green'>✓ rate_limits table exists</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>✗ rate_limits table doesn't exist: " . $e->getMessage() . "</p>";
    }
    
    // Test a direct insert into rate_limits
    if ($rateLimitsExists) {
        $testId = 'direct_test_' . time();
        $testTimestamp = time();
        
        try {
            $query = "INSERT INTO rate_limits (identifier, action, timestamp, ip) 
                      VALUES (:identifier, :action, :timestamp, :ip)";
            $stmt = $db->prepare($query);
            $stmt->bindValue(':identifier', $testId);
            $stmt->bindValue(':action', 'test_action');
            $stmt->bindValue(':timestamp', $testTimestamp);
            $stmt->bindValue(':ip', $ipAddress);
            $stmt->execute();
            
            echo "<p style='color:green'>✓ Successfully inserted test record into rate_limits table</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>✗ Failed to insert into rate_limits table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Summary
    echo "<h2>Summary</h2>";
    
    if (!$certificateVerificationsExists || !$rateLimitsExists) {
        echo "<p>Some tables are missing but your verification system will still work because of the fallback mechanisms.</p>";
        echo "<p>To create the missing tables, run:</p>";
        echo "<pre>C:\\xampp\\php\\php.exe fix_certificate_tables.php</pre>";
    } else {
        echo "<p style='color:green'>✓ All verification tables exist.</p>";
    }
    
    echo "<p>You can now <a href='verify/index.php?id=$certificateId'>try verifying a certificate</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 