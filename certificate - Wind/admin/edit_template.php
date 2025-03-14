<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Edit Certificate Template
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once 'master_init.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if template id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Template ID is required.');
    redirect('certificate_templates.php');
}

$template_id = $_GET['id'];

// Get template details
$query = "SELECT * FROM certificate_templates WHERE template_id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $template_id);
$stmt->execute();
$template = $stmt->fetch(PDO::FETCH_ASSOC);

// If template doesn't exist
if (!$template) {
    setFlashMessage('error', 'Template not found.');
    redirect('certificate_templates.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize error array
    $errors = [];
    
    // Validate template name
    if (empty($_POST['template_name'])) {
        $errors[] = 'Template name is required';
    }
    
    // If no errors, update template
    if (empty($errors)) {
        // Basic template data
        $template_name = $_POST['template_name'];
        $description = $_POST['description'] ?? '';
        $font_family = $_POST['font_family'] ?? 'Arial';
        $font_size = $_POST['font_size'] ?? '12';
        $font_color = $_POST['font_color'] ?? '#000000';
        
        // Element positions
        $name_position_x = $_POST['name_position_x'] ?? null;
        $name_position_y = $_POST['name_position_y'] ?? null;
        $course_position_x = $_POST['course_position_x'] ?? null;
        $course_position_y = $_POST['course_position_y'] ?? null;
        $date_position_x = $_POST['date_position_x'] ?? null;
        $date_position_y = $_POST['date_position_y'] ?? null;
        $qr_position_x = $_POST['qr_position_x'] ?? null;
        $qr_position_y = $_POST['qr_position_y'] ?? null;
        
        // Template image upload
        $template_image = $template['template_image']; // Default to existing image
        
        if (isset($_FILES['template_image']) && $_FILES['template_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/templates/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Allowed file types
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $file_type = $_FILES['template_image']['type'];
            $file_size = $_FILES['template_image']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = 'Invalid file type. Allowed types: JPG, PNG, GIF, PDF';
            } elseif ($file_size > $max_size) {
                $errors[] = 'File size exceeds the maximum limit of 5MB';
            } else {
                // Generate unique file name
                $file_extension = pathinfo($_FILES['template_image']['name'], PATHINFO_EXTENSION);
                $new_file_name = 'template_' . time() . '_' . $template_id . '.' . $file_extension;
                $target_file = $upload_dir . $new_file_name;
                
                // Upload file
                if (move_uploaded_file($_FILES['template_image']['tmp_name'], $target_file)) {
                    // Delete old image if exists
                    if (!empty($template['template_image'])) {
                        $old_file = $upload_dir . $template['template_image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    $template_image = $new_file_name;
                } else {
                    $errors[] = 'Failed to upload template image';
                }
            }
        }
        
        // If still no errors, update database
        if (empty($errors)) {
            $query = "UPDATE certificate_templates SET 
                template_name = :template_name,
                description = :description,
                template_image = :template_image,
                font_family = :font_family,
                font_size = :font_size,
                font_color = :font_color,
                name_position_x = :name_position_x,
                name_position_y = :name_position_y,
                course_position_x = :course_position_x,
                course_position_y = :course_position_y,
                date_position_x = :date_position_x,
                date_position_y = :date_position_y,
                qr_position_x = :qr_position_x,
                qr_position_y = :qr_position_y,
                updated_at = NOW()
                WHERE template_id = :template_id";
                
            $stmt = $db->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':template_name', $template_name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':template_image', $template_image);
            $stmt->bindParam(':font_family', $font_family);
            $stmt->bindParam(':font_size', $font_size);
            $stmt->bindParam(':font_color', $font_color);
            $stmt->bindParam(':name_position_x', $name_position_x);
            $stmt->bindParam(':name_position_y', $name_position_y);
            $stmt->bindParam(':course_position_x', $course_position_x);
            $stmt->bindParam(':course_position_y', $course_position_y);
            $stmt->bindParam(':date_position_x', $date_position_x);
            $stmt->bindParam(':date_position_y', $date_position_y);
            $stmt->bindParam(':qr_position_x', $qr_position_x);
            $stmt->bindParam(':qr_position_y', $qr_position_y);
            $stmt->bindParam(':template_id', $template_id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Template updated successfully.');
                redirect('view_template.php?id=' . $template_id);
            } else {
                $errors[] = 'Database error. Please try again.';
            }
        }
    }
}

// Page title
$pageTitle = "Edit Template: " . htmlspecialchars($template['template_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle;?> - CertifyPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .template-preview-container {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            margin-bottom: 20px;
        }
        .template-preview-image {
            max-width: 100%;
            height: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .position-settings {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .position-group {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .position-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php';?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php';?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header pt-3 pb-2 mb-4">
                    <div>
                        <h1><?php echo $pageTitle;?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="certificate_templates.php">Certificate Templates</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Template</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <a href="view_template.php?id=<?php echo $template_id;?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Template
                        </a>
                    </div>
                </div>
                
                <?php if ($flash = getFlashMessage()):;?>
                <div class="alert alert-<?php echo $flash['type'];?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message'];?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif;?>
                
                <?php if (isset($errors) && !empty($errors)):;?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error):;?>
                        <li><?php echo $error;?></li>
                        <?php endforeach;?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif;?>
                
                <form action="edit_template.php?id=<?php echo $template_id;?>" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="template_name" name="template_name" required 
                                               value="<?php echo htmlspecialchars($template['template_name']);?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($template['description'] ?? '');?></textarea>
                                        <div class="form-text">Provide a brief description of this template and its purpose.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="template_image" class="form-label">Template Image</label>
                                        <input type="file" class="form-control" id="template_image" name="template_image" accept="image/jpeg,image/png,image/gif,application/pdf">
                                        <div class="form-text">Upload a new image to replace the current one. Leave empty to keep the existing image.</div>
                                    </div>
                                    
                                    <?php if (!empty($template['template_image'])):;?>
                                    <div class="template-preview-container">
                                        <p class="mb-2 text-muted">Current Template Image</p>
                                        <img src="../uploads/templates/<?php echo $template['template_image'];?>" 
                                             alt="<?php echo htmlspecialchars($template['template_name']);?>" 
                                             class="template-preview-image">
                                    </div>
                                    <?php endif;?>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Text Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="font_family" class="form-label">Font Family</label>
                                                <select class="form-select" id="font_family" name="font_family">
                                                    <option value="Arial" <?php echo ($template['font_family'] ?? '') == 'Arial' ? 'selected' : '';?>>Arial</option>
                                                    <option value="Times New Roman" <?php echo ($template['font_family'] ?? '') == 'Times New Roman' ? 'selected' : '';?>>Times New Roman</option>
                                                    <option value="Helvetica" <?php echo ($template['font_family'] ?? '') == 'Helvetica' ? 'selected' : '';?>>Helvetica</option>
                                                    <option value="Verdana" <?php echo ($template['font_family'] ?? '') == 'Verdana' ? 'selected' : '';?>>Verdana</option>
                                                    <option value="Tahoma" <?php echo ($template['font_family'] ?? '') == 'Tahoma' ? 'selected' : '';?>>Tahoma</option>
                                                    <option value="Georgia" <?php echo ($template['font_family'] ?? '') == 'Georgia' ? 'selected' : '';?>>Georgia</option>
                                                    <option value="Courier New" <?php echo ($template['font_family'] ?? '') == 'Courier New' ? 'selected' : '';?>>Courier New</option>
                                                    <option value="Trebuchet MS" <?php echo ($template['font_family'] ?? '') == 'Trebuchet MS' ? 'selected' : '';?>>Trebuchet MS</option>
                                                    <option value="Palatino" <?php echo ($template['font_family'] ?? '') == 'Palatino' ? 'selected' : '';?>>Palatino</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="font_size" class="form-label">Font Size (px)</label>
                                                <input type="number" class="form-control" id="font_size" name="font_size" min="8" max="72" 
                                                       value="<?php echo htmlspecialchars($template['font_size'] ?? '12');?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="font_color" class="form-label">Font Color</label>
                                                <input type="color" class="form-control form-control-color w-100" id="font_color" name="font_color" 
                                                       value="<?php echo htmlspecialchars($template['font_color'] ?? '#000000');?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Element Positions</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-4">Set the X and Y coordinates for each certificate element. These values determine where the text or QR code will be placed on the certificate.</p>
                                    
                                    <div class="position-settings">
                                        <div class="position-group">
                                            <h6 class="mb-3">Recipient Name Position</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="name_position_x" class="form-label">X Position (from left)</label>
                                                        <input type="number" class="form-control" id="name_position_x" name="name_position_x" 
                                                               value="<?php echo $template['name_position_x'] ?? '';?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="name_position_y" class="form-label">Y Position (from top)</label>
                                                        <input type="number" class="form-control" id="name_position_y" name="name_position_y" 
                                                               value="<?php echo $template['name_position_y'] ?? '';?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="position-group">
                                            <h6 class="mb-3">Course Name Position</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="course_position_x" class="form-label">X Position (from left)</label>
                                                        <input type="number" class="form-control" id="course_position_x" name="course_position_x" 
                                                               value="<?php echo $template['course_position_x'] ?? '';?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="course_position_y" class="form-label">Y Position (from top)</label>
                                                        <input type="number" class="form-control" id="course_position_y" name="course_position_y" 
                                                               value="<?php echo $template['course_position_y'] ?? '';?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="position-group">
                                            <h6 class="mb-3">Date Position</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="date_position_x" class="form-label">X Position (from left)</label>
                                                        <input type="number" class="form-control" id="date_position_x" name="date_position_x" 
                                                               value="<?php echo $template['date_position_x'] ?? '';?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="date_position_y" class="form-label">Y Position (from top)</label>
                                                        <input type="number" class="form-control" id="date_position_y" name="date_position_y" 
                                                               value="<?php echo $template['date_position_y'] ?? '';?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="position-group">
                                            <h6 class="mb-3">QR Code Position</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="qr_position_x" class="form-label">X Position (from left)</label>
                                                        <input type="number" class="form-control" id="qr_position_x" name="qr_position_x" 
                                                               value="<?php echo $template['qr_position_x'] ?? '';?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="qr_position_y" class="form-label">Y Position (from top)</label>
                                                        <input type="number" class="form-control" id="qr_position_y" name="qr_position_y" 
                                                               value="<?php echo $template['qr_position_y'] ?? '';?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Help & Tips</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <h6 class="fw-bold">Position Coordinates</h6>
                                        <p>X and Y coordinates determine the position of text elements on your certificate:</p>
                                        <ul>
                                            <li><strong>X coordinate</strong>: Distance from the left edge (horizontal position)</li>
                                            <li><strong>Y coordinate</strong>: Distance from the top edge (vertical position)</li>
                                        </ul>
                                        <p>All measurements are in pixels. If left empty, default positions will be used.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="fw-bold">Content Placeholders</h6>
                                        <p>The following dynamic content will be placed at the specified positions:</p>
                                        <ul>
                                            <li><strong>Recipient Name</strong>: The name of the certificate holder</li>
                                            <li><strong>Course Name</strong>: The name of the course or program</li>
                                            <li><strong>Date</strong>: The issue date of the certificate</li>
                                            <li><strong>QR Code</strong>: The verification QR code (recommend bottom right)</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="bi bi-lightbulb me-2"></i>
                                        <strong>Tip:</strong> For best results, upload a high-resolution template image and test the positions with a sample certificate.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Save Template
                                </button>
                                <a href="view_template.php?id=<?php echo $template_id;?>" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
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