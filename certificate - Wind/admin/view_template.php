<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * View Certificate Template
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

// Get usage count
$query = "SELECT COUNT(*) as count FROM certificates WHERE template_id = :template_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':template_id', $template_id);
$stmt->execute();
$usageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Page title
$pageTitle = "View Template: " . htmlspecialchars($template['template_name']);
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
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        .template-preview-image {
            max-width: 100%;
            height: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .template-details-table th {
            width: 200px;
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
                                <li class="breadcrumb-item active" aria-current="page">View Template</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <a href="edit_template.php?id=<?php echo $template_id;?>" class="btn btn-primary me-2">
                            <i class="bi bi-pencil"></i> Edit Template
                        </a>
                        <?php if ($usageCount == 0): ?>
                        <a href="certificate_templates.php?action=delete&id=<?php echo $template_id;?>" 
                           class="btn btn-danger delete-confirm"
                           data-confirm-message="Are you sure you want to delete this template? This action cannot be undone.">
                            <i class="bi bi-trash"></i> Delete Template
                        </a>
                        <?php else: ?>
                        <button class="btn btn-danger" disabled title="Cannot delete template in use">
                            <i class="bi bi-trash"></i> Delete Template
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($flash = getFlashMessage()):;?>
                <div class="alert alert-<?php echo $flash['type'];?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message'] ?? $flash['text'] ?? '';?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif;?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Template Preview</h5>
                                <span class="badge <?php echo $usageCount > 0 ? 'bg-primary' : 'bg-secondary'; ?>">
                                    <?php echo $usageCount; ?> certificate(s) using this template
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="template-preview-container">
                                    <?php if (!empty($template['template_image'])): ?>
                                    <img src="../uploads/templates/<?php echo $template['template_image'];?>" 
                                         alt="<?php echo htmlspecialchars($template['template_name']);?>" 
                                         class="template-preview-image"
                                         onclick="previewFullSize('../uploads/templates/<?php echo $template['template_image'];?>')">
                                    <?php else: ?>
                                    <div class="p-5 text-center text-muted">
                                        <i class="bi bi-image fs-1 mb-3"></i>
                                        <p>No template image available</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-center text-muted small">Click on the image to view in full size</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Template Details</h5>
                            </div>
                            <div class="card-body">
                                <table class="table template-details-table">
                                    <tbody>
                                        <tr>
                                            <th>Template Name</th>
                                            <td><?php echo htmlspecialchars($template['template_name']);?></td>
                                        </tr>
                                        <tr>
                                            <th>Description</th>
                                            <td>
                                                <?php echo !empty($template['description']) ? 
                                                           htmlspecialchars($template['description']) : 
                                                           '<span class="text-muted">No description</span>';?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created On</th>
                                            <td>
                                                <?php echo isset($template['created_at']) ? 
                                                           date('F j, Y, g:i a', strtotime($template['created_at'])) : 
                                                           'Unknown';?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Last Updated</th>
                                            <td>
                                                <?php echo isset($template['updated_at']) ? 
                                                           date('F j, Y, g:i a', strtotime($template['updated_at'])) : 
                                                           'Never';?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Font Family</th>
                                            <td>
                                                <?php echo !empty($template['font_family']) ? 
                                                           htmlspecialchars($template['font_family']) : 
                                                           '<span class="text-muted">Default</span>';?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Font Size</th>
                                            <td>
                                                <?php echo !empty($template['font_size']) ? 
                                                           $template['font_size'] . 'px' : 
                                                           '<span class="text-muted">Default</span>';?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Font Color</th>
                                            <td>
                                                <?php if (!empty($template['font_color'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color: <?php echo $template['font_color']; ?>; border: 1px solid #dee2e6; border-radius: 4px; margin-right: 10px;"></div>
                                                    <?php echo strtoupper($template['font_color']); ?>
                                                </div>
                                                <?php else: ?>
                                                <span class="text-muted">Default</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Element Positions</h5>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Element</th>
                                            <th>X Position</th>
                                            <th>Y Position</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Recipient Name</td>
                                            <td><?php echo isset($template['name_position_x']) ? $template['name_position_x'] : 'N/A'; ?></td>
                                            <td><?php echo isset($template['name_position_y']) ? $template['name_position_y'] : 'N/A'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Course Name</td>
                                            <td><?php echo isset($template['course_position_x']) ? $template['course_position_x'] : 'N/A'; ?></td>
                                            <td><?php echo isset($template['course_position_y']) ? $template['course_position_y'] : 'N/A'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>Date</td>
                                            <td><?php echo isset($template['date_position_x']) ? $template['date_position_x'] : 'N/A'; ?></td>
                                            <td><?php echo isset($template['date_position_y']) ? $template['date_position_y'] : 'N/A'; ?></td>
                                        </tr>
                                        <tr>
                                            <td>QR Code</td>
                                            <td><?php echo isset($template['qr_position_x']) ? $template['qr_position_x'] : 'N/A'; ?></td>
                                            <td><?php echo isset($template['qr_position_y']) ? $template['qr_position_y'] : 'N/A'; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Full Size Preview Modal -->
    <div class="modal fade" id="fullSizePreviewModal" tabindex="-1" aria-labelledby="fullSizePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fullSizePreviewModalLabel">Template: <?php echo htmlspecialchars($template['template_name']);?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="fullSizePreviewImage" src="" alt="Full Size Template Preview" class="img-fluid">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Full size preview function
        function previewFullSize(imagePath) {
            const modal = new bootstrap.Modal(document.getElementById('fullSizePreviewModal'));
            document.getElementById('fullSizePreviewImage').src = imagePath;
            modal.show();
        }
        
        // Confirm delete
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-confirm').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm-message') || 'Are you sure you want to delete this item?';
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html> 