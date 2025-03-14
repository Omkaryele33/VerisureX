<?php
/**
 * User Management Page Fixer
 * This script fixes the user_management.php file
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$userManagementFile = 'user_management.php';

// Check if file exists
if (!file_exists($userManagementFile)) {
    // Create the file if it doesn't exist
    $content = '<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * User Management
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include security constants
require_once \'security_header.php\';

// Include configuration files
require_once \'../config/config.php\';
require_once \'../config/database.php\';
require_once \'../includes/functions.php\';
require_once \'../includes/security.php\';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(\'login.php\');
}

// Check if user has admin privileges
if (!isAdmin()) {
    setFlashMessage(\'error\', \'You do not have permission to access this page.\');
    redirect(\'dashboard.php\');
}

// Process form submissions
$successMessage = $errorMessage = "";

// Handle user creation
if (isset($_POST[\'create_user\']) && validateCSRFToken($_POST[\'csrf_token\'])) {
    $username = trim($_POST[\'username\']);
    $email = trim($_POST[\'email\']);
    $password = $_POST[\'password\'];
    $role = $_POST[\'role\'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $errorMessage = "All fields are required";
    } else {
        // Check if username or email already exists
        $checkQuery = "SELECT id FROM admins WHERE username = :username OR email = :email";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(\':username\', $username);
        $checkStmt->bindParam(\':email\', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $errorMessage = "Username or email already exists";
        } else {
            // Validate password
            $passwordResult = validatePassword($password);
            if (!$passwordResult[\'valid\']) {
                $errorMessage = $passwordResult[\'message\'];
            } else {
                // Create user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $createQuery = "INSERT INTO admins (username, password, email, role) VALUES (:username, :password, :email, :role)";
                $createStmt = $db->prepare($createQuery);
                $createStmt->bindParam(\':username\', $username);
                $createStmt->bindParam(\':password\', $passwordHash);
                $createStmt->bindParam(\':email\', $email);
                $createStmt->bindParam(\':role\', $role);
                
                if ($createStmt->execute()) {
                    $successMessage = "User created successfully";
                    // Log the action
                    logSecurityEvent("user_created", "Created new user: $username", getUserId());
                } else {
                    $errorMessage = "Error creating user";
                }
            }
        }
    }
}

// Handle user deletion
if (isset($_POST[\'delete_user\']) && validateCSRFToken($_POST[\'csrf_token\'])) {
    $userId = $_POST[\'user_id\'];
    
    // Don\'t allow deletion of own account
    if ($userId == getUserId()) {
        $errorMessage = "You cannot delete your own account";
    } else {
        // Get username for logging
        $getUserQuery = "SELECT username FROM admins WHERE id = :id";
        $getUserStmt = $db->prepare($getUserQuery);
        $getUserStmt->bindParam(\':id\', $userId);
        $getUserStmt->execute();
        $userData = $getUserStmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete user
        $deleteQuery = "DELETE FROM admins WHERE id = :id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(\':id\', $userId);
        
        if ($deleteStmt->execute()) {
            $successMessage = "User deleted successfully";
            // Log the action
            logSecurityEvent("user_deleted", "Deleted user: " . $userData[\'username\'], getUserId());
        } else {
            $errorMessage = "Error deleting user";
        }
    }
}

// Get users list
$query = "SELECT id, username, email, role, last_login, is_active FROM admins ORDER BY username";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "User Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include \'includes/header.php\'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include \'includes/sidebar.php\'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageTitle; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="bi bi-person-plus"></i> Add New User
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errorMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Users</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Last Login</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user[\'username\']); ?></td>
                                            <td><?php echo htmlspecialchars($user[\'email\']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $user[\'role\'] === \'admin\' ? \'bg-danger\' : \'bg-primary\'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user[\'role\'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $user[\'last_login\'] ? date(\'Y-m-d H:i\', strtotime($user[\'last_login\'])) : \'Never\'; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $user[\'is_active\'] ? \'bg-success\' : \'bg-secondary\'; ?>">
                                                    <?php echo $user[\'is_active\'] ? \'Active\' : \'Inactive\'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                            data-id="<?php echo $user[\'id\']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user[\'username\']); ?>"
                                                            data-email="<?php echo htmlspecialchars($user[\'email\']); ?>"
                                                            data-role="<?php echo htmlspecialchars($user[\'role\']); ?>"
                                                            data-active="<?php echo $user[\'is_active\']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($user[\'id\'] != getUserId()): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" 
                                                            data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                            data-id="<?php echo $user[\'id\']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user[\'username\']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No users found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">
                                Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long and include 
                                uppercase, lowercase, numbers, and special characters.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="editor">Editor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        
                        <p>Are you sure you want to delete the user <strong id="delete_username"></strong>?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Handle delete user modal
        document.querySelectorAll(\'.delete-user-btn\').forEach(button => {
            button.addEventListener(\'click\', function() {
                const userId = this.getAttribute(\'data-id\');
                const username = this.getAttribute(\'data-username\');
                
                document.getElementById(\'delete_user_id\').value = userId;
                document.getElementById(\'delete_username\').textContent = username;
            });
        });
    </script>
</body>
</html>';
    
    // Write the content to the file
    if (file_put_contents($userManagementFile, $content)) {
        echo "Success: User management file has been created.<br>";
    } else {
        die("Error: Could not create user management file.");
    }
} else {
    // File exists, so fix it
    $content = file_get_contents($userManagementFile);
    
    // Check if security_header.php is already included
    if (strpos($content, "require_once 'security_header.php';") === false) {
        // Add security_header.php include after session_start
        $content = str_replace(
            "require_once __DIR__ . "/../includes/session.php";",
            "require_once __DIR__ . "/../includes/session.php";\n\n// Include security constants\nrequire_once 'security_header.php';",
            $content
        );
    }
    
    // Check if security.php is already included
    if (strpos($content, "require_once '../includes/security.php';") === false) {
        // Add security.php include after functions.php
        $content = str_replace(
            "require_once '../includes/functions.php';",
            "require_once '../includes/functions.php';\nrequire_once '../includes/security.php';",
            $content
        );
    }
    
    // Write the updated content back to the file
    if (file_put_contents($userManagementFile, $content)) {
        echo "Success: User management file has been fixed.<br>";
    } else {
        echo "Error: Could not write to user management file.<br>";
    }
}

// Check the security_log.php file
$securityLogFile = 'security_log.php';
if (!file_exists($securityLogFile)) {
    echo "The security log file doesn't exist. It was created earlier.";
} else {
    echo "The security log file already exists.";
}

// Function to create or fix a file with security includes
function fixFile($filename) {
    if (!file_exists($filename)) {
        echo "Warning: $filename doesn't exist.<br>";
        return;
    }
    
    $content = file_get_contents($filename);
    
    // Add security_header.php include if not present
    if (strpos($content, "require_once 'security_header.php';") === false) {
        $content = str_replace(
            "require_once __DIR__ . "/../includes/session.php";",
            "require_once __DIR__ . "/../includes/session.php";\n\n// Include security constants\nrequire_once 'security_header.php';",
            $content
        );
    }
    
    // Add security.php include if not present
    if (strpos($content, "require_once '../includes/security.php';") === false && 
        strpos($content, "require_once '../includes/functions.php';") !== false) {
        $content = str_replace(
            "require_once '../includes/functions.php';",
            "require_once '../includes/functions.php';\nrequire_once '../includes/security.php';",
            $content
        );
    }
    
    // Write the updated content back to the file
    if (file_put_contents($filename, $content)) {
        echo "Success: $filename has been fixed.<br>";
    } else {
        echo "Error: Could not write to $filename.<br>";
    }
}

// Fix additional files
$additionalFiles = [
    'dashboard.php',
    'create_certificate.php',
    'create_template.php',
    'debug.php',
    'index.php',
    'bulk_certificates.php'
];

echo "<h3>Fixing additional files:</h3>";
foreach ($additionalFiles as $file) {
    fixFile($file);
}

echo "<p><a href='certificate_templates.php'>Test Certificate Templates page</a></p>";
echo "<p><a href='user_management.php'>Test User Management page</a></p>";
?>
