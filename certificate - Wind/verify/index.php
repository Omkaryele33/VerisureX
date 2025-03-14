<?php
/**
 * Certificate Verification System
 * Public verification interface
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Define root path for includes
define("ROOT_PATH", dirname(__DIR__));

// Include files in proper order
require_once ROOT_PATH . "/config/constants.php";
require_once ROOT_PATH . "/config/config.php";
require_once ROOT_PATH . "/config/database.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/security.php";

// Set security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; frame-src 'none'; object-src 'none'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Add these constants after the existing includes or at the beginning of the file, before they're used
// Define rate limit constants if not already defined
if (!defined('VERIFY_RATE_LIMIT')) {
    define('VERIFY_RATE_LIMIT', 5); // Maximum number of verification attempts
}

if (!defined('VERIFY_RATE_WINDOW')) {
    define('VERIFY_RATE_WINDOW', 300); // Time window in seconds (5 minutes)
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$certificateId = '';
$certificate = null;
$error = '';
$success = '';
$isVerified = false;
$verificationToken = '';

// Check if certificate ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize and validate certificate ID
    $certificateId = sanitizeInput($_GET['id']);
    if (!preg_match('/^[A-Za-z0-9\-]+$/', $certificateId)) {
        $error = 'Invalid certificate ID format.';
    } else {
        // Check for CSRF if this is a verification confirmation
        $isConfirmation = isset($_GET['confirm']) && $_GET['confirm'] == 1;
        if ($isConfirmation && (!isset($_GET['token']) || !validateCSRFToken($_GET['token']))) {
            $error = 'Invalid verification request. Please try again.';
        } else {
            // Enhanced rate limiting that combines IP and certificate ID
            $rateLimitKey = 'verify_' . md5($_SERVER['REMOTE_ADDR'] . '_' . $certificateId);
            if (isEnhancedRateLimited($rateLimitKey, VERIFY_RATE_LIMIT, VERIFY_RATE_WINDOW)) {
                $error = 'Too many verification attempts. Please try again in ' . (VERIFY_RATE_WINDOW / 60) . ' minutes.';
            } else {
                // Prepare statement to prevent SQL injection
                $query = "SELECT c.*, 
                          (SELECT COUNT(*) FROM certificate_verifications cv WHERE cv.certificate_id = c.certificate_id) as verify_count 
                          FROM certificates c 
                          WHERE c.certificate_id = :certificate_id";
                          
                $stmt = $db->prepare($query);
                $stmt->bindParam(':certificate_id', $certificateId);
                $stmt->execute();
                
                // Check if certificate exists
                if ($stmt->rowCount() > 0) {
                    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Check if certificate is active
                    if (!$certificate['is_active']) {
                        $error = 'This certificate has been revoked or deactivated.';
                    } 
                    // Check validation status if it exists in the database
                    else if (isset($certificate['validation_status']) && !$certificate['validation_status']) {
                        $error = 'This certificate is marked as invalid. Please contact the issuing authority for more information.';
                        $isVerified = false;
                    } 
                    // Check expiry date if it exists
                    else if (!empty($certificate['expiry_date']) && strtotime($certificate['expiry_date']) < time()) {
                        $error = 'This certificate has expired on ' . date('F j, Y', strtotime($certificate['expiry_date'])) . '.';
                        $isVerified = false;
                    } 
                    else {
                        $isVerified = true;
                        $success = 'Certificate verified successfully.';
                        
                        // Log verification with extended information
                        logEnhancedVerification($db, $certificateId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                        
                        // Verify digital signature if enabled
                        if (defined('ENABLE_DIGITAL_SIGNATURES') && ENABLE_DIGITAL_SIGNATURES) {
                            $certificateData = [
                                'certificate_id' => $certificate['certificate_id'],
                                'holder_name' => $certificate['holder_name'],
                                'course_name' => $certificate['course_name'],
                                'issue_date' => $certificate['issue_date']
                            ];
                            
                            // If signature doesn't match, show a warning
                            if (!verifyCertificateSignature($certificateData, $certificate['digital_signature'])) {
                                $error = 'Warning: Certificate data integrity check failed. The certificate might have been tampered with.';
                                $isVerified = false;
                            }
                        }
                    }
                } else {
                    $error = 'Invalid certificate. This certificate does not exist in our records.';
                }
            }
        }
    }
    
    // Generate a token for further verification actions if needed
    $verificationToken = generateCSRFToken();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Verify the authenticity of certificates issued by our institution">
    <meta name="robots" content="noindex, nofollow">
    <title>Certificate Verification - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .verification-container {
            max-width: 900px;
            margin: 2rem auto;
        }
        .logo {
            max-height: 80px;
            margin-bottom: 1rem;
        }
        .certificate-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .certificate-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('../assets/img/watermark.png');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 50%;
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
        }
        .certificate-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        .certificate-content {
            position: relative;
            z-index: 1;
        }
        .certificate-photo {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .verified-badge {
            display: inline-block;
            background-color: #28a745;
            color: white;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            margin-bottom: 1rem;
        }
        .verified-badge i {
            margin-right: 0.5rem;
        }
        .qr-scanner-container {
            display: none;
            margin-top: 1rem;
        }
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        #qr-reader__scan_region {
            background: white;
        }
        #qr-reader__dashboard_section_swaplink {
            display: none;
        }
        .certificate-details {
            background-color: rgba(248, 249, 250, 0.7);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: bold;
            min-width: 150px;
        }
        .detail-value {
            flex-grow: 1;
        }
        .grade-badge {
            display: inline-block;
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
        }
        .verification-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="text-center mb-4">
            <img src="../assets/img/logo.png" alt="Logo" class="logo">
            <h1>Certificate Verification</h1>
            <p class="lead">Verify the authenticity of certificates issued by our institute</p>
        </div>
        
        <?php if (!$isVerified): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5>Enter Certificate ID</h5>
                        <form method="get" class="mt-3">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="id" placeholder="Enter certificate ID" value="<?php echo htmlspecialchars($certificateId); ?>" required pattern="[A-Za-z0-9\-]+" title="Certificate ID should only contain letters, numbers, and hyphens">
                                <button class="btn btn-primary" type="submit">Verify</button>
                            </div>
                            <div class="form-text">Format: XX-XXXXXX-YYYY</div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h5>Scan QR Code</h5>
                        <button id="startScanner" class="btn btn-outline-primary mt-3">
                            <i class="bi bi-camera"></i> Start QR Scanner
                        </button>
                        <div id="qr-scanner-container" class="qr-scanner-container">
                            <div id="qr-reader"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <div class="certificate-container">
            <div class="certificate-header">
                <div class="verified-badge">
                    <i class="bi bi-check-circle-fill"></i> VERIFIED CERTIFICATE
                </div>
                <h2><?php echo htmlspecialchars($certificate['course_name']); ?></h2>
                <p class="fs-5">presented to</p>
                <h3 class="fw-bold"><?php echo htmlspecialchars($certificate['holder_name']); ?></h3>
            </div>
            
            <div class="certificate-content">
                <div class="row">
                    <div class="col-md-7">
                        <div class="certificate-details">
                            <div class="detail-row">
                                <div class="detail-label">Certificate ID:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($certificate['certificate_id']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Issue Date:</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($certificate['issue_date'])); ?></div>
                            </div>
                            <?php if (!empty($certificate['expiry_date'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Expiry Date:</div>
                                <div class="detail-value"><?php echo date('F j, Y', strtotime($certificate['expiry_date'])); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($certificate['certification_description'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Description:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($certificate['certification_description']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($certificate['grade'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Grade:</div>
                                <div class="detail-value"><span class="grade-badge"><?php echo htmlspecialchars($certificate['grade']); ?></span></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($certificate['issuer'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Issuing Authority:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($certificate['issuer']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="text-center mb-3">
                            <img src="../assets/img/qrcodes/<?php echo $certificate['certificate_id']; ?>.png" class="img-fluid" alt="Certificate QR Code">
                        </div>
                        <?php if (!empty($certificate['recipient_photo']) && file_exists('../uploads/photos/' . $certificate['recipient_photo'])): ?>
                        <div class="text-center">
                            <img src="../uploads/photos/<?php echo htmlspecialchars($certificate['recipient_photo']); ?>" class="certificate-photo" alt="Recipient Photo">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="verification-info">
                    <p class="mb-1"><strong>Verification Information:</strong></p>
                    <p class="mb-1">This certificate was verified on <?php echo date('F j, Y, g:i A'); ?></p>
                    <p class="mb-1">Total verification count: <?php echo $certificate['verify_count'] + 1; ?></p>
                    <p class="mb-0">To verify this certificate again, please visit: <?php echo VERIFY_URL; ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.0.9/dist/html5-qrcode.min.js"></script>
    <script>
        // QR Code scanner functionality
        document.getElementById('startScanner').addEventListener('click', function() {
            const scannerContainer = document.getElementById('qr-scanner-container');
            
            if (scannerContainer.style.display === 'block') {
                scannerContainer.style.display = 'none';
                this.innerHTML = '<i class="bi bi-camera"></i> Start QR Scanner';
                if (window.html5QrCode) {
                    window.html5QrCode.stop();
                }
            } else {
                scannerContainer.style.display = 'block';
                this.innerHTML = '<i class="bi bi-camera-video-off"></i> Stop Scanner';
                startScanner();
            }
        });
        
        function startScanner() {
            const html5QrCode = new Html5Qrcode("qr-reader");
            window.html5QrCode = html5QrCode;
            
            const qrCodeSuccessCallback = (decodedText) => {
                console.log(`QR Code detected: ${decodedText}`);
                html5QrCode.stop();
                
                // Extract certificate ID from the URL if it's a verification URL
                let certificateId = decodedText;
                if (decodedText.includes('?id=')) {
                    certificateId = decodedText.split('?id=')[1].split('&')[0];
                }
                
                // Redirect to verification with the certificate ID
                window.location.href = 'index.php?id=' + encodeURIComponent(certificateId) + '&token=<?php echo $verificationToken; ?>&confirm=1';
            };
            
            const config = { fps: 10, qrbox: 250 };
            
            html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback);
        }
        
        // Prevent multiple form submissions
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>

<?php
/**
 * Enhanced verification logging
 */
function logEnhancedVerification($db, $certificateId, $ipAddress, $userAgent) {
    try {

        // Check if certificate_verifications table exists
        try {
            $db->query("SELECT 1 FROM certificate_verifications LIMIT 1");
        } catch (PDOException $e) {
            // Table doesn't exist, log and return
            error_log("certificate_verifications table is missing: " . $e->getMessage());
            return false;
        }
        // Get country by IP (simplified)
        $country = 'Unknown';
        if (function_exists('geoip_country_code_by_name')) {
            $countryCode = geoip_country_code_by_name($ipAddress);
            if ($countryCode) {
                $country = $countryCode;
            }
        }
        
        // Detect device type
        $deviceType = 'Unknown';
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($userAgent, 0, 4))) {
            $deviceType = 'Mobile';
        } else if (preg_match('/android|ipad|playbook|silk/i', $userAgent)) {
            $deviceType = 'Tablet';
        } else {
            $deviceType = 'Desktop';
        }
        
        // Browser detection (simplified)
        $browser = 'Unknown';
        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }
        
        // Get HTTP referrer
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Insert into database
        $query = "INSERT INTO certificate_verifications (certificate_id, ip_address, user_agent, 
                  country, device_type, browser, http_referrer, created_at) 
                  VALUES (:certificate_id, :ip_address, :user_agent, 
                  :country, :device_type, :browser, :http_referrer, NOW())";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':certificate_id', $certificateId);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);
        $stmt->bindParam(':country', $country);
        $stmt->bindParam(':device_type', $deviceType);
        $stmt->bindParam(':browser', $browser);
        $stmt->bindParam(':http_referrer', $referrer);
        $stmt->execute();
        
        // Update verification count on certificate
        $query = "UPDATE certificates SET 
                  verification_count = verification_count + 1, 
                  last_verified = NOW() 
                  WHERE certificate_id = :certificate_id";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':certificate_id', $certificateId);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log('Error logging verification: ' . $e->getMessage());
        return false;
    }
}
