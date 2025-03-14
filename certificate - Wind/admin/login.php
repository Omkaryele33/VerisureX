<?php
/**
 * Admin Panel - Login Page
 */

// Include master initialization file which already includes session.php
require_once 'master_init.php';

// Set page title
$pageTitle = 'Admin Login';

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
                try {
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
                                // Failed login - handle manually rather than calling the function
                                try {
                                    $query = "UPDATE users SET login_attempts = login_attempts + 1 WHERE username = :username";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':username', $username);
                                    $stmt->execute();
                                    
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
                                } catch (Exception $e) {
                                    $error = "System error. Please try again later.";
                                    error_log("Login error: " . $e->getMessage());
                                }
                            }
                        }
                    } else {
                        // User not found
                        $error = "Invalid username or password.";
                        logSecurityEvent("login_failed", $username, "Username not found");
                    }
                } catch (Exception $e) {
                    $error = "Database error. Please try again later.";
                    error_log("Login query error: " . $e->getMessage());
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
                
                <div class="text-center mt-3">
                    <a href="../index.php" class="text-decoration-none">Back to Homepage</a>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>