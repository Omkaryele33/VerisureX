<?php
/**
 * Login Page Fix
 * This script fixes the login page constant redefinition issues
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Page Fix</h1>";

// Fix login.php
$loginFile = "login.php";
if (file_exists($loginFile)) {
    $content = file_get_contents($loginFile);
    
    // Create the new content with proper include structure
    $newContent = '<?php
/**
 * Admin Panel - Login Page
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once "master_init.php";

// Check if user is already logged in
if (isLoggedIn()) {
    redirect("dashboard.php");
}

// Initialize variables
$username = "";
$error = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Verify CSRF token
    if (!isset($_POST["csrf_token"]) || !validateCSRFToken($_POST["csrf_token"])) {
        $error = "Security validation failed. Please try again.";
    } else {
        // Get form data
        $username = sanitizeInput($_POST["username"]);
        $password = $_POST["password"];
        
        // Validate form data
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } else {
            // Check if account is locked
            $lockStatus = isAccountLocked($username);
            if ($lockStatus !== false) {
                $error = $lockStatus;
            } else {
                // Check if user exists
                $query = "SELECT id, username, password, role, password_change_required, 
                          account_status, login_attempts 
                          FROM users 
                          WHERE username = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":username", $username);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if account is active
                    if ($user["account_status"] !== "active") {
                        $error = "Your account is not active. Please contact the administrator.";
                        logSecurityEvent("login_failed", $username, "Account not active");
                    } else {
                        // Verify password
                        if (password_verify($password, $user["password"])) {
                            // Login successful
                            
                            // Reset failed login attempts
                            resetFailedLoginAttempts($username);
                            
                            // Set session variables
                            $_SESSION["user_id"] = $user["id"];
                            $_SESSION["username"] = $user["username"];
                            $_SESSION["role"] = $user["role"];
                            
                            // Regenerate session ID for security
                            session_regenerate_id(true);
                            
                            // Log successful login
                            logSecurityEvent("login_success", $username);
                            
                            // Redirect to dashboard or password change page
                            if ($user["password_change_required"] == 1) {
                                redirect("change_password.php?required=1");
                            } else {
                                redirect("dashboard.php");
                            }
                        } else {
                            // Increment failed login attempts
                            incrementFailedLoginAttempts($username);
                            
                            // Get updated user data
                            $query = "SELECT login_attempts FROM users WHERE username = :username";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(":username", $username);
                            $stmt->execute();
                            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Check if account should be locked
                            if ($updatedUser["login_attempts"] >= MAX_LOGIN_ATTEMPTS) {
                                $error = "Too many failed login attempts. Your account has been locked for " . (ACCOUNT_LOCKOUT_TIME / 60) . " minutes.";
                                logSecurityEvent("account_locked", $username, "Too many failed login attempts");
                            } else {
                                $error = "Invalid username or password. Remaining attempts: " . (MAX_LOGIN_ATTEMPTS - $updatedUser["login_attempts"]) . "."; 
                                logSecurityEvent("login_failed", $username, "Invalid password");
                            }
                        }
                    }
                } else {
                    // User not found
                    $error = "Invalid username or password.";
                    logSecurityEvent("login_failed", $username, "Username not found");
                }
            }
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .form-signin .card-header {
            border-radius: 10px 10px 0 0;
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,.125);
            text-align: center;
            padding: 1.5rem;
        }
        .form-signin .card-body {
            padding: 2rem;
        }
        .logo-img {
            max-width: 200px;
            margin-bottom: 1rem;
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body>
    <main class="form-signin">
        <div class="card">
            <div class="card-header">
                <img src="../assets/images/logo.png" alt="Logo" class="logo-img">
                <h2>Certificate Validation System</h2>
                <p class="text-muted">Administrator Login</p>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                        <label for="username">Username</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    
                    <button class="w-100 btn btn-lg btn-primary" type="submit">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Log in
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <a href="../index.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
        
        <p class="mt-4 mb-3 text-muted text-center">&copy; <?php echo date("Y"); ?> Certificate Validation System</p>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
    
    // Backup the original file
    $backupFile = "login.php.bak." . time();
    file_put_contents($backupFile, $content);
    
    // Write the new content
    if (file_put_contents($loginFile, $newContent)) {
        echo "<p style='color:green'>✅ Successfully fixed login.php</p>";
        echo "<p>Original file backed up as $backupFile</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to update login.php</p>";
    }
} else {
    echo "<p style='color:red'>❌ login.php file not found</p>";
}

// Fix verify.php if it exists
$verifyFile = "../verify.php";
if (file_exists($verifyFile)) {
    $content = file_get_contents($verifyFile);
    
    // Replace multiple include statements with a single master include
    if (strpos($content, "require_once 'config/config.php';") !== false) {
        $newContent = preg_replace(
            '/require_once\s+\'config\/config\.php\'\s*;\s*require_once\s+\'config\/database\.php\'\s*;\s*require_once\s+\'includes\/functions\.php\'/s',
            "require_once 'config/constants.php';\nrequire_once 'config/config.php';\nrequire_once 'config/database.php';\nrequire_once 'includes/functions.php'",
            $content
        );
        
        // Backup the original file
        $backupFile = "../verify.php.bak." . time();
        file_put_contents($backupFile, $content);
        
        // Write the new content
        if (file_put_contents($verifyFile, $newContent)) {
            echo "<p style='color:green'>✅ Successfully fixed verify.php</p>";
            echo "<p>Original file backed up as $backupFile</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update verify.php</p>";
        }
    } else {
        echo "<p style='color:green'>✅ verify.php doesn't need modifications</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ verify.php file not found</p>";
}

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Try accessing the <a href='login.php' target='_blank'>login page</a></li>";
echo "<li>If you're still seeing errors, try <a href='admin_fix.php' target='_blank'>running the comprehensive fix tool</a></li>";
echo "<li>Ensure constants are properly defined in config/constants.php</li>";
echo "<li>Check that master_init.php correctly includes all required files</li>";
echo "</ol>";

echo "<p><strong>Login page has been fixed to use the master initialization file, which should resolve the constant redefinition errors.</strong></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    h1, h3 {
        color: #2c3e50;
    }
    p {
        margin: 10px 0;
    }
    a {
        color: #3498db;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
    ol, ul {
        margin-left: 20px;
    }
    li {
        margin-bottom: 5px;
    }
</style>
