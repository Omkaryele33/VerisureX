<?php
/**
 * Reset Rate Limits
 * This script clears the rate_limits table for testing purposes
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Reset Rate Limits</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
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
    }
    
    // Check if the rate_limits table exists
    $tableExists = false;
    try {
        $result = $db->query("SELECT 1 FROM rate_limits LIMIT 1");
        $tableExists = true;
        echo "<p style='color:green'>✓ The rate_limits table exists.</p>";
    } catch (PDOException $e) {
        $tableExists = false;
        echo "<p style='color:orange'>! The rate_limits table does not exist.</p>";
    }
    
    if ($tableExists) {
        // First get a count of records
        $stmt = $db->query("SELECT COUNT(*) as count FROM rate_limits");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Found $count rate limit records.</p>";
        
        // Delete all records from the rate_limits table
        $query = "DELETE FROM rate_limits";
        $db->exec($query);
        echo "<p style='color:green'>✓ Successfully cleared all rate limit records.</p>";

        // Add a test entry with the current key to verify it's actually gone
        $testKey = 'test_' . md5($_SERVER['REMOTE_ADDR'] . '_' . $certificateId);
        echo "<p>Checking key that was previously rate limited: $testKey</p>";

        // Query for this specific key
        $query = "SELECT COUNT(*) as count FROM rate_limits 
                  WHERE identifier = :identifier";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $testKey);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Found " . $result['count'] . " records for this key after reset.</p>";
    }
    
    // Also clear session-based rate limits for good measure
    if (isset($_SESSION['rate_limits'])) {
        unset($_SESSION['rate_limits']);
        echo "<p style='color:green'>✓ Successfully cleared session-based rate limits.</p>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>Rate limits have been reset. You can now test rate limiting again.</p>";
    echo "<p>Go back to <a href='test_verification.php'>test verification</a> to see if rate limiting starts fresh.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 