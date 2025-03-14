<?php
/**
 * Fix Rate Limiting
 * This script modifies the rate limiting function to make it optional
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing Rate Limiting</h1>";

try {
    // Check if the security.php file exists
    if (!file_exists('includes/security.php')) {
        echo "<p style='color:red'>Error: Could not find security.php file.</p>";
        exit;
    }
    
    // Read the security.php file
    $securityPath = 'includes/security.php';
    $securityContent = file_get_contents($securityPath);
    
    // Find the isEnhancedRateLimited function
    $pattern = '/function isEnhancedRateLimited\([^)]+\)[^{]*\{/';
    preg_match($pattern, $securityContent, $matches, PREG_OFFSET_CAPTURE);
    
    if (empty($matches)) {
        echo "<p style='color:orange'>Could not find isEnhancedRateLimited function in security.php. No changes made.</p>";
        exit;
    }
    
    // Make a backup of the original file
    $backupPath = 'includes/security.php.bak.' . time();
    file_put_contents($backupPath, $securityContent);
    echo "<p style='color:blue'>Created backup of security.php at $backupPath</p>";
    
    // Modify the function to make rate limiting optional
    $originalFunction = 'function isEnhancedRateLimited($key, $maxRequests = 10, $timeWindow = 60) {';
    $modifiedFunction = <<<'EOD'
function isEnhancedRateLimited($key, $maxRequests = 10, $timeWindow = 60) {
    global $db;
    
    // Skip rate limiting if the table doesn't exist
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE `key` = :key");
        $stmt->bindParam(':key', $key);
        $stmt->execute();
    } catch (PDOException $e) {
        // Table doesn't exist, bypass rate limiting
        error_log("Rate limiting table doesn't exist. Bypassing rate limits: " . $e->getMessage());
        return false; // Not rate limited
    }
    
EOD;
    
    // Replace the function definition
    $newContent = str_replace($originalFunction, $modifiedFunction, $securityContent);
    
    // Write the modified content back
    file_put_contents($securityPath, $newContent);
    
    echo "<p style='color:green'>✓ Successfully modified rate limiting function to make it optional</p>";
    echo "<p>Now, if the rate_limits table doesn't exist, rate limiting will be bypassed and verification will continue to work.</p>";
    echo "<p>You can now <a href='verify/index.php'>try verifying a certificate</a>, or <a href='create_rate_limits_table.php'>create the rate_limits table</a> to enable rate limiting.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 