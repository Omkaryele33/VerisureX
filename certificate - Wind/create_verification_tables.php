<?php
/**
 * Create Certificate Verification Tables
 * This script creates the certificate_verifications table if it doesn't exist
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Creating Certificate Verification Tables</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if the certificate_verifications table already exists
    $tableExists = false;
    try {
        $result = $db->query("SELECT 1 FROM certificate_verifications LIMIT 1");
        $tableExists = true;
        echo "<p style='color:green'>✓ The certificate_verifications table already exists.</p>";
    } catch (PDOException $e) {
        $tableExists = false;
    }
    
    if (!$tableExists) {
        // Create the certificate_verifications table
        $query = "CREATE TABLE `certificate_verifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `certificate_id` varchar(36) NOT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `country` varchar(2) DEFAULT 'XX',
            `device_type` varchar(20) DEFAULT 'Unknown',
            `browser` varchar(50) DEFAULT 'Unknown',
            `http_referrer` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `fk_verifications_certificate` (`certificate_id`),
            KEY `idx_verification_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        // Execute the query
        $db->exec($query);
        echo "<p style='color:green'>✓ Successfully created the certificate_verifications table.</p>";
        
        // Add verification_count column to certificates table if it doesn't exist
        $stmt = $db->query("SHOW COLUMNS FROM certificates LIKE 'verification_count'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE certificates ADD COLUMN verification_count INT NOT NULL DEFAULT 0 AFTER validation_status");
            echo "<p style='color:green'>✓ Added verification_count column to certificates table.</p>";
        }
        
        // Add last_verified column to certificates table if it doesn't exist
        $stmt = $db->query("SHOW COLUMNS FROM certificates LIKE 'last_verified'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE certificates ADD COLUMN last_verified TIMESTAMP NULL AFTER verification_count");
            echo "<p style='color:green'>✓ Added last_verified column to certificates table.</p>";
        }
    }
    
    // Modify the logEnhancedVerification function in verify/index.php to handle missing tables
    if (file_exists('verify/index.php')) {
        $indexPath = 'verify/index.php';
        $indexContent = file_get_contents($indexPath);
        
        // Check if we need to modify the function
        if (strpos($indexContent, 'certificate_verifications table is missing') === false) {
            // Find the logEnhancedVerification function
            $pattern = '/function logEnhancedVerification\([^)]+\)[^{]*\{/';
            preg_match($pattern, $indexContent, $matches, PREG_OFFSET_CAPTURE);
            
            if (!empty($matches)) {
                // Make a backup of the original file
                $backupPath = 'verify/index.php.bak.' . time();
                file_put_contents($backupPath, $indexContent);
                echo "<p style='color:blue'>Created backup of index.php at $backupPath</p>";
                
                // Modify the logEnhancedVerification function to handle missing tables
                $originalFunction = 'function logEnhancedVerification($db, $certificateId, $ipAddress, $userAgent) {
    try {';
                $modifiedFunction = 'function logEnhancedVerification($db, $certificateId, $ipAddress, $userAgent) {
    try {
        // Check if certificate_verifications table exists
        try {
            $db->query("SELECT 1 FROM certificate_verifications LIMIT 1");
        } catch (PDOException $e) {
            // Table doesn\'t exist, log and return
            error_log("certificate_verifications table is missing: " . $e->getMessage());
            return false;
        }';
                
                // Replace the function definition
                $newContent = str_replace($originalFunction, $modifiedFunction, $indexContent);
                
                // Write the modified content back
                file_put_contents($indexPath, $newContent);
                
                echo "<p style='color:green'>✓ Modified logEnhancedVerification function to handle missing tables</p>";
            } else {
                echo "<p style='color:orange'>Could not find logEnhancedVerification function in index.php. No changes made.</p>";
            }
        } else {
            echo "<p style='color:blue'>logEnhancedVerification function already handles missing tables.</p>";
        }
    } else {
        echo "<p style='color:red'>Could not find verify/index.php file.</p>";
    }
    
    echo "<p>The certificate verification tables have been set up.</p>";
    echo "<p>You can now <a href='verify/index.php'>try verifying a certificate</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 