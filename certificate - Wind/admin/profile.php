<?php
/**
 * Admin Panel - User Profile
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

// Get user data
$userId = $_SESSION['user_id'] ?? 0;
$query = "SELECT * FROM admins WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, redirect to login
if (!$user) {
    setFlashMessage('error', 'User not found. Please login again.');
    redirect('login.php');
}

// Initialize variables
$errors = [];
$success = false;

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Update profile
    $updateQuery = "UPDATE admins SET full_name = :full_name, email = :email, updated_at = NOW() WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':full_name', $fullName);
    $updateStmt->bindParam(':email', $email);
    $updateStmt->bindParam(':id', $userId);
    
    if ($updateStmt->execute()) {
        $success = true;
        setFlashMessage('success', 'Profile updated successfully');
        redirect('profile.php');
    } else {
        $errors[] = 'Failed to update profile';
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Validate input
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    } else {
        // Verify current password
        if (!password_verify($currentPassword, $user['password'] ?? '')) {
            $errors[] = 'Current password is incorrect';
        }
    }
    
    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long';
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirm password do not match';
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE admins SET password = :password, updated_at = NOW() WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':id', $userId);
        
        if ($updateStmt->execute()) {
            $success = true;
            setFlashMessage('success', 'Password changed successfully');
            redirect('profile.php');
        } else {
            $errors[] = 'Failed to change password';
        }
    }
}

// Page title
$pageTitle = "Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Certificate Validation System</title>
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
                    <h1 class="h2">User Profile</h1>
                </div>
                
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                        <?php echo $_SESSION['flash_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Profile updated successfully!
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form action="profile.php" method="post">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly>
                                        <div class="form-text">Username cannot be changed.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" class="form-control" id="role" value="<?php echo htmlspecialchars(ucfirst($user['role'] ?? '')); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="created_at" class="form-label">Account Created</label>
                                        <input type="text" class="form-control" id="created_at" value="<?php echo date('Y-m-d H:i:s', strtotime($user['created_at'] ?? '')); ?>" readonly>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="last_login" class="form-label">Last Login</label>
                                        <input type="text" class="form-control" id="last_login" value="<?php echo $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'; ?>" readonly>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="profile.php" method="post">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 8 characters long.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Account Information</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Status:</strong> 
                                    <?php if ($user['is_active'] ?? false): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </p>
                                
                                <p><strong>Certificates Created:</strong>
                                    <?php
                                        $certQuery = "SELECT COUNT(*) FROM certificates WHERE created_by = :user_id";
                                        $certStmt = $db->prepare($certQuery);
                                        $certStmt->bindParam(':user_id', $userId);
                                        $certStmt->execute();
                                        $certCount = $certStmt->fetchColumn();
                                        echo $certCount;
                                    ?>
                                </p>
                                
                                <p><strong>Last Activity:</strong>
                                    <?php
                                        if ($user['last_activity'] ?? false) {
                                            $lastActivity = strtotime($user['last_activity']);
                                            $now = time();
                                            $diff = $now - $lastActivity;
                                            
                                            if ($diff < 60) {
                                                echo 'Just now';
                                            } elseif ($diff < 3600) {
                                                echo floor($diff / 60) . ' minutes ago';
                                            } elseif ($diff < 86400) {
                                                echo floor($diff / 3600) . ' hours ago';
                                            } else {
                                                echo date('Y-m-d H:i:s', $lastActivity);
                                            }
                                        } else {
                                            echo 'No activity recorded';
                                        }
                                    ?>
                                </p>
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
