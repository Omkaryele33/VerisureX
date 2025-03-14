<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Create Certificate Template
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_template'])) {
    $template_name = sanitizeInput($_POST['template_name']);
    $description = sanitizeInput($_POST['description']);
    $name_position_x = isset($_POST['name_position_x']) ? (int)$_POST['name_position_x'] : 50;
    $name_position_y = isset($_POST['name_position_y']) ? (int)$_POST['name_position_y'] : 50;
    $course_position_x = isset($_POST['course_position_x']) ? (int)$_POST['course_position_x'] : 50;
    $course_position_y = isset($_POST['course_position_y']) ? (int)$_POST['course_position_y'] : 60;
    $date_position_x = isset($_POST['date_position_x']) ? (int)$_POST['date_position_x'] : 50;
    $date_position_y = isset($_POST['date_position_y']) ? (int)$_POST['date_position_y'] : 70;
    $qr_position_x = isset($_POST['qr_position_x']) ? (int)$_POST['qr_position_x'] : 85;
    $qr_position_y = isset($_POST['qr_position_y']) ? (int)$_POST['qr_position_y'] : 80;
    $font_family = sanitizeInput($_POST['font_family'] ?? 'Arial');
    $font_size = (int)$_POST['font_size'] ?? 18;
    $font_color = sanitizeInput($_POST['font_color'] ?? '#000000');
    $created_by = $_SESSION['user_id'];
    
    // Validate required fields
    $errors = [];
    
    if (empty($template_name)) {
        $errors[] = "Template name is required";
    }
    
    // Upload template image if provided
    $template_image = '';
    if (isset($_FILES['template_image']) && $_FILES['template_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['template_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, and PDF files are allowed.";
        } elseif ($_FILES['template_image']['size'] > $max_size) {
            $errors[] = "File size exceeds the limit (5MB).";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../uploads/templates/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['template_image']['name'], PATHINFO_EXTENSION);
            $template_image = uniqid('template_') . '.' . $file_extension;
            $upload_path = $upload_dir . $template_image;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['template_image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload template image.";
                $template_image = '';
            }
        }
    }
    
    // If no errors, insert template into database
    if (empty($errors)) {
        $query = "INSERT INTO certificate_templates 
                  (template_name, description, template_image, name_position_x, name_position_y, 
                   course_position_x, course_position_y, date_position_x, date_position_y,
                   qr_position_x, qr_position_y, font_family, font_size, font_color, created_by, created_at) 
                  VALUES 
                  (:template_name, :description, :template_image, :name_position_x, :name_position_y,
                   :course_position_x, :course_position_y, :date_position_x, :date_position_y,
                   :qr_position_x, :qr_position_y, :font_family, :font_size, :font_color, :created_by, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':template_name', $template_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':template_image', $template_image);
        $stmt->bindParam(':name_position_x', $name_position_x);
        $stmt->bindParam(':name_position_y', $name_position_y);
        $stmt->bindParam(':course_position_x', $course_position_x);
        $stmt->bindParam(':course_position_y', $course_position_y);
        $stmt->bindParam(':date_position_x', $date_position_x);
        $stmt->bindParam(':date_position_y', $date_position_y);
        $stmt->bindParam(':qr_position_x', $qr_position_x);
        $stmt->bindParam(':qr_position_y', $qr_position_y);
        $stmt->bindParam(':font_family', $font_family);
        $stmt->bindParam(':font_size', $font_size);
        $stmt->bindParam(':font_color', $font_color);
        $stmt->bindParam(':created_by', $created_by);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Template created successfully.');
            redirect('certificate_templates.php');
        } else {
            $errors[] = "Failed to create template.";
        }
    }
}

// Page title
$pageTitle = "Create Certificate Template";?>

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
        .certificate-canvas {
            position: relative;
            width: 100%;
            min-height: 500px;
            border: 1px solid #dee2e6;
            background-color: #fff;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .certificate-element {
            position: absolute;
            padding: 5px;
            background-color: rgba(240, 240, 240, 0.7);
            border: 1px dashed #aaa;
            border-radius: 4px;
            cursor: move;
            z-index: 10;
            display: inline-block;
        }
        
        .certificate-element.recipient-name {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .certificate-element.course-name {
            top: 60%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .certificate-element.issue-date {
            top: 70%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .certificate-element.qr-code {
            width: 100px;
            height: 100px;
            top: 80%;
            left: 85%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .position-input {
            width: 60px;
            display: inline-block;
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
                                <li class="breadcrumb-item active" aria-current="page">Create Template</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <a href="certificate_templates.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Back to Templates
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errors)):;?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error):;?>
                        <li><?php echo $error;?></li>
                        <?php endforeach;?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif;?>
                
                <form action="create_template.php" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4">
                            <!-- Template Details -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Template Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="template_name" name="template_name" value="<?php echo isset($template_name) ? htmlspecialchars($template_name) : '';?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : '';?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="template_image" class="form-label">Template Background</label>
                                        <input type="file" class="form-control" id="template_image" name="template_image" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="form-text">Optional. Maximum file size: 5MB</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Typography Settings -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Typography</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#typographyOptions">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="card-body collapse show" id="typographyOptions">
                                    <div class="mb-3">
                                        <label for="font_family" class="form-label">Font Family</label>
                                        <select class="form-select" id="font_family" name="font_family">
                                            <option value="Arial" <?php echo (isset($font_family) && $font_family == 'Arial') ? 'selected' : '';?>>Arial</option>
                                            <option value="Times New Roman" <?php echo (isset($font_family) && $font_family == 'Times New Roman') ? 'selected' : '';?>>Times New Roman</option>
                                            <option value="Helvetica" <?php echo (isset($font_family) && $font_family == 'Helvetica') ? 'selected' : '';?>>Helvetica</option>
                                            <option value="Verdana" <?php echo (isset($font_family) && $font_family == 'Verdana') ? 'selected' : '';?>>Verdana</option>
                                            <option value="Georgia" <?php echo (isset($font_family) && $font_family == 'Georgia') ? 'selected' : '';?>>Georgia</option>
                                            <option value="Tahoma" <?php echo (isset($font_family) && $font_family == 'Tahoma') ? 'selected' : '';?>>Tahoma</option>
                                            <option value="Trebuchet MS" <?php echo (isset($font_family) && $font_family == 'Trebuchet MS') ? 'selected' : '';?>>Trebuchet MS</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="font_size" class="form-label">Font Size (px)</label>
                                        <input type="number" class="form-control" id="font_size" name="font_size" min="10" max="72" value="<?php echo isset($font_size) ? $font_size : 18;?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="font_color" class="form-label">Font Color</label>
                                        <input type="color" class="form-control form-control-color" id="font_color" name="font_color" value="<?php echo isset($font_color) ? $font_color : '#000000';?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Element Positions -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Element Positions</h5>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#positionOptions">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="card-body collapse show" id="positionOptions">
                                    <p class="text-muted small mb-3">Positions are percentage-based (0-100). You can drag elements in the preview or enter values manually.</p>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Recipient Name Position</label>
                                        <div class="input-group">
                                            <span class="input-group-text">X</span>
                                            <input type="number" class="form-control position-input" id="name_position_x" name="name_position_x" min="0" max="100" value="<?php echo isset($name_position_x) ? $name_position_x : 50;?>">
                                            <span class="input-group-text">Y</span>
                                            <input type="number" class="form-control position-input" id="name_position_y" name="name_position_y" min="0" max="100" value="<?php echo isset($name_position_y) ? $name_position_y : 50;?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Course Name Position</label>
                                        <div class="input-group">
                                            <span class="input-group-text">X</span>
                                            <input type="number" class="form-control position-input" id="course_position_x" name="course_position_x" min="0" max="100" value="<?php echo isset($course_position_x) ? $course_position_x : 50;?>">
                                            <span class="input-group-text">Y</span>
                                            <input type="number" class="form-control position-input" id="course_position_y" name="course_position_y" min="0" max="100" value="<?php echo isset($course_position_y) ? $course_position_y : 60;?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Issue Date Position</label>
                                        <div class="input-group">
                                            <span class="input-group-text">X</span>
                                            <input type="number" class="form-control position-input" id="date_position_x" name="date_position_x" min="0" max="100" value="<?php echo isset($date_position_x) ? $date_position_x : 50;?>">
                                            <span class="input-group-text">Y</span>
                                            <input type="number" class="form-control position-input" id="date_position_y" name="date_position_y" min="0" max="100" value="<?php echo isset($date_position_y) ? $date_position_y : 70;?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">QR Code Position</label>
                                        <div class="input-group">
                                            <span class="input-group-text">X</span>
                                            <input type="number" class="form-control position-input" id="qr_position_x" name="qr_position_x" min="0" max="100" value="<?php echo isset($qr_position_x) ? $qr_position_x : 85;?>">
                                            <span class="input-group-text">Y</span>
                                            <input type="number" class="form-control position-input" id="qr_position_y" name="qr_position_y" min="0" max="100" value="<?php echo isset($qr_position_y) ? $qr_position_y : 80;?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <!-- Template Preview -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Template Preview</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i> Drag elements to position them on the certificate. Values will automatically update.
                                    </div>
                                    
                                    <div class="certificate-canvas" id="certificateCanvas">
                                        <div class="certificate-element recipient-name" data-input-x="name_position_x" data-input-y="name_position_y">
                                            <div id="previewRecipientName">John Doe</div>
                                        </div>
                                        <div class="certificate-element course-name" data-input-x="course_position_x" data-input-y="course_position_y">
                                            <div id="previewCourseName">Advanced Web Development</div>
                                        </div>
                                        <div class="certificate-element issue-date" data-input-x="date_position_x" data-input-y="date_position_y">
                                            <div id="previewIssueDate">January 15, 2023</div>
                                        </div>
                                        <div class="certificate-element qr-code" data-input-x="qr_position_x" data-input-y="qr_position_y">
                                            <div class="text-center">
                                                <i class="bi bi-qr-code" style="font-size: 50px;"></i>
                                                <div class="mt-1 small">QR Code</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="backgroundPreview" class="form-label">Background Image Preview</label>
                                        <input type="file" class="form-control" id="backgroundPreview" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="form-text">This is for preview only and won't be saved. Use the field in the Template Details section to save.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <a href="certificate_templates.php" class="btn btn-outline-secondary">Cancel</a>
                                        <button type="submit" name="create_template" class="btn btn-primary">Create Template</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs@1.10.11/dist/interact.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update preview based on typography settings
            const fontFamilySelect = document.getElementById('font_family');
            const fontSizeInput = document.getElementById('font_size');
            const fontColorInput = document.getElementById('font_color');
            const previewElements = document.querySelectorAll('.certificate-element div');
            
            function updateTypography() {
                const fontFamily = fontFamilySelect.value;
                const fontSize = fontSizeInput.value + 'px';
                const fontColor = fontColorInput.value;
                
                previewElements.forEach(el => {
                    el.style.fontFamily = fontFamily;
                    el.style.fontSize = fontSize;
                    el.style.color = fontColor;
                });
            }
            
            fontFamilySelect.addEventListener('change', updateTypography);
            fontSizeInput.addEventListener('change', updateTypography);
            fontColorInput.addEventListener('input', updateTypography);
            
            // Initialize typography
            updateTypography();
            
            // Background image preview
            const backgroundPreview = document.getElementById('backgroundPreview');
            const certificateCanvas = document.getElementById('certificateCanvas');
            
            backgroundPreview.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        certificateCanvas.style.backgroundImage = `url('${e.target.result}')`;
                        certificateCanvas.style.backgroundSize = 'cover';
                        certificateCanvas.style.backgroundPosition = 'center';
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Make elements draggable
            interact('.certificate-element').draggable({
                inertia: true,
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: 'parent',
                        endOnly: true
                    })
                ],
                autoScroll: true,
                listeners: {
                    move: dragMoveListener,
                    end: function(event) {
                        const target = event.target;
                        const inputX = document.getElementById(target.dataset.inputX);
                        const inputY = document.getElementById(target.dataset.inputY);
                        
                        // Calculate percentage positions
                        const canvas = document.getElementById('certificateCanvas');
                        const canvasRect = canvas.getBoundingClientRect();
                        const targetRect = target.getBoundingClientRect();
                        
                        const centerX = targetRect.left + targetRect.width / 2 - canvasRect.left;
                        const centerY = targetRect.top + targetRect.height / 2 - canvasRect.top;
                        
                        const percentX = Math.round((centerX / canvasRect.width) * 100);
                        const percentY = Math.round((centerY / canvasRect.height) * 100);
                        
                        // Update input values
                        if (inputX) inputX.value = Math.max(0, Math.min(100, percentX));
                        if (inputY) inputY.value = Math.max(0, Math.min(100, percentY));
                    }
                }
            });
            
            function dragMoveListener(event) {
                const target = event.target;
                
                // Keep track of the position
                const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                
                // Update element position
                target.style.transform = `translate(${x}px, ${y}px)`;
                
                // Store the position
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            }
            
            // Update element positions from input values
            const positionInputs = document.querySelectorAll('input[id$="_position_x"], input[id$="_position_y"]');
            positionInputs.forEach(input => {
                input.addEventListener('change', updateElementPositions);
            });
            
            function updateElementPositions() {
                const elements = document.querySelectorAll('.certificate-element');
                const canvas = document.getElementById('certificateCanvas');
                const canvasRect = canvas.getBoundingClientRect();
                
                elements.forEach(element => {
                    const inputXId = element.dataset.inputX;
                    const inputYId = element.dataset.inputY;
                    
                    if (inputXId && inputYId) {
                        const inputX = document.getElementById(inputXId);
                        const inputY = document.getElementById(inputYId);
                        
                        if (inputX && inputY) {
                            const percentX = parseFloat(inputX.value);
                            const percentY = parseFloat(inputY.value);
                            
                            // Reset the transform
                            element.style.transform = '';
                            element.setAttribute('data-x', 0);
                            element.setAttribute('data-y', 0);
                            
                            // Position by percentage
                            element.style.left = percentX + '%';
                            element.style.top = percentY + '%';
                            element.style.transform = 'translate(-50%, -50%)';
                        }
                    }
                });
            }
            
            // Initialize positions
            updateElementPositions();
        });
    </script>
</body>
</html>
