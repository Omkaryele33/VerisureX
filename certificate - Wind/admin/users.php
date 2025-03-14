<?php
/**
 * Admin Panel - Manage Users
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

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    setFlashMessage('error', 'You do not have permission to access this page');
    redirect('index.php');
}

// Handle user actions (activate/deactivate/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $userId = (int)$_GET['id'];
    
    // Don't allow actions on own account
    if ($userId === (int)$_SESSION['user_id']) {
        setFlashMessage('error', 'You cannot modify your own account');
        redirect('users.php');
    }
    
    // Check if user exists
    $checkQuery = "SELECT * FROM admins WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $userId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        setFlashMessage('error', 'User not found');
        redirect('users.php');
    }
    
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Perform action
    switch ($action) {
        case 'activate':
            $updateQuery = "UPDATE admins SET is_active = 1 WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $userId);
            
            if ($updateStmt->execute()) {
                setFlashMessage('success', 'User activated successfully');
            } else {
                setFlashMessage('error', 'Failed to activate user');
            }
            break;
            
        case 'deactivate':
            $updateQuery = "UPDATE admins SET is_active = 0 WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $userId);
            
            if ($updateStmt->execute()) {
                setFlashMessage('success', 'User deactivated successfully');
            } else {
                setFlashMessage('error', 'Failed to deactivate user');
            }
            break;
            
        case 'delete':
            // Check if user has created certificates
            $certQuery = "SELECT COUNT(*) FROM certificates WHERE created_by = :id";
            $certStmt = $db->prepare($certQuery);
            $certStmt->bindParam(':id', $userId);
            $certStmt->execute();
            $certCount = $certStmt->fetchColumn();
            
            if ($certCount > 0) {
                setFlashMessage('error', 'Cannot delete user: User has created ' . $certCount . ' certificates');
                redirect('users.php');
            }
            
            $deleteQuery = "DELETE FROM admins WHERE id = :id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $userId);
            
            if ($deleteStmt->execute()) {
                setFlashMessage('success', 'User deleted successfully');
            } else {
                setFlashMessage('error', 'Failed to delete user');
            }
            break;
            
        default:
            setFlashMessage('error', 'Invalid action');
    }
    
    redirect('users.php');
}

// Handle create user form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'user');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } else {
        // Check if username exists
        $checkQuery = "SELECT COUNT(*) FROM admins WHERE username = :username";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists';
        }
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($role !== 'admin' && $role !== 'user') {
        $errors[] = 'Invalid role';
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO admins (username, password, role, is_active, created_at) 
                        VALUES (:username, :password, :role, :is_active, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':password', $hashedPassword);
        $insertStmt->bindParam(':role', $role);
        $insertStmt->bindParam(':is_active', $isActive);
        
        if ($insertStmt->execute()) {
            setFlashMessage('success', 'User created successfully');
            redirect('users.php');
        } else {
            setFlashMessage('error', 'Failed to create user');
        }
    } else {
        // Store errors in session for display
        $_SESSION['user_errors'] = $errors;
        $_SESSION['form_data'] = [
            'username' => $username,
            'role' => $role,
            'is_active' => $isActive
        ];
    }
}

// Get all users
$query = "SELECT * FROM admins ORDER BY username ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Users";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Certificate Validation System</title>
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
                    <h1 class="h2">Manage Users</h1>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus"></i> Add New User
                    </button>
                </div>
                
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                        <?php echo $_SESSION['flash_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                                        <td>
                                            <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($user['is_active']) && $user['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($user['created_at']) ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php if (isset($user['last_login']) && $user['last_login']): ?>
                                                <?php echo date('Y-m-d H:i:s', strtotime($user['last_login'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Never</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($user['id']) && isset($_SESSION['user_id']) && (int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                                <?php if (isset($user['is_active']) && $user['is_active']): ?>
                                                    <a href="users.php?action=deactivate&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to deactivate this user?');">
                                                        <i class="bi bi-toggle-off"></i> Deactivate
                                                    </a>
                                                <?php else: ?>
                                                    <a href="users.php?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to activate this user?');">
                                                        <i class="bi bi-toggle-on"></i> Activate
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="users.php" method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-body">
                        <?php if (isset($_SESSION['user_errors'])): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($_SESSION['user_errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php unset($_SESSION['user_errors']); ?>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_SESSION['form_data']['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo (isset($_SESSION['form_data']['role']) && $_SESSION['form_data']['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo (isset($_SESSION['form_data']['role']) && $_SESSION['form_data']['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo (isset($_SESSION['form_data']['is_active']) && $_SESSION['form_data']['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
                <?php unset($_SESSION['form_data']); ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    
    <?php if (isset($_SESSION['user_errors'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var createUserModal = new bootstrap.Modal(document.getElementById('createUserModal'));
                createUserModal.show();
            });
        </script>
    <?php endif; ?>
</body>
</html>
