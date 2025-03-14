<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if phpqrcode library exists
if (!file_exists('vendor/phpqrcode/qrlib.php')) {
    die("Error: phpqrcode library not found! Please ensure it's installed in the vendor directory.");
}

// Include required files
require_once 'config/config.php';
require_once 'vendor/phpqrcode/qrlib.php';

echo "<h1>QR Code Generation Diagnostic</h1>";

// Directory checks
echo "<h2>Directory Status:</h2>";
echo "<pre>";
echo "Upload Directory (UPLOAD_DIR): " . UPLOAD_DIR . "\n";
echo "QR Directory (QR_DIR): " . QR_DIR . "\n";
echo "Upload Directory exists: " . (file_exists(UPLOAD_DIR) ? "Yes" : "No") . "\n";
echo "QR Directory exists: " . (file_exists(QR_DIR) ? "Yes" : "No") . "\n";

// Create directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    echo "Creating upload directory...\n";
    $result = @mkdir(UPLOAD_DIR, 0777, true);
    echo "Result: " . ($result ? "Success" : "Failed") . "\n";
}

if (!file_exists(QR_DIR)) {
    echo "Creating QR directory...\n";
    $result = @mkdir(QR_DIR, 0777, true);
    echo "Result: " . ($result ? "Success" : "Failed") . "\n";
}

// Test QR code generation
$testId = 'test_' . time();
$qrPath = 'qrcodes/' . $testId . '.png';
$fullPath = UPLOAD_DIR . $qrPath;
$verificationUrl = VERIFY_URL . '?id=' . $testId;

echo "\nTesting QR Code Generation:\n";
echo "Full Path: " . $fullPath . "\n";
echo "Verification URL: " . $verificationUrl . "\n";

try {
    // Check if directory is writable
    echo "QR Directory writable: " . (is_writable(QR_DIR) ? "Yes" : "No") . "\n";
    
    // Try to create a test file
    $testFile = QR_DIR . 'test.txt';
    $writeTest = @file_put_contents($testFile, 'test');
    echo "Write test result: " . ($writeTest !== false ? "Success" : "Failed") . "\n";
    if (file_exists($testFile)) {
        unlink($testFile);
    }
    
    // Generate QR code
    echo "Attempting to generate QR code...\n";
    QRcode::png($verificationUrl, $fullPath);
    
    if (file_exists($fullPath)) {
        echo "</pre>";
        echo "<div style='background: #dff0d8; color: #3c763d; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
        echo "<p>QR code successfully generated!</p>";
        echo "<img src='uploads/" . $qrPath . "' style='max-width: 200px; border: 1px solid #ccc; padding: 10px;'>";
        echo "</div>";
    } else {
        echo "</pre>";
        echo "<div style='background: #f2dede; color: #a94442; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
        echo "<p>QR code generation failed: File not created</p>";
        echo "<p>Please ensure the web server has write permissions to the uploads directory.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "</pre>";
    echo "<div style='background: #f2dede; color: #a94442; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>