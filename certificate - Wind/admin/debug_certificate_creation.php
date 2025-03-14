<?php
/**
 * Debug Certificate Creation Process
 * This script tests each step of the certificate creation process to identify issues
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../vendor/phpqrcode/qrlib.php';

echo "<h1>Certificate Creation Debug</h1>";

// Step 1: Test database connection
echo "<h2>Step 1: Testing Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color:green;'>Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 2: Test UUID generation
echo "<h2>Step 2: Testing UUID Generation</h2>";
$uuid = generateUUID();
echo "<p>Generated UUID: " . htmlspecialchars($uuid) . "</p>";

// Step 3: Test directory permissions
echo "<h2>Step 3: Testing Directory Permissions</h2>";
$directories = [
    UPLOAD_DIR,
    UPLOAD_DIR . '/photos',
    UPLOAD_DIR . '/qrcodes'
];

foreach ($directories as $dir) {
    echo "<p>Checking directory: " . htmlspecialchars($dir) . "</p>";
    
    if (!file_exists($dir)) {
        echo "<p style='color:orange;'>Directory does not exist. Attempting to create...</p>";
        
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color:green;'>Directory created successfully</p>";
        } else {
            echo "<p style='color:red;'>Failed to create directory</p>";
        }
    } else {
        echo "<p style='color:green;'>Directory exists</p>";
        
        // Check if writable
        if (is_writable($dir)) {
            echo "<p style='color:green;'>Directory is writable</p>";
        } else {
            echo "<p style='color:red;'>Directory is not writable</p>";
        }
    }
}

// Step 4: Test QR code generation
echo "<h2>Step 4: Testing QR Code Generation</h2>";
$qrText = 'http://localhost/certificate/verify?id=' . $uuid;
$qrFile = UPLOAD_DIR . '/qrcodes/test_' . $uuid . '.png';

echo "<p>Generating QR code for: " . htmlspecialchars($qrText) . "</p>";
echo "<p>QR code file path: " . htmlspecialchars($qrFile) . "</p>";

try {
    $qrResult = QRcode::png($qrText, $qrFile);
    
    if (file_exists($qrFile)) {
        echo "<p style='color:green;'>QR code generated successfully</p>";
        echo "<p><img src='/certificate/uploads/qrcodes/test_" . htmlspecialchars($uuid) . ".png' alt='Test QR Code'></p>";
    } else {
        echo "<p style='color:red;'>QR code file was not created</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>QR code generation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Step 5: Test certificate creation in database
echo "<h2>Step 5: Testing Certificate Database Entry</h2>";

try {
    // Generate test data
    $fullName = "Test Student";
    $dateOfBirth = "2000-01-01";
    $certificateContent = "This is a test certificate";
    $photoPath = "test_photo.jpg";
    $qrCodePath = "test_" . $uuid . ".png";
    
    // Insert test certificate
    $query = "INSERT INTO certificates (certificate_id, full_name, date_of_birth, certificate_content, photo_path, qr_code_path, created_at) 
              VALUES (:certificate_id, :full_name, :date_of_birth, :certificate_content, :photo_path, :qr_code_path, NOW())";
              
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':certificate_id', $uuid);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':date_of_birth', $dateOfBirth);
    $stmt->bindParam(':certificate_content', $certificateContent);
    $stmt->bindParam(':photo_path', $photoPath);
    $stmt->bindParam(':qr_code_path', $qrCodePath);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>Test certificate created successfully in database</p>";
        
        // Clean up the test entry
        $deleteQuery = "DELETE FROM certificates WHERE certificate_id = :certificate_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':certificate_id', $uuid);
        $deleteStmt->execute();
        
        echo "<p>Test certificate entry removed from database</p>";
    } else {
        echo "<p style='color:red;'>Failed to create test certificate in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Database operation failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Testing Complete</h2>";
echo "<p><a href='/certificate/admin/create_certificate.php'>Return to Create Certificate Page</a></p>";
?>
