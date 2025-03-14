<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Verification Detail Page
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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Verification ID is required');
    redirect('verification_logs.php');
}

$id = $_GET['id'];

// Get verification details
$query = "SELECT v.*, c.full_name, c.course_name, c.issue_date, c.expiry_date, c.is_active 
          FROM verification_logs v 
          JOIN certificates c ON v.certificate_id = c.certificate_id 
          WHERE v.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    setFlashMessage('error', 'Verification not found');
    redirect('verification_logs.php');
}

$verification = $stmt->fetch(PDO::FETCH_ASSOC);

// Get device information
$deviceInfo = json_decode($verification['user_agent_data'] ?? '{}', true);

// Get geolocation data (if available)
$geoData = json_decode($verification['geo_data'] ?? '{}', true);

// Get other verifications for this certificate
$query = "SELECT * FROM verification_logs 
          WHERE certificate_id = :certificate_id AND id != :id 
          ORDER BY verified_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':certificate_id', $verification['certificate_id']);
$stmt->bindParam(':id', $id);
$stmt->execute();
$otherVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Verification Details";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Details - CertifyPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header pt-3 pb-2 mb-4">
                    <div>
                        <h1>Verification Details</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="verification_logs.php">Verification Logs</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Details</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <a href="verification_logs.php" class="btn btn-outline-primary me-2">
                            <i class="bi bi-arrow-left"></i> Back to Logs
                        </a>
                        <button class="btn btn-primary export-data" data-export-type="pdf" data-export-target="Verification Report">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Export as PDF
                        </button>
                    </div>
                </div>
                
                <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Verification Summary -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Verification Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="verification-status mb-4">
                                    <div class="d-flex align-items-center">
                                        <div class="verification-badge <?php echo $verification['is_active'] ? 'success' : 'danger'; ?>">
                                            <i class="bi <?php echo $verification['is_active'] ? 'bi-shield-check' : 'bi-shield-x'; ?>"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h4 class="mb-1">Certificate <?php echo $verification['is_active'] ? 'Successfully Verified' : 'Verification Failed'; ?></h4>
                                            <p class="text-muted mb-0">
                                                Verified on <?php echo date('F j, Y \a\t g:i A', strtotime($verification['verified_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless detail-table">
                                            <tbody>
                                                <tr>
                                                    <th>Certificate ID:</th>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($verification['certificate_id']); ?></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Recipient:</th>
                                                    <td><?php echo htmlspecialchars($verification['full_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Course/Program:</th>
                                                    <td><?php echo htmlspecialchars($verification['course_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Issue Date:</th>
                                                    <td><?php echo date('F j, Y', strtotime($verification['issue_date'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Expiry Date:</th>
                                                    <td>
                                                        <?php if ($verification['expiry_date']): ?>
                                                            <?php echo date('F j, Y', strtotime($verification['expiry_date'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No expiry date</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless detail-table">
                                            <tbody>
                                                <tr>
                                                    <th>Verification Time:</th>
                                                    <td><?php echo date('F j, Y g:i:s A', strtotime($verification['verified_at'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>IP Address:</th>
                                                    <td><?php echo htmlspecialchars($verification['ip_address']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Status:</th>
                                                    <td>
                                                        <?php if ($verification['is_active']): ?>
                                                            <span class="badge bg-success">Valid</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Revoked</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Verification Method:</th>
                                                    <td>
                                                        <?php if (strpos($verification['referrer'] ?? '', 'qr') !== false): ?>
                                                            <span class="badge bg-info">QR Code Scan</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Direct Entry</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Referrer:</th>
                                                    <td>
                                                        <?php if (!empty($verification['referrer'])): ?>
                                                            <?php echo htmlspecialchars($verification['referrer']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Direct access</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Device Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Device Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless detail-table">
                                            <tbody>
                                                <tr>
                                                    <th>Device Type:</th>
                                                    <td>
                                                        <?php 
                                                        $deviceType = $deviceInfo['device_type'] ?? '';
                                                        $deviceIcon = 'bi-laptop';
                                                        
                                                        if (stripos($deviceType, 'mobile') !== false || 
                                                            stripos($verification['user_agent'] ?? '', 'mobile') !== false) {
                                                            $deviceType = 'Mobile';
                                                            $deviceIcon = 'bi-phone';
                                                        } elseif (stripos($deviceType, 'tablet') !== false || 
                                                                 stripos($verification['user_agent'] ?? '', 'tablet') !== false) {
                                                            $deviceType = 'Tablet';
                                                            $deviceIcon = 'bi-tablet';
                                                        } else {
                                                            $deviceType = 'Desktop';
                                                        }
                                                        ?>
                                                        <i class="bi <?php echo $deviceIcon; ?> me-1"></i>
                                                        <?php echo $deviceType; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Browser:</th>
                                                    <td>
                                                        <?php 
                                                        $browser = $deviceInfo['browser'] ?? '';
                                                        if (empty($browser)) {
                                                            $ua = $verification['user_agent'] ?? '';
                                                            if (stripos($ua, 'chrome') !== false) $browser = 'Chrome';
                                                            elseif (stripos($ua, 'firefox') !== false) $browser = 'Firefox';
                                                            elseif (stripos($ua, 'safari') !== false) $browser = 'Safari';
                                                            elseif (stripos($ua, 'edge') !== false) $browser = 'Edge';
                                                            elseif (stripos($ua, 'opera') !== false) $browser = 'Opera';
                                                            else $browser = 'Unknown';
                                                        }
                                                        echo $browser;
                                                        ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Operating System:</th>
                                                    <td><?php echo $deviceInfo['os'] ?? 'Unknown'; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless detail-table">
                                            <tbody>
                                                <tr>
                                                    <th>Screen Resolution:</th>
                                                    <td><?php echo $deviceInfo['screen_resolution'] ?? 'Unknown'; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Language:</th>
                                                    <td><?php echo $deviceInfo['language'] ?? 'Unknown'; ?></td>
                                                </tr>
                                                <tr>
                                                    <th>User Agent:</th>
                                                    <td>
                                                        <div class="text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($verification['user_agent'] ?? ''); ?>">
                                                            <?php echo htmlspecialchars($verification['user_agent'] ?? 'Not recorded'); ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Other Verifications -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Other Verifications of This Certificate</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (count($otherVerifications) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>IP Address</th>
                                                <th>Device</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($otherVerifications as $otherVerif): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y g:i A', strtotime($otherVerif['verified_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($otherVerif['ip_address']); ?></td>
                                                <td>
                                                    <?php 
                                                    $ua = $otherVerif['user_agent'] ?? '';
                                                    if (stripos($ua, 'mobile') !== false) echo '<i class="bi bi-phone me-1"></i> Mobile';
                                                    elseif (stripos($ua, 'tablet') !== false) echo '<i class="bi bi-tablet me-1"></i> Tablet';
                                                    else echo '<i class="bi bi-laptop me-1"></i> Desktop';
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="verification_detail.php?id=<?php echo $otherVerif['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="p-4 text-center">
                                    <p class="text-muted mb-0">No other verifications found for this certificate</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Geolocation Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Geolocation Information</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($geoData) && isset($geoData['country'])): ?>
                                <div class="geo-info">
                                    <div class="mb-3 text-center">
                                        <div class="geo-map mb-3">
                                            <img src="https://maps.googleapis.com/maps/api/staticmap?center=<?php echo $geoData['latitude'] ?? 0; ?>,<?php echo $geoData['longitude'] ?? 0; ?>&zoom=10&size=350x200&markers=color:red%7C<?php echo $geoData['latitude'] ?? 0; ?>,<?php echo $geoData['longitude'] ?? 0; ?>&key=YOUR_API_KEY" 
                                                 alt="Location Map" class="img-fluid rounded">
                                            <div class="geo-map-overlay">
                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    Map preview is disabled in demo mode
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <table class="table table-borderless detail-table">
                                        <tbody>
                                            <tr>
                                                <th><i class="bi bi-geo-alt me-2"></i> Country:</th>
                                                <td><?php echo htmlspecialchars($geoData['country'] ?? 'Unknown'); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-building me-2"></i> City:</th>
                                                <td><?php echo htmlspecialchars($geoData['city'] ?? 'Unknown'); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-globe me-2"></i> Region:</th>
                                                <td><?php echo htmlspecialchars($geoData['region'] ?? 'Unknown'); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-pin-map me-2"></i> Postal Code:</th>
                                                <td><?php echo htmlspecialchars($geoData['postal_code'] ?? 'Unknown'); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-compass me-2"></i> Coordinates:</th>
                                                <td>
                                                    <?php if (isset($geoData['latitude']) && isset($geoData['longitude'])): ?>
                                                        <?php echo $geoData['latitude']; ?>, <?php echo $geoData['longitude']; ?>
                                                    <?php else: ?>
                                                        Unknown
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th><i class="bi bi-clock me-2"></i> Timezone:</th>
                                                <td><?php echo htmlspecialchars($geoData['timezone'] ?? 'Unknown'); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="bi bi-geo-alt-fill text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0 text-muted">Geolocation data is not available for this verification</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Certificate Actions -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Certificate Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="view_certificate.php?id=<?php echo $verification['id']; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-eye me-2"></i> View Certificate
                                    </a>
                                    <a href="../verify/index.php?id=<?php echo $verification['certificate_id']; ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-shield-check me-2"></i> Verify Certificate
                                    </a>
                                    <a href="edit_certificate.php?id=<?php echo $verification['id']; ?>" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil me-2"></i> Edit Certificate
                                    </a>
                                    <?php if ($verification['is_active']): ?>
                                    <button type="button" class="btn btn-outline-danger delete-confirm" 
                                            data-bs-toggle="modal" data-bs-target="#revokeModal">
                                        <i class="bi bi-shield-x me-2"></i> Revoke Certificate
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-success" 
                                            data-bs-toggle="modal" data-bs-target="#activateModal">
                                        <i class="bi bi-shield-check me-2"></i> Activate Certificate
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Verification QR Code -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Verification QR Code</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="qr-code-container mb-3">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode(BASE_URL . 'verify/index.php?id=' . $verification['certificate_id']); ?>" 
                                         alt="Certificate QR Code" class="img-fluid">
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode(BASE_URL . 'verify/index.php?id=' . $verification['certificate_id']); ?>" 
                                       download="certificate-<?php echo $verification['certificate_id']; ?>.png" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i> Download QR Code
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Revoke Certificate Modal -->
    <div class="modal fade" id="revokeModal" tabindex="-1" aria-labelledby="revokeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="revokeModalLabel">Revoke Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Warning: Revoking this certificate will make it invalid for verification. This action can be reversed later.
                    </div>
                    <form id="revokeCertificateForm" action="process_certificate.php" method="post">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="certificate_id" value="<?php echo $verification['certificate_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="revoke_reason" class="form-label">Reason for Revocation</label>
                            <select class="form-select" id="revoke_reason" name="revoke_reason" required>
                                <option value="">Select a reason...</option>
                                <option value="error">Certificate issued in error</option>
                                <option value="fraud">Fraudulent activity</option>
                                <option value="replacement">Replaced by new certificate</option>
                                <option value="expiration">Certificate expired</option>
                                <option value="other">Other reason</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="revoke_notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="revoke_notes" name="revoke_notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="revokeCertificateForm" class="btn btn-danger">Revoke Certificate</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Activate Certificate Modal -->
    <div class="modal fade" id="activateModal" tabindex="-1" aria-labelledby="activateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activateModalLabel">Activate Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        You are about to reactivate this certificate. It will become valid for verification again.
                    </div>
                    <form id="activateCertificateForm" action="process_certificate.php" method="post">
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="certificate_id" value="<?php echo $verification['certificate_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="activate_notes" class="form-label">Activation Notes</label>
                            <textarea class="form-control" id="activate_notes" name="activate_notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="activateCertificateForm" class="btn btn-success">Activate Certificate</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
