<?php
/**
 * Fix Certificate Tables
 * This script fixes issues with missing database tables for certificate verification
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Certificate Verification System - Fix Tables</h1>";

// Define which fixes to run
$runRateLimitsFix = true;
$runVerificationTableFix = true;

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Database Connection Successful</h2>";
    
    // 1. Fix rate_limits table
    if ($runRateLimitsFix) {
        echo "<h2>Checking rate_limits table</h2>";
        
        // Check if the rate_limits table already exists
        $tableExists = false;
        try {
            $result = $db->query("SELECT 1 FROM rate_limits LIMIT 1");
            $tableExists = true;
            echo "<p style='color:green'>✓ The rate_limits table already exists.</p>";
        } catch (PDOException $e) {
            $tableExists = false;
            echo "<p style='color:orange'>! The rate_limits table does not exist.</p>";
        }
        
        if (!$tableExists) {
            // Create the rate_limits table
            $query = "CREATE TABLE `rate_limits` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `identifier` varchar(64) NOT NULL,
                `action` varchar(50) NOT NULL,
                `timestamp` int(11) NOT NULL,
                `ip` varchar(45) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_rate_identifier_action` (`identifier`, `action`),
                KEY `idx_rate_timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            // Execute the query
            $db->exec($query);
            echo "<p style='color:green'>✓ Successfully created the rate_limits table.</p>";
        }
    }
    
    // 2. Fix certificate_verifications table
    if ($runVerificationTableFix) {
        echo "<h2>Checking certificate_verifications table</h2>";
        
        // Check if the certificate_verifications table already exists
        $tableExists = false;
        try {
            $result = $db->query("SELECT 1 FROM certificate_verifications LIMIT 1");
            $tableExists = true;
            echo "<p style='color:green'>✓ The certificate_verifications table already exists.</p>";
        } catch (PDOException $e) {
            $tableExists = false;
            echo "<p style='color:orange'>! The certificate_verifications table does not exist.</p>";
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
            try {
                $stmt = $db->query("SHOW COLUMNS FROM certificates LIKE 'verification_count'");
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE certificates ADD COLUMN verification_count INT NOT NULL DEFAULT 0 AFTER validation_status");
                    echo "<p style='color:green'>✓ Added verification_count column to certificates table.</p>";
                } else {
                    echo "<p style='color:green'>✓ Verification_count column already exists in certificates table.</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color:red'>! Could not check/add verification_count column: " . $e->getMessage() . "</p>";
            }
            
            // Add last_verified column to certificates table if it doesn't exist
            try {
                $stmt = $db->query("SHOW COLUMNS FROM certificates LIKE 'last_verified'");
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE certificates ADD COLUMN last_verified TIMESTAMP NULL AFTER verification_count");
                    echo "<p style='color:green'>✓ Added last_verified column to certificates table.</p>";
                } else {
                    echo "<p style='color:green'>✓ Last_verified column already exists in certificates table.</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color:red'>! Could not check/add last_verified column: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // 3. Confirm security.php has been fixed
    echo "<h2>Checking security.php fallback for rate limiting</h2>";
    
    if (file_exists('includes/security.php')) {
        $securityContent = file_get_contents('includes/security.php');
        
        if (strpos($securityContent, 'rate_limits table is missing') !== false) {
            echo "<p style='color:green'>✓ Security.php already has the rate_limits fallback mechanism.</p>";
        } else {
            echo "<p style='color:orange'>! Security.php doesn't have the rate_limits fallback mechanism.</p>";
            echo "<p>Please run the following command to fix it:</p>";
            echo "<code>php fix_rate_limiting.php</code>";
        }
    } else {
        echo "<p style='color:red'>! Could not find includes/security.php file.</p>";
    }
    
    // 4. Check verify/index.php for certificate_verifications fallback
    echo "<h2>Checking verify/index.php fallback for certificate_verifications</h2>";
    
    if (file_exists('verify/index.php')) {
        $indexContent = file_get_contents('verify/index.php');
        
        if (strpos($indexContent, 'certificate_verifications table is missing') !== false) {
            echo "<p style='color:green'>✓ verify/index.php already has the certificate_verifications fallback mechanism.</p>";
        } else {
            echo "<p style='color:orange'>! verify/index.php doesn't have the certificate_verifications fallback mechanism.</p>";
            echo "<p>Please run the create_verification_tables.php script to fix it.</p>";
        }
    } else {
        echo "<p style='color:red'>! Could not find verify/index.php file.</p>";
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>The database table fixes have been completed.</p>";
    echo "<p>You can now <a href='verify/index.php'>try verifying a certificate</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 