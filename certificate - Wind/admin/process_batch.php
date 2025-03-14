<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Process Batch Certificate Generation
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $course_name = sanitizeInput($_POST['course_name']);
        $issue_date = sanitizeInput($_POST['issue_date']);
        $expiry_date = !empty($_POST['expiry_date']) ? sanitizeInput($_POST['expiry_date']) : null;
        $additional_info = sanitizeInput($_POST['additional_info']);
        $template_id = !empty($_POST['template_id_batch']) ? (int)$_POST['template_id_batch'] : null;
        $send_email = isset($_POST['send_email_batch']) ? 1 : 0;
        $created_by = $_SESSION['user_id'];
        
        // Validate required fields
        if (empty($course_name) || empty($issue_date)) {
            throw new Exception("Course name and issue date are required.");
        }
        
        // Validate recipients
        if (!isset($_POST['recipients']) || !is_array($_POST['recipients']) || count($_POST['recipients']) === 0) {
            throw new Exception("At least one recipient is required.");
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Create batch operation record
        $query = "INSERT INTO batch_operations 
                 (operation_type, total_items, status, created_by, created_at) 
                 VALUES 
                 ('certificate_generation', :total_items, 'processing', :created_by, NOW())";
        $stmt = $db->prepare($query);
        $total_items = count($_POST['recipients']);
        $stmt->bindParam(':total_items', $total_items);
        $stmt->bindParam(':created_by', $created_by);
        $stmt->execute();
        $batch_id = $db->lastInsertId();
        
        // Process each recipient
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        $certificate_ids = [];
        
        foreach ($_POST['recipients'] as $index => $recipient) {
            $name = sanitizeInput($recipient['name']);
            $email = isset($recipient['email']) ? sanitizeInput($recipient['email']) : '';
            
            // Generate certificate ID
            $certificate_id = generateUniqueID();
            
            try {
                if (empty($name)) {
                    throw new Exception("Recipient name cannot be empty");
                }
                
                // Insert certificate into database
                $query = "INSERT INTO certificates 
                          (certificate_id, full_name, course_name, issue_date, expiry_date, additional_info, template_id, created_by, created_at) 
                          VALUES 
                          (:certificate_id, :full_name, :course_name, :issue_date, :expiry_date, :additional_info, :template_id, :created_by, NOW())";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':certificate_id', $certificate_id);
                $stmt->bindParam(':full_name', $name);
                $stmt->bindParam(':course_name', $course_name);
                $stmt->bindParam(':issue_date', $issue_date);
                $stmt->bindParam(':expiry_date', $expiry_date);
                $stmt->bindParam(':additional_info', $additional_info);
                $stmt->bindParam(':template_id', $template_id);
                $stmt->bindParam(':created_by', $created_by);
                
                $stmt->execute();
                
                // Insert batch operation item
                $query = "INSERT INTO batch_operation_items 
                         (batch_id, item_id, item_data, status, created_at) 
                         VALUES 
                         (:batch_id, :item_id, :item_data, 'completed', NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':batch_id', $batch_id);
                $stmt->bindParam(':item_id', $certificate_id);
                
                $item_data = json_encode([
                    'name' => $name,
                    'email' => $email,
                    'course_name' => $course_name,
                    'issue_date' => $issue_date,
                    'expiry_date' => $expiry_date
                ]);
                
                $stmt->bindParam(':item_data', $item_data);
                $stmt->execute();
                
                // If send email is enabled and email is provided
                if ($send_email && !empty($email)) {
                    // In a premium system, we would send the email here
                    // For now, we'll just log it (implement email sending functionality later)
                    // sendCertificateEmail($email, $name, $certificate_id, $course_name);
                }
                
                $success_count++;
                $certificate_ids[] = $certificate_id;
                
            } catch (Exception $e) {
                $error_count++;
                $errors[] = "Error for {$name}: " . $e->getMessage();
                
                // Insert failed batch operation item
                $query = "INSERT INTO batch_operation_items 
                         (batch_id, item_id, item_data, status, error_message, created_at) 
                         VALUES 
                         (:batch_id, :item_id, :item_data, 'failed', :error_message, NOW())";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':batch_id', $batch_id);
                $stmt->bindParam(':item_id', $certificate_id);
                
                $item_data = json_encode([
                    'name' => $name,
                    'email' => $email,
                    'course_name' => $course_name,
                    'issue_date' => $issue_date,
                    'expiry_date' => $expiry_date
                ]);
                
                $stmt->bindParam(':item_data', $item_data);
                $stmt->bindParam(':error_message', $e->getMessage());
                $stmt->execute();
            }
        }
        
        // Update batch operation record
        $query = "UPDATE batch_operations 
                 SET status = :status, 
                     successful_items = :successful_items, 
                     failed_items = :failed_items, 
                     error_message = :error_message, 
                     completed_at = NOW()
                 WHERE id = :batch_id";
        
        $stmt = $db->prepare($query);
        $status = ($error_count === $total_items) ? 'failed' : (($success_count === $total_items) ? 'completed' : 'completed');
        $error_message = !empty($errors) ? implode("\n", $errors) : null;
        
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':successful_items', $success_count);
        $stmt->bindParam(':failed_items', $error_count);
        $stmt->bindParam(':error_message', $error_message);
        $stmt->bindParam(':batch_id', $batch_id);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        // Set success message
        if ($success_count === $total_items) {
            setFlashMessage('success', "{$success_count} certificates were generated successfully.");
        } elseif ($success_count > 0 && $error_count > 0) {
            setFlashMessage('warning', "{$success_count} certificates were generated successfully. {$error_count} failed: " . implode("; ", $errors));
        } else {
            setFlashMessage('error', "Failed to generate certificates: " . implode("; ", $errors));
        }
        
        redirect('bulk_certificates.php');
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        setFlashMessage('error', "Error: " . $e->getMessage());
        redirect('bulk_certificates.php');
    }
} else {
    // Not a POST request
    redirect('bulk_certificates.php');
}
?>
