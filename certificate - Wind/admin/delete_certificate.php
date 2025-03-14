<?php
/**
 * Admin Panel - Delete Certificate
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Certificate ID is required');
    redirect('certificates.php');
}

// Get certificate ID
$id = (int)$_GET['id'];

// Verify certificate exists
$query = "SELECT * FROM certificates WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

// Check if certificate exists
if ($stmt->rowCount() === 0) {
    setFlashMessage('error', 'Certificate not found');
    redirect('certificates.php');
}

// Get certificate data
$certificate = $stmt->fetch(PDO::FETCH_ASSOC);

// Process deletion
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Delete related verification logs if they exist
        $deleteLogsQuery = "DELETE FROM verification_logs WHERE certificate_id = :certificate_id";
        $deleteLogsStmt = $db->prepare($deleteLogsQuery);
        $deleteLogsStmt->bindParam(':certificate_id', $certificate['certificate_id']);
        $deleteLogsStmt->execute();
        
        // Delete certificate
        $deleteCertQuery = "DELETE FROM certificates WHERE id = :id";
        $deleteCertStmt = $db->prepare($deleteCertQuery);
        $deleteCertStmt->bindParam(':id', $id);
        $deleteCertStmt->execute();
        
        // Commit transaction
        $db->commit();
        
        // Delete associated files
        if (!empty($certificate['photo_path'])) {
            $photoFullPath = UPLOAD_DIR . $certificate['photo_path'];
            if (file_exists($photoFullPath)) {
                unlink($photoFullPath);
            }
        }
        
        if (!empty($certificate['qr_code_path'])) {
            $qrCodeFullPath = UPLOAD_DIR . $certificate['qr_code_path'];
            if (file_exists($qrCodeFullPath)) {
                unlink($qrCodeFullPath);
            }
        }
        
        // Success message
        setFlashMessage('success', 'Certificate deleted successfully');
        redirect('certificates.php');
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        setFlashMessage('error', 'Failed to delete certificate: ' . $e->getMessage());
        redirect('certificates.php');
    }
} else {
    // Show confirmation page
    $pageTitle = "Delete Certificate";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Certificate - Certificate Validation System</title>
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
                    <h1 class="h2">Delete Certificate</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="certificates.php" class="btn btn-sm btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        <a href="view_certificate.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-eye"></i> View Certificate
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-danger">
                    <h4 class="alert-heading">Warning!</h4>
                    <p>You are about to delete the certificate for <strong><?php echo htmlspecialchars($certificate['full_name']); ?></strong> (ID: <?php echo htmlspecialchars($certificate['certificate_id']); ?>).</p>
                    <p>This action cannot be undone and will permanently remove the certificate from the system.</p>
                    <hr>
                    <p class="mb-0">All associated data, including QR code, photos, and verification logs will also be deleted.</p>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Confirm Deletion</h5>
                    </div>
                    <div class="card-body">
                        <form action="delete_certificate.php?id=<?php echo $id; ?>" method="post">
                            <input type="hidden" name="confirm_delete" value="yes">
                            <p>Type <strong>DELETE</strong> in the box below to confirm:</p>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="confirm_text" required pattern="DELETE" oninvalid="this.setCustomValidity('Please type DELETE to confirm')" oninput="this.setCustomValidity('')">
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="view_certificate.php?id=<?php echo $id; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-danger">Delete Certificate</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
<?php
}
?>
