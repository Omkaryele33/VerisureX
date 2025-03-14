<?php
/**
 * Admin Panel - Create Certificate Page
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once 'master_init.php';

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
$holderName = '';
$courseName = '';
$branchName = '';
$issueDate = '';
$passDate = '';
$grade = '';
$templateId = '';
$photoPath = '';
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $holderName = $_POST['holder_name'] ?? '';
    $courseName = $_POST['course_name'] ?? '';
    $branchName = $_POST['branch_name'] ?? '';
    $issueDate = $_POST['issue_date'] ?? '';
    $passDate = $_POST['pass_date'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $templateId = $_POST['template_id'] ?? '';
    
    if (empty($holderName)) {
        $error = 'Full name is required';
    } elseif (empty($courseName)) {
        $error = 'Course name is required';
    } elseif (empty($issueDate)) {
        $error = 'Issue date is required';
    } elseif (empty($passDate)) {
        $error = 'Pass date is required';
    } elseif (empty($grade)) {
        $error = 'Grade is required';
    } elseif (empty($templateId)) {
        $error = 'Certificate template is required';
    } elseif (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Student photo is required';
    } else {
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Generate certificate ID (UUID)
            $certificateId = generateUUID();
            
            // Upload photo
            $photoPath = uploadImage($_FILES['photo'], $certificateId);
            
            if (!$photoPath) {
                throw new Exception('Failed to upload photo. Please check file size and format.');
            }
            
            // Generate QR code
            $qrCodePath = generateQRCode($certificateId);
            
            // Get current admin ID with validation and fallback
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $adminId = $_SESSION['user_id'];
            } else {
                // Fallback: get first admin from database
                $adminQuery = $db->query("SELECT id FROM admins LIMIT 1");
                $adminResult = $adminQuery->fetch(PDO::FETCH_ASSOC);
                
                if (!$adminResult) {
                    throw new Exception('No admin user found. Cannot create certificate.');
                }
                
                $adminId = $adminResult['id'];
            }
            
            // Validate admin ID is not empty
            if (empty($adminId)) {
                throw new Exception('Admin ID is required but not available. Please log in again.');
            }
            
            // Insert certificate into database
            $query = "INSERT INTO certificates (certificate_id, holder_name, course_name, branch_name, issue_date, pass_date, grade, template_id, created_by, full_name, certificate_content, photo_path, qr_code_path) 
                      VALUES (:certificate_id, :holder_name, :course_name, :branch_name, :issue_date, :pass_date, :grade, :template_id, :created_by, :full_name, :certificate_content, :photo_path, :qr_code_path)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':certificate_id', $certificateId);
            $stmt->bindParam(':holder_name', $holderName);
            $stmt->bindParam(':course_name', $courseName);
            $stmt->bindParam(':branch_name', $branchName);
            $stmt->bindParam(':issue_date', $issueDate);
            $stmt->bindParam(':pass_date', $passDate);
            $stmt->bindParam(':grade', $grade);
            $stmt->bindParam(':template_id', $templateId);
            $stmt->bindParam(':created_by', $adminId);
            $stmt->bindParam(':full_name', $holderName);
            $stmt->bindParam(':certificate_content', $courseName);
            $stmt->bindParam(':photo_path', $photoPath);
            $stmt->bindParam(':qr_code_path', $qrCodePath);
            
            if ($stmt->execute()) {
                // Commit transaction
                $db->commit();
                
                // Set success message
                $success = 'Certificate created successfully with ID: ' . $certificateId;
                
                // Clear form data
                $holderName = '';
                $courseName = '';
                $branchName = '';
                $issueDate = '';
                $passDate = '';
                $grade = '';
                $templateId = '';
                $photoPath = '';
            } else {
                throw new Exception('Failed to create certificate');
            }
        } catch (Exception $e) {
            // Rollback transaction
            $db->rollBack();
            
            // Set error message
            $error = $e->getMessage();
        }
    }
}

// Page title
$pageTitle = "Create Certificate";?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Certificate - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php';?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php';?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Create Certificate</h1>
                </div>
                
                <?php if (!empty($error)):;?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error;?>
                </div>
                <?php endif;?>
                
                <?php if (!empty($success)):;?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success;?>
                </div>
                <?php endif;?>
                
                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="holder_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="holder_name" name="holder_name" value="<?php echo htmlspecialchars($holderName ?? '');?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="course_name" class="form-label">Course Name *</label>
                                    <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($courseName ?? '');?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="branch_name" class="form-label">Branch Name *</label>
                                    <input type="text" class="form-control" id="branch_name" name="branch_name" value="<?php echo htmlspecialchars($branchName ?? '');?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="template_id" class="form-label">Certificate Template *</label>
                                    <select class="form-control" id="template_id" name="template_id" required>
                                        <option value="">Select Template</option>
                                        <?php
                                        $templateQuery = "SELECT template_id, template_name FROM certificate_templates ORDER BY template_name";
                                        $templates = $db->query($templateQuery)->fetchAll();
                                        foreach ($templates as $template) {
                                            $selected = ($template['template_id'] == ($templateId ?? '')) ? 'selected' : '';
                                            echo "<option value='" . $template['template_id'] . "' $selected>" . htmlspecialchars($template['template_name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="issue_date" class="form-label">Issue Date *</label>
                                    <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo htmlspecialchars($issueDate ?? '');?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="pass_date" class="form-label">Pass Date *</label>
                                    <input type="date" class="form-control" id="pass_date" name="pass_date" value="<?php echo htmlspecialchars($passDate ?? '');?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="grade" class="form-label">Grade *</label>
                                    <input type="text" class="form-control" id="grade" name="grade" value="<?php echo htmlspecialchars($grade ?? '');?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="photo" class="form-label">Student Photo *</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Create Certificate</button>
                                <a href="certificates.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#certificate_content').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });
    </script>
</body>
</html>
