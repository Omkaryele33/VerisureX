<?php
/**
 * Admin Panel - Main Page (Fixed Version)
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();

// Include security constants
require_once 'security_header.php';
}

// Define required constants
if (!defined('VERIFY_RATE_LIMIT')) define('VERIFY_RATE_LIMIT', 20);
if (!defined('VERIFY_RATE_WINDOW')) define('VERIFY_RATE_WINDOW', 300);
if (!defined('API_RATE_LIMIT')) define('API_RATE_LIMIT', 100);
if (!defined('API_RATE_WINDOW')) define('API_RATE_WINDOW', 60);
if (!defined('ENABLE_DIGITAL_SIGNATURES')) define('ENABLE_DIGITAL_SIGNATURES', true);
if (!defined('SIGNATURE_ALGORITHM')) define('SIGNATURE_ALGORITHM', 'sha256WithRSAEncryption');
if (!defined('PRIVATE_KEY_PATH')) define('PRIVATE_KEY_PATH', dirname(__DIR__) . '/secure_config/private.key');
if (!defined('PUBLIC_KEY_PATH')) define('PUBLIC_KEY_PATH', dirname(__DIR__) . '/secure_config/public.key');

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Function aliases for backward compatibility
if (!function_exists('isEnhancedRateLimited') && function_exists('enhancedRateLimiting')) {
    function isEnhancedRateLimited($action, $ip, $userId = null) {
        return enhancedRateLimiting($action, $ip, $userId);
    }
}

// Connect to database
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get admin information
try {
    $query = "SELECT username, role FROM admins WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error retrieving admin information: ' . $e->getMessage());
}

// Get certificate count
try {
    $query = "SELECT COUNT(*) as total FROM certificates";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $certificateCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $certificateCount = 0;
    error_log('Error counting certificates: ' . $e->getMessage());
}

// Get recent certificates
try {
    $query = "SELECT c.id, c.certificate_id, c.recipient_name as full_name, c.created_at, a.username 
            FROM certificates c 
            JOIN admins a ON c.created_by = a.id 
            ORDER BY c.created_at DESC 
            LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentCertificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentCertificates = [];
    error_log('Error retrieving recent certificates: ' . $e->getMessage());
}

// Get recent verifications - handle table name difference gracefully
try {
    // First try with certificate_verifications table
    $verificationTable = 'certificate_verifications';
    
    // Check if the table exists
    $stmt = $db->query("SHOW TABLES LIKE '$verificationTable'");
    if ($stmt->rowCount() == 0) {
        // Fall back to verification_logs if certificate_verifications doesn't exist
        $verificationTable = 'verification_logs';
    }
    
    $query = "SELECT v.id, v.certificate_id, v.ip_address, v.created_at as verified_at, c.recipient_name as full_name 
            FROM $verificationTable v 
            JOIN certificates c ON v.certificate_id = c.certificate_id 
            ORDER BY v.created_at DESC 
            LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recentVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentVerifications = [];
    error_log('Error retrieving recent verifications: ' . $e->getMessage());
}

// Page title
$pageTitle = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="create_certificate.php" class="btn btn-sm btn-outline-primary">Create Certificate</a>
                            <a href="bulk_certificates.php" class="btn btn-sm btn-outline-secondary">Bulk Generation</a>
                        </div>
                    </div>
                </div>
                
                <!-- Status Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Certificates</h5>
                                <p class="card-text display-6"><?php echo $certificateCount; ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Additional stat cards can be placed here -->
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Certificates</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Certificate ID</th>
                                                <th>Recipient</th>
                                                <th>Created By</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentCertificates)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No certificates found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach($recentCertificates as $cert): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($cert['certificate_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($cert['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($cert['username']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($cert['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="certificates.php" class="btn btn-sm btn-primary mt-2">View All</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Verifications</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Certificate ID</th>
                                                <th>Recipient</th>
                                                <th>IP Address</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recentVerifications)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No verifications found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach($recentVerifications as $verification): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($verification['certificate_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($verification['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($verification['ip_address']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($verification['verified_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="verification_logs.php" class="btn btn-sm btn-primary mt-2">View All</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
