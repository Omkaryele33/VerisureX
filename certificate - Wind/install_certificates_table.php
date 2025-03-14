<?php
// Create certificates table
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Creating Certificates Table</h1>";

// Database settings
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'certificate_system';

try {
    // Connect to MySQL server
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p>Connected to database</p>";
    
    // Create certificates table
    $sql = "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        certificate_id VARCHAR(100) NOT NULL UNIQUE,
        template_id INT NOT NULL,
        holder_name VARCHAR(255) NOT NULL,
        course_name VARCHAR(255) NOT NULL,
        issue_date DATE NOT NULL,
        FOREIGN KEY (template_id) REFERENCES certificate_templates(template_id)
    ) ENGINE=InnoDB";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Certificates table created successfully</p>";
    } else {
        throw new Exception("Error creating certificates table: " . $conn->error);
    }
    
    // Create verification_logs table too if it doesn't exist
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
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>Verification logs table created successfully</p>";
    } else {
        throw new Exception("Error creating verification logs table: " . $conn->error);
    }
    
    // Add sample certificate data for testing
    $sql = "INSERT INTO certificates (certificate_id, template_id, holder_name, course_name, issue_date) VALUES
            ('CERT-2024-001', 1, 'John Doe', 'Web Development Fundamentals', '2024-01-15'),
            ('CERT-2024-002', 2, 'Jane Smith', 'Advanced Database Design', '2024-02-10'),
            ('CERT-2024-003', 3, 'Robert Johnson', 'Cybersecurity Basics', '2024-01-05'),
            ('CERT-2024-004', 4, 'Emily Brown', 'Mobile App Development', '2024-03-01'),
            ('CERT-2024-005', 5, 'Michael Williams', 'Cloud Computing', '2024-02-20')";
    
    // Only add sample data if the table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM certificates");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        if ($conn->query($sql) === TRUE) {
            echo "<p>Sample certificate data inserted successfully</p>";
        } else {
            echo "<p>Warning: Could not insert sample data: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Database already contains certificates. No sample data added.</p>";
    }
    
    // Add sample verification data
    $sql = "INSERT INTO verification_logs (certificate_id, ip_address, user_agent, verification_result) VALUES
            ('CERT-2024-001', '192.168.1.1', 'Mozilla/5.0', 'valid'),
            ('CERT-2024-002', '192.168.1.2', 'Mozilla/5.0', 'valid'),
            ('CERT-2024-003', '192.168.1.3', 'Chrome/90.0', 'revoked'),
            ('CERT-2024-004', '192.168.1.4', 'Chrome/91.0', 'valid'),
            ('CERT-2024-001', '192.168.1.5', 'Firefox/88.0', 'valid')";
    
    // Only add sample verification data if the table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM verification_logs");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        if ($conn->query($sql) === TRUE) {
            echo "<p>Sample verification data inserted successfully</p>";
        } else {
            echo "<p>Warning: Could not insert sample verification data: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Database already contains verification logs. No sample data added.</p>";
    }
    
    // Close connection
    $conn->close();
    
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #d4edda; color: #155724; border-radius: 5px;'>";
    echo "<h2>All Done!</h2>";
    echo "<p>The certificates and verification_logs tables have been created successfully.</p>";
    echo "<p>You should now be able to view the dashboard.</p>";
    echo "<p><a href='/certificate/admin/dashboard.php' style='display: inline-block; margin-top: 10px; padding: 8px 16px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px;'>Go to Dashboard</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8d7da; color: #721c24; border-radius: 5px;'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
