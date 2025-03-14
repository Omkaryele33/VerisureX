<?php
/**
 * Admin Panel - Change Password Page
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once 'master_init.php';

// Set page title
$pageTitle = 'Change Password';

// Include master initialization file
require_once 'master_init.php';

// Set page title
$pageTitle = 'Change Password';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize variables
$error = '';
$success = '';

// Process password change form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
    } else {
        // Get form data
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate form data
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required';
        } else if ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match';
        } else {
            // Check password complexity
            $passwordValidation = validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                $error = $passwordValidation['message'];
            } else {
                // Get user data
                $userId = $_SESSION['user_id'];
                $query = "SELECT password FROM admins WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify current password
                if (password_verify($currentPassword, $user['password'])) {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password in database
                    $query = "UPDATE admins SET 
                              password = :password, 
                              password_change_required = 0, 
                              last_password_change = NOW() 
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':id', $userId);
                    
                    if ($stmt->execute()) {
                        // Remove forced password change flag
                        $_SESSION['password_change_required'] = false;
                        
                        $success = 'Password changed successfully. You will be redirected to the dashboard.';
                        
                        // Redirect to dashboard after short delay
                        header("refresh:2;url=index.php");
                    } else {
                        $error = 'Failed to update password. Please try again.';
                    }
                } else {
                    $error = 'Current password is incorrect';
                }}}}}

// Generate CSRF token for the form
$csrfToken = generateCSRFToken();

// Check if password change is required
$passwordChangeRequired = isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'];?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 2rem auto;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            transition: width 0.5s ease-in-out;
        }
        .password-feedback {
            font-size: 0.8rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="password-container">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Change Password</h4>
                    <?php if ($passwordChangeRequired):;?>
                    <small class="text-danger">* Password change is required to continue</small>
                    <?php endif;?>
                </div>
                <div class="card-body">
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
                    
                    <form method="post" id="passwordForm" autocomplete="off">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="progress">
                                <div class="progress-bar password-strength" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="password-feedback text-muted">
                                <ul>
                                    <li id="length">At least <?php echo PASSWORD_MIN_LENGTH;?> characters</li>
                                    <?php if (PASSWORD_REQUIRE_MIXED_CASE):;?>
                                    <li id="case">Contains both uppercase and lowercase letters</li>
                                    <?php endif;?>
                                    <?php if (PASSWORD_REQUIRE_NUMBERS):;?>
                                    <li id="number">Contains at least one number</li>
                                    <?php endif;?>
                                    <?php if (PASSWORD_REQUIRE_SYMBOLS):;?>
                                    <li id="special">Contains at least one special character</li>
                                    <?php endif;?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Passwords do not match</div>
                        </div>
                        
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken);?>">
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                            <?php if (!$passwordChangeRequired):;?>
                            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php endif;?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = [];
            
            // Check length
            const lengthValid = password.length >= <?php echo PASSWORD_MIN_LENGTH;?>;
            document.getElementById('length').className = lengthValid ? 'text-success' : '';
            if (lengthValid) strength += 25;
            
            <?php if (PASSWORD_REQUIRE_MIXED_CASE):;?>
            // Check for mixed case
            const caseValid = /[a-z]/.test(password) && /[A-Z]/.test(password);
            document.getElementById('case').className = caseValid ? 'text-success' : '';
            if (caseValid) strength += 25;
            <?php endif;?>
            
            <?php if (PASSWORD_REQUIRE_NUMBERS):;?>
            // Check for numbers
            const numberValid = /[0-9]/.test(password);
            document.getElementById('number').className = numberValid ? 'text-success' : '';
            if (numberValid) strength += 25;
            <?php endif;?>
            
            <?php if (PASSWORD_REQUIRE_SYMBOLS):;?>
            // Check for special characters
            const specialValid = /[^a-zA-Z0-9]/.test(password);
            document.getElementById('special').className = specialValid ? 'text-success' : '';
            if (specialValid) strength += 25;
            <?php endif;?>
            
            // Update strength indicator
            const strengthBar = document.querySelector('.password-strength');
            strengthBar.style.width = strength + '%';
            
            if (strength < 25) {
                strengthBar.className = 'progress-bar password-strength bg-danger';
            } else if (strength < 50) {
                strengthBar.className = 'progress-bar password-strength bg-warning';
            } else if (strength < 75) {
                strengthBar.className = 'progress-bar password-strength bg-info';
            } else {
                strengthBar.className = 'progress-bar password-strength bg-success';
            }
        });
        
        // Check password confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmation = this.value;
            
            if (password !== confirmation) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
        
        // Form validation
        document.getElementById('passwordForm').addEventListener('submit', function(event) {
            const password = document.getElementById('new_password').value;
            const confirmation = document.getElementById('confirm_password').value;
            
            if (password !== confirmation) {
                event.preventDefault();
                document.getElementById('confirm_password').classList.add('is-invalid');
            }
        });
    </script>
</body>
</html>
