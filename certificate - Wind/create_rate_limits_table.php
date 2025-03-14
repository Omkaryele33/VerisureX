<?php
/**
 * Create Rate Limits Table
 * This script creates the rate_limits table if it doesn't exist
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Creating Rate Limits Table</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if the rate_limits table already exists
    $tableExists = false;
    try {
        $result = $db->query("SELECT 1 FROM rate_limits LIMIT 1");
        $tableExists = true;
        echo "<p style='color:green'>✓ The rate_limits table already exists.</p>";
    } catch (PDOException $e) {
        $tableExists = false;
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
    
    // Create indexes if they don't exist
    if ($tableExists) {
        // Check if indexes exist
        $hasIdentifierActionIndex = false;
        $hasTimestampIndex = false;
        
        $indexes = $db->query("SHOW INDEX FROM rate_limits")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($indexes as $index) {
            if ($index['Key_name'] === 'idx_rate_identifier_action') {
                $hasIdentifierActionIndex = true;
            } else if ($index['Key_name'] === 'idx_rate_timestamp') {
                $hasTimestampIndex = true;
            }
        }
        
        // Add missing indexes
        if (!$hasIdentifierActionIndex) {
            $db->exec("CREATE INDEX idx_rate_identifier_action ON rate_limits (identifier, action)");
            echo "<p style='color:green'>✓ Added idx_rate_identifier_action index.</p>";
        }
        
        if (!$hasTimestampIndex) {
            $db->exec("CREATE INDEX idx_rate_timestamp ON rate_limits (timestamp)");
            echo "<p style='color:green'>✓ Added idx_rate_timestamp index.</p>";
        }
    }
    
    echo "<p>The rate_limits table has been set up.</p>";
    echo "<p>You can now <a href='verify/index.php'>try verifying a certificate</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 