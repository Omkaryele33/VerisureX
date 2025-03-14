<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Bulk Certificate Generation
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

// Handle file upload
$importMessage = '';
$importType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    // Check if file was uploaded without errors
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $fileName = $_FILES['csv_file']['name'];
        $fileSize = $_FILES['csv_file']['size'];
        $fileTmpName = $_FILES['csv_file']['tmp_name'];
        $fileType = $_FILES['csv_file']['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file extension
        if ($fileExtension === 'csv') {
            // Check file size (limit: 5MB)
            if ($fileSize <= 5000000) {
                // Process CSV file
                $row = 1;
                $successCount = 0;
                $errorCount = 0;
                $errors = [];
                
                if (($handle = fopen($fileTmpName, "r")) !== FALSE) {
                    // Begin transaction
                    $db->beginTransaction();
                    
                    try {
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            if ($row === 1) {
                                // Skip header row
                                $row++;
                                continue;
                            }
                            
                            // Validate the CSV data
                            if (count($data) < 4) {
                                $errors[] = "Row $row: Insufficient data";
                                $errorCount++;
                                $row++;
                                continue;
                            }
                            
                            // Generate certificate ID
                            $certificate_id = generateUniqueID();
                            
                            // Prepare data for insertion
                            $full_name = sanitizeInput($data[0]);
                            $course_name = sanitizeInput($data[1]);
                            $issue_date = date('Y-m-d', strtotime(sanitizeInput($data[2])));
                            $expiry_date = !empty($data[3]) ? date('Y-m-d', strtotime(sanitizeInput($data[3]))) : null;
                            $additional_info = sanitizeInput($data[4] ?? '');
                            $created_by = $_SESSION['user_id'];
                            
                            // Validate required fields
                            if (empty($full_name) || empty($course_name) || empty($issue_date)) {
                                $errors[] = "Row $row: Missing required fields (name, course, or issue date)";
                                $errorCount++;
                                $row++;
                                continue;
                            }
                            
                            // Insert certificate into database
                            $query = "INSERT INTO certificates 
                                      (certificate_id, full_name, course_name, issue_date, expiry_date, additional_info, created_by, created_at) 
                                      VALUES 
                                      (:certificate_id, :full_name, :course_name, :issue_date, :expiry_date, :additional_info, :created_by, NOW())";
                            
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':certificate_id', $certificate_id);
                            $stmt->bindParam(':full_name', $full_name);
                            $stmt->bindParam(':course_name', $course_name);
                            $stmt->bindParam(':issue_date', $issue_date);
                            $stmt->bindParam(':expiry_date', $expiry_date);
                            $stmt->bindParam(':additional_info', $additional_info);
                            $stmt->bindParam(':created_by', $created_by);
                            
                            if ($stmt->execute()) {
                                $successCount++;
                            } else {
                                $errors[] = "Row $row: Database error";
                                $errorCount++;
                            }
                            
                            $row++;
                        }
                        
                        // Commit transaction
                        $db->commit();
                        
                        // Set success message
                        $importMessage = "CSV import complete: $successCount certificates created successfully, $errorCount failed.";
                        $importType = "success";
                        
                        if ($errorCount > 0) {
                            $importMessage .= " Errors: " . implode("; ", $errors);
                            $importType = "warning";
                        }
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $db->rollBack();
                        $importMessage = "Error: " . $e->getMessage();
                        $importType = "danger";
                    }
                    
                    fclose($handle);
                }
            } else {
                $importMessage = "Error: File size exceeds the limit (5MB).";
                $importType = "danger";
            }
        } else {
            $importMessage = "Error: Only CSV files are allowed.";
            $importType = "danger";
        }
    } else {
        $importMessage = "Error: File upload failed with error code " . $_FILES['csv_file']['error'];
        $importType = "danger";
    }
}

// Get certificate templates
$query = "SELECT template_id, name AS template_name, file_path FROM certificate_templates ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Bulk Certificate Generation";?>

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
                                <li class="breadcrumb-item"><a href="certificates.php">Certificates</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Bulk Generation</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <a href="certificates.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-arrow-left"></i> Back to Certificates
                        </a>
                        <a href="create_certificate.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Create Single Certificate
                        </a>
                    </div>
                </div>
                
                <?php if ($importMessage):;?>
                <div class="alert alert-<?php echo $importType;?> alert-dismissible fade show" role="alert">
                    <?php echo $importMessage;?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif;?>
                
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <!-- CSV Upload -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Import Certificates from CSV</h5>
                            </div>
                            <div class="card-body">
                                <form action="bulk_certificates.php" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="csv_file" class="form-label">CSV File</label>
                                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                        <div class="form-text">Maximum file size: 5MB</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="template_id" class="form-label">Certificate Template</label>
                                        <select class="form-select" id="template_id" name="template_id">
                                            <option value="">Default Template</option>
                                            <?php foreach ($templates as $template):;?>
                                            <option value="<?php echo $template['template_id'];?>"><?php echo htmlspecialchars($template['template_name']);?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="send_email" name="send_email" value="1">
                                            <label class="form-check-label" for="send_email">
                                                Send email notification to recipients
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="upload_csv" class="btn btn-primary">
                                            <i class="bi bi-upload me-2"></i> Upload and Process
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Excel Generator -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Generate Excel Template</h5>
                            </div>
                            <div class="card-body">
                                <p>Download an Excel template to help you create your CSV file correctly.</p>
                                <div class="d-grid">
                                    <a href="assets/templates/certificate_template.xlsx" class="btn btn-outline-primary" download>
                                        <i class="bi bi-file-earmark-excel me-2"></i> Download Excel Template
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <!-- CSV Format Guide -->
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">CSV Format Guide</h5>
                            </div>
                            <div class="card-body">
                                <p>Your CSV file should include the following columns:</p>
                                
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Column</th>
                                            <th>Description</th>
                                            <th>Required</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Full Name</td>
                                            <td>Recipient's full name</td>
                                            <td class="text-center text-success">Yes</td>
                                        </tr>
                                        <tr>
                                            <td>Course Name</td>
                                            <td>Name of course or certification</td>
                                            <td class="text-center text-success">Yes</td>
                                        </tr>
                                        <tr>
                                            <td>Issue Date</td>
                                            <td>Date of certificate issuance (YYYY-MM-DD)</td>
                                            <td class="text-center text-success">Yes</td>
                                        </tr>
                                        <tr>
                                            <td>Expiry Date</td>
                                            <td>Certificate expiration date (YYYY-MM-DD)</td>
                                            <td class="text-center text-danger">No</td>
                                        </tr>
                                        <tr>
                                            <td>Additional Info</td>
                                            <td>Any additional information</td>
                                            <td class="text-center text-danger">No</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading fw-bold"><i class="bi bi-info-circle me-2"></i> Sample Data Format</h6>
                                    <pre class="mb-0">Full Name,Course Name,Issue Date,Expiry Date,Additional Info
John Smith,Advanced Web Development,2023-01-15,2025-01-15,Top performer
Jane Doe,Data Science Fundamentals,2023-02-10,,With distinction</pre>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle me-2"></i> Important Notes</h6>
                                    <ul class="mb-0">
                                        <li>The first row in your CSV should contain the column headers.</li>
                                        <li>Dates should be in YYYY-MM-DD format.</li>
                                        <li>Leave the expiry date empty if the certificate doesn't expire.</li>
                                        <li>Maximum 5,000 certificates per import.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Batch Generation Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Generate Multiple Certificates for the Same Course</h5>
                    </div>
                    <div class="card-body">
                        <form action="process_batch.php" method="post">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="course_name" class="form-label">Course/Program Name</label>
                                        <input type="text" class="form-control" id="course_name" name="course_name" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Issue Date</label>
                                        <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="expiry_date" class="form-label">Expiry Date (optional)</label>
                                        <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="template_id_batch" class="form-label">Certificate Template</label>
                                        <select class="form-select" id="template_id_batch" name="template_id_batch">
                                            <option value="">Default Template</option>
                                            <?php foreach ($templates as $template):;?>
                                            <option value="<?php echo $template['template_id'];?>"><?php echo htmlspecialchars($template['template_name']);?></option>
                                            <?php endforeach;?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="additional_info" class="form-label">Additional Information (optional)</label>
                                        <textarea class="form-control" id="additional_info" name="additional_info" rows="3"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="send_email_batch" name="send_email_batch" value="1">
                                            <label class="form-check-label" for="send_email_batch">
                                                Send email notification to recipients
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="recipient-list mb-4">
                                <h6 class="mb-3">Recipients</h6>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="recipients-table">
                                        <thead>
                                            <tr>
                                                <th width="40%">Full Name</th>
                                                <th width="40%">Email (optional)</th>
                                                <th width="20%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <input type="text" class="form-control" name="recipients[0][name]" required>
                                                </td>
                                                <td>
                                                    <input type="email" class="form-control" name="recipients[0][email]">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-recipient" disabled>
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-outline-primary" id="add-recipient">
                                        <i class="bi bi-plus-circle me-2"></i> Add Recipient
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 offset-md-3">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-award me-2"></i> Generate Certificates
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let recipientCounter = 1;
            const recipientsTable = document.getElementById('recipients-table');
            const addRecipientBtn = document.getElementById('add-recipient');
            
            // Add recipient
            addRecipientBtn.addEventListener('click', function() {
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>
                        <input type="text" class="form-control" name="recipients[${recipientCounter}][name]" required>
                    </td>
                    <td>
                        <input type="email" class="form-control" name="recipients[${recipientCounter}][email]">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-recipient">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                recipientsTable.querySelector('tbody').appendChild(newRow);
                recipientCounter++;
                
                // Enable all remove buttons if we have more than one row
                if (recipientsTable.querySelectorAll('tbody tr').length > 1) {
                    recipientsTable.querySelectorAll('.remove-recipient').forEach(btn => {
                        btn.disabled = false;
                    });
                }
            });
            
            // Remove recipient (using event delegation)
            recipientsTable.addEventListener('click', function(e) {
                if (e.target.closest('.remove-recipient')) {
                    const button = e.target.closest('.remove-recipient');
                    const row = button.closest('tr');
                    
                    // Only remove if we have more than one row
                    if (recipientsTable.querySelectorAll('tbody tr').length > 1) {
                        row.remove();
                        
                        // If only one row remains, disable its remove button
                        if (recipientsTable.querySelectorAll('tbody tr').length === 1) {
                            recipientsTable.querySelector('.remove-recipient').disabled = true;
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
