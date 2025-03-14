<?php
/**
 * Database Table Repair Script
 * This script checks and creates missing tables
 */

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Table Repair Tool</h1>";

// Include database configuration
require_once 'secure_config/db_config.php';

try {
    // Connect to the database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p style='color:green'>Connected to database successfully.</p>";
    
    // Define all required tables
    $requiredTables = [
        'admins' => "CREATE TABLE admins (
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
        )",
        
        'certificates' => "CREATE TABLE certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            certificate_id VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            course VARCHAR(100) NOT NULL,
            issue_date DATE NOT NULL,
            expiry_date DATE NULL,
            status ENUM('active', 'revoked', 'expired') DEFAULT 'active',
            qr_code VARCHAR(255) NULL,
            admin_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
        )",
        
        'verification_logs' => "CREATE TABLE verification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            certificate_id VARCHAR(50) NOT NULL,
            verification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            verification_status ENUM('success', 'failed') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        'certificate_templates' => "CREATE TABLE certificate_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(100) NOT NULL,
            template_html TEXT NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
        )"
    ];
    
    // Check each required table
    foreach ($requiredTables as $tableName => $createStatement) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$tableName' exists.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Table '$tableName' does not exist. Creating...</p>";
            
            try {
                $pdo->exec($createStatement);
                echo "<p style='color:green'>✅ Successfully created table '$tableName'.</p>";
                
                // Insert default admin if this is the admins table
                if ($tableName === 'admins') {
                    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM admins WHERE username = 'admin'");
                    if ($checkAdmin->fetchColumn() == 0) {
                        // Insert default admin (admin/admin123)
                        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role, name) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            'admin', 
                            'admin@example.com', 
                            password_hash('admin123', PASSWORD_DEFAULT), 
                            'admin',
                            'Administrator'
                        ]);
                        echo "<p style='color:green'>✅ Created default admin user (admin/admin123).</p>";
                    }
                }
                
                // Insert default template if this is the certificate_templates table
                if ($tableName === 'certificate_templates') {
                    $checkTemplate = $pdo->query("SELECT COUNT(*) FROM certificate_templates WHERE is_default = 1");
                    if ($checkTemplate->fetchColumn() == 0) {
                        // Insert default template
                        $defaultTemplate = '<div class="certificate-container">
                            <div class="certificate-header">
                                <h1>Certificate of Completion</h1>
                            </div>
                            <div class="certificate-body">
                                <p>This certifies that</p>
                                <h2>{{name}}</h2>
                                <p>has successfully completed the course</p>
                                <h3>{{course}}</h3>
                                <p>on {{issue_date}}</p>
                            </div>
                            <div class="certificate-footer">
                                <div class="signature">
                                    <img src="{{signature}}" alt="Signature">
                                    <p>Authorized Signature</p>
                                </div>
                                <div class="certificate-id">
                                    <p>Certificate ID: {{certificate_id}}</p>
                                </div>
                            </div>
                        </div>';
                        
                        $stmt = $pdo->prepare("INSERT INTO certificate_templates (template_name, template_html, is_default) VALUES (?, ?, ?)");
                        $stmt->execute(['Default Template', $defaultTemplate, 1]);
                        echo "<p style='color:green'>✅ Created default certificate template.</p>";
                    }
                }
                
            } catch (PDOException $e) {
                echo "<p style='color:red'>❌ Failed to create table '$tableName': " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Check for any foreign key issues and fix them
    echo "<h2>Checking Foreign Key Relationships</h2>";
    
    try {
        // Check certificates -> admins foreign key
        $result = $pdo->query("
            SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = '" . DB_NAME . "' 
            AND TABLE_NAME = 'certificates' 
            AND REFERENCED_TABLE_NAME = 'admins'
        ")->fetchColumn();
        
        if ($result > 0) {
            echo "<p>✅ Foreign key from certificates to admins is properly set up.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Foreign key from certificates to admins is missing. Attempting to repair...</p>";
            
            // This is a complex operation - would need to check table structure first
            echo "<p>This requires database structure modification. Please run the full installation script.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking foreign keys: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
    
    if (strpos($e->getMessage(), "Unknown database")) {
        echo "<p>The database '" . DB_NAME . "' does not exist. Please run the database installation script:</p>";
        echo "<p><a href='install_database.php'>Run Database Installation</a></p>";
    }
}

// Provide links to other useful pages
echo "<h2>Next Steps</h2>";
echo "<p><a href='install_database.php' style='margin-right:15px'>Run Full Database Installation</a>";
echo "<a href='db_diagnostic.php' style='margin-right:15px'>Run Diagnostics</a>";
echo "<a href='admin/login.php' style='margin-right:15px'>Admin Login</a>";
echo "<a href='index.php'>Home Page</a></p>";
?> 