<?php
/**
 * Database Installation Script
 * This script automatically creates all tables defined in the schema files
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to MySQL without selecting a database
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // First connect without a database to check if the database exists
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Installation Tool</h2>";
    
    // Check if database exists, create if it doesn't
    $stmt = $pdo->query("SHOW DATABASES LIKE 'certificate_system'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE DATABASE certificate_system");
        echo "<p>✅ Database 'certificate_system' created.</p>";
    } else {
        echo "<p>✅ Database 'certificate_system' already exists.</p>";
    }
    
    // Connect to the certificate_system database
    $pdo = new PDO("mysql:host=$host;dbname=certificate_system", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admins table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
        name VARCHAR(100) NULL,
        password_change_required TINYINT(1) DEFAULT 0,
        account_status VARCHAR(20) DEFAULT 'active',
        login_attempts INT DEFAULT 0,
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'admins' created or already exists.</p>";
    
    // Create users view (link to admins table) if it doesn't exist
    try {
        // First drop the view if it exists to avoid conflicts
        $pdo->exec("DROP VIEW IF EXISTS users");
        
        // Check if a users table already exists instead of a view
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            // Drop the users table if it exists
            $pdo->exec("DROP TABLE IF EXISTS users");
            echo "<p>✅ Dropped existing 'users' table for replacement with view.</p>";
        }
        
        // Create the users view
        $sql = "CREATE VIEW users AS SELECT * FROM admins";
        $pdo->exec($sql);
        echo "<p>✅ View 'users' created successfully (linked to admins table).</p>";
    } catch (PDOException $e) {
        echo "<p>❌ Error creating users view: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Create certificates table
    $sql = "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(100) NOT NULL UNIQUE,
        holder_name VARCHAR(255) NOT NULL,
        course_name VARCHAR(255) NOT NULL,
        issue_date DATE NOT NULL,
        expiry_date DATE,
        is_active TINYINT(1) DEFAULT 1,
        revocation_reason TEXT,
        certificate_image VARCHAR(255),
        verification_qr VARCHAR(255),
        additional_details TEXT,
        created_by INT,
        template_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (certificate_id),
        INDEX (holder_name),
        INDEX (course_name),
        INDEX (issue_date),
        FOREIGN KEY (created_by) REFERENCES admins(id),
        FOREIGN KEY (template_id) REFERENCES certificate_templates(template_id)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'certificates' created or already exists.</p>";
    
    // Create certificate_templates table
    $sql = "CREATE TABLE IF NOT EXISTS certificate_templates (
        template_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admins(id)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'certificate_templates' created</p>";
    
    // Create verification_logs table
    $sql = "CREATE TABLE IF NOT EXISTS verification_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(100) NOT NULL,
        ip_address VARCHAR(50) NOT NULL,
        user_agent TEXT,
        verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verification_result ENUM('valid', 'invalid', 'revoked') NOT NULL,
        INDEX (certificate_id),
        INDEX (verified_at)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'verification_logs' created or already exists.</p>";
    
    // Create security_logs table
    $sql = "CREATE TABLE IF NOT EXISTS security_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        event_type VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES admins(id)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'security_logs' created</p>";
    
    // Create api_keys table
    $sql = "CREATE TABLE IF NOT EXISTS api_keys (
        key_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        api_key VARCHAR(255) NOT NULL UNIQUE,
        permissions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES admins(id)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'api_keys' created</p>";
    
    // Create premium tables
    $sql = "CREATE TABLE IF NOT EXISTS premium_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(100) NOT NULL UNIQUE,
        holder_name VARCHAR(255) NOT NULL,
        course_name VARCHAR(255) NOT NULL,
        issue_date DATE NOT NULL,
        expiry_date DATE,
        is_active TINYINT(1) DEFAULT 1,
        revocation_reason TEXT,
        certificate_image VARCHAR(255),
        verification_qr VARCHAR(255),
        additional_details TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (certificate_id),
        INDEX (holder_name),
        INDEX (course_name),
        INDEX (issue_date)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'premium_certificates' created</p>";
    
    $sql = "CREATE TABLE IF NOT EXISTS premium_verification_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(100) NOT NULL,
        ip_address VARCHAR(50) NOT NULL,
        user_agent TEXT,
        verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verification_result ENUM('valid', 'invalid', 'revoked') NOT NULL,
        INDEX (certificate_id),
        INDEX (verified_at)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'premium_verification_logs' created</p>";
    
    $sql = "CREATE TABLE IF NOT EXISTS premium_api_keys (
        key_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        api_key VARCHAR(255) NOT NULL UNIQUE,
        permissions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES admins(id)
    )";
    $pdo->exec($sql);
    echo "<p>✅ Table 'premium_api_keys' created</p>";
    
    // Add admin user if the admins table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $username = 'admin';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@example.com';
        $name = 'System Administrator';
        
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, email, name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $name, 'admin']);
        echo "<p>✅ Default admin user created.</p>";
    } else {
        echo "<p>ℹ️ Admin users already exist. No new admin created.</p>";
    }
    
    // Add sample certificate data if certificates table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM certificates");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sql = "INSERT INTO certificates (certificate_id, holder_name, course_name, issue_date, expiry_date, is_active) VALUES
            ('CERT-2024-001', 'John Doe', 'Web Development Fundamentals', '2024-01-15', '2025-01-15', 1),
            ('CERT-2024-002', 'Jane Smith', 'Advanced Database Design', '2024-02-10', '2025-02-10', 1),
            ('CERT-2024-003', 'Robert Johnson', 'Cybersecurity Basics', '2024-01-05', '2025-01-05', 0),
            ('CERT-2024-004', 'Emily Brown', 'Mobile App Development', '2024-03-01', '2025-03-01', 1),
            ('CERT-2024-005', 'Michael Williams', 'Cloud Computing', '2024-02-20', '2025-02-20', 1)";
        $pdo->exec($sql);
        echo "<p>✅ Sample certificate data added.</p>";
    } else {
        echo "<p>ℹ️ Certificate data already exists. No sample data added.</p>";
    }
    
    // Add sample verification data if verification_logs table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM verification_logs");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sql = "INSERT INTO verification_logs (certificate_id, ip_address, user_agent, verification_result) VALUES
            ('CERT-2024-001', '192.168.1.1', 'Mozilla/5.0', 'valid'),
            ('CERT-2024-002', '192.168.1.2', 'Mozilla/5.0', 'valid'),
            ('CERT-2024-003', '192.168.1.3', 'Chrome/90.0', 'revoked'),
            ('CERT-2024-004', '192.168.1.4', 'Chrome/91.0', 'valid'),
            ('CERT-2024-001', '192.168.1.5', 'Firefox/88.0', 'valid')";
        $pdo->exec($sql);
        echo "<p>✅ Sample verification data added.</p>";
    } else {
        echo "<p>ℹ️ Verification data already exists. No sample data added.</p>";
    }
    
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px;'>";
    echo "<h2>Installation Completed Successfully</h2>";
    echo "<p>The database 'certificate_system' has been set up successfully with all necessary tables.</p>";
    echo "<p>You can now access the <a href='/certificate/admin/login.php'>login page</a> with the following credentials:</p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px;'>";
    echo "<h2>Installation Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installation - Certificate Validation System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        p {
            margin: 10px 0;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- PHP output goes here -->
</body>
</html>
