<?php
/**
 * Debug Certificate Creation to fix foreign key constraint issues
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Certificate Creation</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Get first admin ID
    $stmt = $db->query("SELECT id FROM admins LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        echo "<p style='color:red'>Error: No admin users found. Please create an admin user first.</p>";
        exit;
    }
    
    $adminId = $admin['id'];
    echo "<p>Using admin ID: {$adminId} for testing</p>";
    
    // Generate test data
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $certificateNumber = "TEST-" . date('YmdHis');
    $fullName = "Test User";
    $courseName = "Test Course";
    $issueDate = date('Y-m-d');
    
    echo "<h2>Test Certificate Data:</h2>";
    echo "<ul>";
    echo "<li><strong>Certificate ID:</strong> {$uuid}</li>";
    echo "<li><strong>Certificate Number:</strong> {$certificateNumber}</li>";
    echo "<li><strong>Full Name:</strong> {$fullName}</li>";
    echo "<li><strong>Course Name:</strong> {$courseName}</li>";
    echo "<li><strong>Issue Date:</strong> {$issueDate}</li>";
    echo "<li><strong>Created By (Admin ID):</strong> {$adminId}</li>";
    echo "</ul>";
    
    // Prepare insert statement with all required fields
    $stmt = $db->prepare("
        INSERT INTO certificates (
            certificate_id, 
            certificate_number, 
            full_name,
            certificate_content,
            is_active,
            created_by,
            holder_name,
            course_name,
            issue_date
        ) VALUES (
            :certificate_id,
            :certificate_number,
            :full_name,
            :certificate_content,
            1,
            :created_by,
            :holder_name,
            :course_name,
            :issue_date
        )
    ");
    
    // Bind parameters
    $certificateContent = "<p>This is a test certificate for {$fullName}.</p>";
    
    $stmt->bindParam(':certificate_id', $uuid);
    $stmt->bindParam(':certificate_number', $certificateNumber);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':certificate_content', $certificateContent);
    $stmt->bindParam(':created_by', $adminId, PDO::PARAM_INT);
    $stmt->bindParam(':holder_name', $fullName);
    $stmt->bindParam(':course_name', $courseName);
    $stmt->bindParam(':issue_date', $issueDate);
    
    // Execute statement
    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        echo "<p style='color:green'>✓ Test certificate created successfully with ID: {$newId}</p>";
        
        // Verify the entry
        $verifyStmt = $db->prepare("SELECT * FROM certificates WHERE id = :id");
        $verifyStmt->bindParam(':id', $newId);
        $verifyStmt->execute();
        $certificate = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Created Certificate Data:</h3>";
        echo "<table border='1' cellpadding='5'>";
        foreach ($certificate as $key => $value) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>The test certificate was created without any foreign key constraint issues.</p>";
        
        // Clean up - remove test certificate
        $deleteStmt = $db->prepare("DELETE FROM certificates WHERE id = :id");
        $deleteStmt->bindParam(':id', $newId);
        $deleteStmt->execute();
        echo "<p>Test certificate has been removed.</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create test certificate:</p>";
        echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
    }
    
    echo "<p><strong>Based on these results:</strong></p>";
    echo "<ol>";
    echo "<li>If the test certificate was created successfully, the issue might be in your application code when inserting certificates.</li>";
    echo "<li>Specifically, ensure the <code>created_by</code> field is being properly set to a valid admin ID (e.g., {$adminId}).</li>";
    echo "<li>Check any existing custom insert/update statements in your application's certificate creation forms and processes.</li>";
    echo "</ol>";
    
    echo "<p><a href='admin/create_certificate.php'>Go to Certificate Creation Page</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 