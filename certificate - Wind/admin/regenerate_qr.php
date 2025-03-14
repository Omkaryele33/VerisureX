<?php
/**
 * Regenerate QR Code for a Certificate
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if certificate ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Certificate ID is required');
    redirect('certificates.php');
}

$certificateId = (int)$_GET['id'];

// Get certificate details
$query = "SELECT id, certificate_id FROM certificates WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $certificateId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('error', 'Certificate not found');
    redirect('certificates.php');
}

$certificate = $stmt->fetch(PDO::FETCH_ASSOC);

// Regenerate QR code
$qrCodePath = generateQRCode($certificate['certificate_id']);

if ($qrCodePath === false) {
    setFlashMessage('error', 'Failed to regenerate QR code. Check error logs for more details.');
    redirect('view_certificate.php?id=' . $certificateId);
}

// Update certificate with new QR code path
$updateQuery = "UPDATE certificates SET qr_code_path = :qr_code_path WHERE id = :id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(':qr_code_path', $qrCodePath);
$updateStmt->bindParam(':id', $certificateId);

if ($updateStmt->execute()) {
    setFlashMessage('success', 'QR code regenerated successfully');
} else {
    setFlashMessage('warning', 'QR code was generated but database record could not be updated');
}

// Redirect back to certificate view
redirect('view_certificate.php?id=' . $certificateId);
