<?php
/**
 * Repair QR Codes for Existing Certificates
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../vendor/phpqrcode/qrlib.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Create QR code directory if it doesn't exist
if (!file_exists(QR_DIR)) {
    mkdir(QR_DIR, 0755, true);
}

// Get all certificates
$query = "SELECT id, certificate_id, qr_code_path FROM certificates";
$stmt = $db->prepare($query);
$stmt->execute();

// Process each certificate
$success = 0;
$failures = 0;
$results = [];

echo "<h1>QR Code Repair Tool</h1>";
echo "<p>This tool will regenerate QR codes for all certificates in the database.</p>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Set up paths
    $certificateId = $row['certificate_id'];
    $qrCodePath = 'qrcodes/' . $certificateId . '.png';
    $qrCodeFullPath = UPLOAD_DIR . $qrCodePath;
    
    // Generate the QR code with the verification URL
    $verificationUrl = VERIFY_URL . '?id=' . $certificateId;
    
    echo "<hr>";
    echo "<h3>Processing Certificate ID: {$certificateId}</h3>";
    echo "<p>Verification URL: {$verificationUrl}</p>";
    
    try {
        // Generate the QR code
        if (QRcode::png($verificationUrl, $qrCodeFullPath)) {
            echo "<p style='color:green'>QR code successfully generated!</p>";
            
            // Update database with new path if different
            if ($qrCodePath != $row['qr_code_path']) {
                $updateQuery = "UPDATE certificates SET qr_code_path = :qr_code_path WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':qr_code_path', $qrCodePath);
                $updateStmt->bindParam(':id', $row['id']);
                
                if ($updateStmt->execute()) {
                    echo "<p style='color:green'>Database record updated!</p>";
                } else {
                    echo "<p style='color:orange'>Database update failed, but QR code was generated.</p>";
                }
            }
            
            // Show the QR code
            echo "<div style='margin-bottom: 20px;'>";
            echo "<img src='../uploads/{$qrCodePath}?t=" . time() . "' alt='QR Code' style='border: 1px solid #ccc; padding: 10px; max-width: 200px;'>";
            echo "<p><a href='../uploads/{$qrCodePath}' download class='btn' style='display: inline-block; padding: 8px 16px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 10px;'>Download QR Code</a></p>";
            echo "</div>";
            
            $success++;
            $results[] = [
                'certificate_id' => $certificateId,
                'success' => true,
                'path' => $qrCodePath
            ];
        } else {
            echo "<p style='color:red'>Failed to generate QR code!</p>";
            $failures++;
            $results[] = [
                'certificate_id' => $certificateId,
                'success' => false,
                'error' => 'QR code generation failed'
            ];
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
        $failures++;
        $results[] = [
            'certificate_id' => $certificateId,
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Show summary
echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>Total certificates processed: " . ($success + $failures) . "</p>";
echo "<p style='color:green'>Successful QR codes: {$success}</p>";
echo "<p style='color:red'>Failed QR codes: {$failures}</p>";

// Add link to return to admin
echo "<p><a href='index.php' class='btn' style='display: inline-block; padding: 8px 16px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 20px;'>Return to Admin Panel</a></p>";
?>
