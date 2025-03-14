<?php
/**
 * Admin Panel - Edit Certificate
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once 'master_init.php';

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

// Initialize variables
$errors = [];
$success = false;
$certificate = [];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Certificate ID is required');
    redirect('certificates.php');
}

// Get certificate ID
$id = (int)$_GET['id'];

// Get certificate details
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $fullName = trim($_POST['full_name'] ?? '');
    $issueDate = trim($_POST['issue_date'] ?? '');
    $passDate = trim($_POST['pass_date'] ?? '');
    $certificateNumber = trim($_POST['certificate_number'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $branchName = trim($_POST['branch_name'] ?? '');
    $validationStatus = isset($_POST['validation_status']) ? 1 : 0;
    $certificateContent = trim($_POST['certificate_content'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Check for errors
    if (empty($fullName)) {
        $errors[] = 'Full name is required';
    }

    if (empty($issueDate)) {
        $errors[] = 'Issue date is required';
    }

    if (empty($certificateContent)) {
        $errors[] = 'Certificate content is required';
    }

    // If no errors, update certificate
    if (empty($errors)) {
        // Update certificate in database
        $query = "UPDATE certificates SET 
                 full_name = :full_name,
                 issue_date = :issue_date,
                 pass_date = :pass_date,
                 certificate_number = :certificate_number,
                 grade = :grade,
                 branch_name = :branch_name,
                 validation_status = :validation_status,
                 certificate_content = :certificate_content,
                 is_active = :is_active,
                 updated_at = NOW()
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':issue_date', $issueDate);
        $stmt->bindParam(':pass_date', $passDate);
        $stmt->bindParam(':certificate_number', $certificateNumber);
        $stmt->bindParam(':grade', $grade);
        $stmt->bindParam(':branch_name', $branchName);
        $stmt->bindParam(':validation_status', $validationStatus);
        $stmt->bindParam(':certificate_content', $certificateContent);
        $stmt->bindParam(':is_active', $isActive);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            // Success
            $success = true;
            setFlashMessage('success', 'Certificate updated successfully');
            
            // Handle photo upload if provided
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = uploadImage($_FILES['photo'], $certificate['certificate_id']);
                
                if ($photoPath) {
                    // Update photo path
                    $query = "UPDATE certificates SET photo_path = :photo_path WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':photo_path', $photoPath);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                }
            }
            
            // Regenerate QR code in case of URL changes
            $qrCodePath = generateQRCode($certificate['certificate_id']);
            if ($qrCodePath) {
                $query = "UPDATE certificates SET qr_code_path = :qr_code_path WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':qr_code_path', $qrCodePath);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
            }
            
            // Redirect to view page
            redirect('view_certificate.php?id=' . $id);
        } else {
            $errors[] = 'Failed to update certificate';
        }
    }
}

// Page title
$pageTitle = "Edit Certificate";?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Certificate - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php';?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php';?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Certificate</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="certificates.php" class="btn btn-sm btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                        <a href="view_certificate.php?id=<?php echo $id;?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-eye"></i> View Certificate
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errors)):;?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error):;?>
                                <li><?php echo htmlspecialchars($error);?></li>
                            <?php endforeach;?>
                        </ul>
                    </div>
                <?php endif;?>
                
                <?php if ($success):;?>
                    <div class="alert alert-success">
                        Certificate updated successfully!
                    </div>
                <?php endif;?>
                
                <form action="edit_certificate.php?id=<?php echo $id;?>" method="post" enctype="multipart/form-data">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Certificate Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="certificate_id" class="form-label">Certificate ID</label>
                                        <input type="text" class="form-control" id="certificate_id" value="<?php echo htmlspecialchars($certificate['certificate_id']);?>" readonly>
                                        <div class="form-text">This ID is used for verification and cannot be changed.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="certificate_number" class="form-label">Certificate Number</label>
                                        <input type="text" class="form-control" id="certificate_number" name="certificate_number" value="<?php echo htmlspecialchars($certificate['certificate_number'] ?? '');?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($certificate['full_name']);?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="branch_name" class="form-label">Branch Name</label>
                                        <input type="text" class="form-control" id="branch_name" name="branch_name" value="<?php echo htmlspecialchars($certificate['branch_name'] ?? '');?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="grade" class="form-label">Grade</label>
                                        <select class="form-select" id="grade" name="grade">
                                            <option value="" <?php echo empty($certificate['grade']) ? 'selected' : '';?>>-- Select Grade --</option>
                                            <option value="A" <?php echo isset($certificate['grade']) && $certificate['grade'] === 'A' ? 'selected' : '';?>>A</option>
                                            <option value="A+" <?php echo isset($certificate['grade']) && $certificate['grade'] === 'A+' ? 'selected' : '';?>>A+</option>
                                            <option value="B" <?php echo isset($certificate['grade']) && $certificate['grade'] === 'B' ? 'selected' : '';?>>B</option>
                                            <option value="B+" <?php echo isset($certificate['grade']) && $certificate['grade'] === 'B+' ? 'selected' : '';?>>B+</option>
                                            <option value="C" <?php echo isset($certificate['grade']) && $certificate['grade'] === 'C' ? 'selected' : '';?>>C</option>
                                            <option value="C+" <?php echo isset($certificate['grade']) && $certificate['grade'] === 'C+' ? 'selected' : '';?>>C+</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Issue Date *</label>
                                        <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo htmlspecialchars($certificate['issue_date'] ?? $certificate['date_of_birth'] ?? '');?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="pass_date" class="form-label">Pass Date</label>
                                        <input type="date" class="form-control" id="pass_date" name="pass_date" value="<?php echo htmlspecialchars($certificate['pass_date'] ?? '');?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Certificate Content</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="certificate_content" class="form-label">Content *</label>
                                        <textarea class="form-control" id="certificate_content" name="certificate_content" rows="10" required><?php echo htmlspecialchars($certificate['certificate_content']);?></textarea>
                                        <div class="form-text">Customize the certificate text content.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Student Photo</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <img src="<?php echo '../uploads/' . $certificate['photo_path'];?>" alt="Student Photo" class="img-fluid certificate-image" style="max-width: 200px; max-height: 200px; border-radius: 5px;">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="photo" class="form-label">Change Photo</label>
                                        <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg, image/png">
                                        <div class="form-text">Leave empty to keep the current photo.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $certificate['is_active'] ? 'checked' : '';?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                        <div class="form-text">Inactive certificates cannot be verified.</div>
                                    </div>
                                    
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="validation_status" name="validation_status" <?php echo isset($certificate['validation_status']) && $certificate['validation_status'] ? 'checked' : '';?>>
                                        <label class="form-check-label" for="validation_status">Valid</label>
                                        <div class="form-text">Invalid certificates will show an error message when verified.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Actions</h5>
                                </div>
                                <div class="card-body">
                                    <button type="submit" class="btn btn-primary w-100 mb-2">Save Changes</button>
                                    <a href="view_certificate.php?id=<?php echo $id;?>" class="btn btn-secondary w-100 mb-2">Cancel</a>
                                    <a href="delete_certificate.php?id=<?php echo $id;?>" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete this certificate? This action cannot be undone.');">Delete Certificate</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
