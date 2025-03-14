<?php
/**
 * Setup Database Tables
 */

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connect to database
$database = new Database();
$db = $database->getConnection();

echo "<h1>Database Setup Script</h1>";

// Verification Logs Table
$verificationLogsTable = "
CREATE TABLE IF NOT EXISTS `verification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `is_valid` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_certificate_id` (`certificate_id`),
  KEY `idx_verified_at` (`verified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute SQL
try {
    $db->exec($verificationLogsTable);
    echo "<p style='color:green'>✓ Verification logs table created or already exists</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error creating verification logs table: " . $e->getMessage() . "</p>";
}

// Add last_activity column to admins table if it doesn't exist
try {
    $checkColumn = $db->query("SHOW COLUMNS FROM admins LIKE 'last_activity'");
    if ($checkColumn->rowCount() === 0) {
        $db->exec("ALTER TABLE admins ADD COLUMN last_activity datetime DEFAULT NULL");
        echo "<p style='color:green'>✓ Added last_activity column to admins table</p>";
    } else {
        echo "<p style='color:green'>✓ last_activity column already exists in admins table</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error modifying admins table: " . $e->getMessage() . "</p>";
}

// Add email and full_name columns to admins table if they don't exist
try {
    $checkEmailColumn = $db->query("SHOW COLUMNS FROM admins LIKE 'email'");
    if ($checkEmailColumn->rowCount() === 0) {
        $db->exec("ALTER TABLE admins ADD COLUMN email varchar(255) DEFAULT NULL");
        echo "<p style='color:green'>✓ Added email column to admins table</p>";
    } else {
        echo "<p style='color:green'>✓ email column already exists in admins table</p>";
    }
    
    $checkFullNameColumn = $db->query("SHOW COLUMNS FROM admins LIKE 'full_name'");
    if ($checkFullNameColumn->rowCount() === 0) {
        $db->exec("ALTER TABLE admins ADD COLUMN full_name varchar(255) DEFAULT NULL");
        echo "<p style='color:green'>✓ Added full_name column to admins table</p>";
    } else {
        echo "<p style='color:green'>✓ full_name column already exists in admins table</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error modifying admins table: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' class='btn' style='display: inline-block; padding: 8px 16px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 20px;'>Return to Admin Panel</a></p>";
?>
