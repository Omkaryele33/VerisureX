<?php
// Set error reporting to maximum
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Certificate System Database Setup</h1>";

// Check if MySQL is running
$mysqlRunning = @fsockopen('localhost', 3306, $errno, $errstr, 1);
if (!$mysqlRunning) {
    echo "<div style='color:red;font-weight:bold;'>MySQL is not running. Please start the MySQL service in XAMPP Control Panel.</div>";
    exit;
}
fclose($mysqlRunning);

// Try to connect with default XAMPP credentials
try {
    $conn = new PDO("mysql:host=localhost", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div style='color:green;'>Successfully connected to MySQL with default credentials.</div>";
    
    // Check if certificate_db exists
    $stmt = $conn->query("SHOW DATABASES LIKE 'certificate_db'");
    $certificateDbExists = $stmt->rowCount() > 0;
    
    // Check if certificate_system exists
    $stmt = $conn->query("SHOW DATABASES LIKE 'certificate_system'");
    $certificateSystemExists = $stmt->rowCount() > 0;
    
    if ($certificateDbExists) {
        echo "<div style='color:green;'>Found database: certificate_db</div>";
        
        // Update config to use this database
        $dbToUse = 'certificate_db';
    } elseif ($certificateSystemExists) {
        echo "<div style='color:green;'>Found database: certificate_system</div>";
        
        // Update config to use this database
        $dbToUse = 'certificate_system';
    } else {
        echo "<div style='color:blue;'>No certificate database found. Creating new database...</div>";
        
        // Create new database
        $conn->exec("CREATE DATABASE certificate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<div style='color:green;'>Created new database: certificate_db</div>";
        $dbToUse = 'certificate_db';
    }
    
    // Now create the db_config.php file
    $configDir = __DIR__ . '/secure_config';
    if (!file_exists($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    $configFile = $configDir . '/db_config.php';
    
    // Backup existing config if it exists
    if (file_exists($configFile)) {
        copy($configFile, $configFile . '.bak.' . date('YmdHis'));
    }
    
    // Create the config content
    $configContent = "<?php
/**
 * Secure Database Credentials
 * This file should be placed outside the web root in production
 */

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', '$dbToUse');
define('DB_USER', 'root');
define('DB_PASS', '');

// Additional security settings
define('HASH_SECRET', '8b7df143d91c716ecfa5fc1730022f6b'); // Random secret for hashing
define('ENCRYPTION_KEY', '4d6783e91af4e3dfa5fc1720022f6ab'); // For encrypting sensitive data
?>";
    
    file_put_contents($configFile, $configContent);
    echo "<div style='color:green;'>Created database configuration file with correct settings.</div>";
    
    // Connect to the specific database and check tables
    $dbConn = new PDO("mysql:host=localhost;dbname=$dbToUse", "root", "");
    $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get list of tables
    $stmt = $dbConn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables in $dbToUse</h2>";
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<div style='color:orange;'>No tables found in database. Initial schema will be created.</div>";
        
        // Create basic tables
        $queries = [
            "CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                role ENUM('admin', 'manager', 'operator') NOT NULL DEFAULT 'operator',
                failed_login_attempts INT NOT NULL DEFAULT 0,
                last_failed_login INT NULL,
                account_locked TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS certificates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                certificate_id VARCHAR(50) NOT NULL UNIQUE,
                recipient_name VARCHAR(255) NOT NULL,
                course_name VARCHAR(255) NOT NULL,
                issue_date DATE NOT NULL,
                expiry_date DATE NULL,
                certificate_hash VARCHAR(64) NOT NULL,
                signature TEXT NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES admins(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS certificate_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                certificate_id VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) NULL,
                verification_result TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY idx_certificate_id (certificate_id),
                FOREIGN KEY (certificate_id) REFERENCES certificates(certificate_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS certificate_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_name VARCHAR(100) NOT NULL,
                template_html TEXT NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES admins(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(255) NOT NULL,
                action VARCHAR(50) NOT NULL,
                timestamp INT NOT NULL,
                ip VARCHAR(45) NOT NULL,
                KEY idx_identifier_action (identifier, action),
                KEY idx_timestamp (timestamp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            "CREATE TABLE IF NOT EXISTS api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                api_key VARCHAR(64) NOT NULL UNIQUE,
                description VARCHAR(255) NULL,
                created_by INT NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                last_used TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                FOREIGN KEY (created_by) REFERENCES admins(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Create a default admin user
            "INSERT INTO admins (username, password, email, role) VALUES 
            ('admin', '$2y$10$91MUnMJL9/l4JaURdyTAquvoGvEFIHdfGmQxXeON3VT9fL.FJ6m7.', 'admin@example.com', 'admin')
            ON DUPLICATE KEY UPDATE id=id;"
        ];
        
        $dbConn->beginTransaction();
        try {
            foreach ($queries as $query) {
                $dbConn->exec($query);
            }
            $dbConn->commit();
            echo "<div style='color:green;'>Successfully created initial database schema.</div>";
            echo "<div style='color:blue;'>Default admin user created:</div>";
            echo "<div><strong>Username:</strong> admin</div>";
            echo "<div><strong>Password:</strong> Admin123!</div>";
        } catch (PDOException $e) {
            $dbConn->rollBack();
            echo "<div style='color:red;'>Error creating database schema: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>Database Setup Complete</h2>";
    echo "<div style='margin-top:20px;'>";
    echo "<a href='admin/index.php' style='background-color:#4CAF50; color:white; padding:10px 15px; text-decoration:none; display:inline-block; border-radius:4px;'>Go to Admin Panel</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;font-weight:bold;'>Database connection failed: " . $e->getMessage() . "</div>";
    echo "<p>This could be due to:</p>";
    echo "<ul>";
    echo "<li>MySQL service not running properly</li>";
    echo "<li>Incorrect database credentials</li>";
    echo "<li>Permissions issues</li>";
    echo "</ul>";
}
?>
