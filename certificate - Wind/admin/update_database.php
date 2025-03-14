<?php
/**
 * Database Structure Update Script
 * This script ensures all necessary database tables and columns exist
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Database Structure Update</h1>";

// Load database configuration
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "<div style='color:green'>✓ Connected to database successfully</div>";
} catch (Exception $e) {
    die("<div style='color:red'>Database connection error: " . $e->getMessage() . "</div>");
}

// Define necessary tables structure
$tables = [
    'admins' => "CREATE TABLE IF NOT EXISTS admins (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('admin', 'editor') NOT NULL DEFAULT 'admin',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        failed_login_attempts INT(11) NOT NULL DEFAULT 0,
        last_failed_login INT(11) DEFAULT NULL,
        account_locked TINYINT(1) NOT NULL DEFAULT 0,
        password_change_required TINYINT(1) NOT NULL DEFAULT 0,
        last_password_change DATETIME DEFAULT NULL,
        last_login DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (username),
        UNIQUE KEY (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'rate_limits' => "CREATE TABLE IF NOT EXISTS rate_limits (
        id INT(11) NOT NULL AUTO_INCREMENT,
        identifier VARCHAR(255) NOT NULL,
        action VARCHAR(50) NOT NULL,
        timestamp INT(11) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        PRIMARY KEY (id),
        KEY idx_identifier_action (identifier, action),
        KEY idx_timestamp (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'certificates' => "CREATE TABLE IF NOT EXISTS certificates (
        id INT(11) NOT NULL AUTO_INCREMENT,
        certificate_id VARCHAR(50) NOT NULL,
        template_id INT(11) NOT NULL,
        recipient_name VARCHAR(100) NOT NULL,
        recipient_email VARCHAR(100) DEFAULT NULL,
        issue_date DATE NOT NULL,
        expiry_date DATE DEFAULT NULL,
        custom_fields TEXT DEFAULT NULL,
        status ENUM('active', 'revoked', 'expired') NOT NULL DEFAULT 'active',
        revocation_reason TEXT DEFAULT NULL,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (certificate_id),
        KEY idx_template_id (template_id),
        KEY idx_recipient (recipient_name, recipient_email),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'templates' => "CREATE TABLE IF NOT EXISTS templates (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        template_html TEXT NOT NULL,
        created_by INT(11) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    'certificate_verifications' => "CREATE TABLE IF NOT EXISTS certificate_verifications (
        id INT(11) NOT NULL AUTO_INCREMENT,
        certificate_id VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verification_result TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_certificate_id (certificate_id),
        KEY idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// Execute table creation queries
foreach ($tables as $table => $query) {
    try {
        $db->exec($query);
        echo "<div>✓ Table '$table' structure verified</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red'>Error creating '$table' table: " . $e->getMessage() . "</div>";
    }
}

// Check if admin user exists and create if not
$adminQuery = "SELECT id FROM admins WHERE username = 'admin'";
$stmt = $db->query($adminQuery);

if ($stmt->rowCount() == 0) {
    // Create admin user with default password '123'
    $passwordHash = password_hash('123', PASSWORD_DEFAULT);
    $insertQuery = "INSERT INTO admins (username, password, email, role) VALUES ('admin', :password, 'admin@example.com', 'admin')";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':password', $passwordHash);
    
    try {
        $insertStmt->execute();
        echo "<div style='color:green'>✓ Admin user created with username 'admin' and password '123'</div>";
    } catch (PDOException $e) {
        echo "<div style='color:red'>Error creating admin user: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div>✓ Admin user already exists</div>";
    
    // Ensure admin user has the right password format
    $updateQuery = "UPDATE admins SET password = :password WHERE username = 'admin' AND password != :password_check";
    $passwordHash = password_hash('123', PASSWORD_DEFAULT);
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $passwordHash);
    $updateStmt->bindParam(':password_check', $passwordHash);
    
    try {
        $updateStmt->execute();
        if ($updateStmt->rowCount() > 0) {
            echo "<div style='color:green'>✓ Admin password updated to '123'</div>";
        }
    } catch (PDOException $e) {
        echo "<div style='color:red'>Error updating admin password: " . $e->getMessage() . "</div>";
    }
}

echo "<div style='margin-top:20px;padding:10px;background-color:#d4edda;color:#155724;border-radius:5px;'>
    <strong>Database update completed successfully!</strong><br>
    You can now <a href='login.php' style='color:#155724;font-weight:bold;'>login to the admin panel</a> with username 'admin' and password '123'.
</div>";
?>
