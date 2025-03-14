<?php
// Set error reporting to display all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include QR code library
require_once 'vendor/phpqrcode/qrlib.php';

// Test QR code generation
echo "<h1>QR Code Generation Test</h1>";

// Create test directory if it doesn't exist
if (!file_exists('uploads/test')) {
    mkdir('uploads/test', 0755, true);
}

// Generate a test QR code
$testText = 'http://localhost/certificate/verify?id=test123';
$testFile = 'uploads/test/test_qrcode.png';

echo "<p>Attempting to generate QR code for: " . htmlspecialchars($testText) . "</p>";

try {
    $result = QRcode::png($testText, $testFile);
    
    if ($result) {
        echo "<p style='color:green;'>QR code generated successfully at: " . htmlspecialchars($testFile) . "</p>";
        echo "<p><img src='" . htmlspecialchars($testFile) . "' alt='Test QR Code'></p>";
    } else {
        echo "<p style='color:red;'>Failed to generate QR code.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
