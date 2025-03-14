<?php
/**
 * Certificate Validation System
 * Main entry point
 */

// Start session
require_once('./includes/session.php');

// Include configuration files
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Get certificate count
$query = "SELECT COUNT(*) as total FROM certificates";
$stmt = $db->prepare($query);
$stmt->execute();
$certificateCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get active certificate count
$query = "SELECT COUNT(*) as active FROM certificates WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$activeCertificateCount = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// Get verification count
$query = "SELECT COUNT(*) as total FROM verification_logs";
$stmt = $db->prepare($query);
$stmt->execute();
$verificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent certificates
$query = "SELECT c.id, c.certificate_id, c.holder_name AS full_name, c.created_at, a.username 
          FROM certificates c 
          LEFT JOIN admins a ON c.created_by = a.id 
          ORDER BY c.created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recentCertificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent verifications
$query = "SELECT v.id, v.certificate_id, v.ip_address, v.verified_at, c.holder_name AS full_name 
          FROM verification_logs v 
          LEFT JOIN certificates c ON v.certificate_id = c.certificate_id 
          ORDER BY v.verified_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recentVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize any necessary variables or configurations here
$pageTitle = "Certificate Management System";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin/assets/css/admin.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #fff;
            border-right: 1px solid #e5e7eb;
            padding: 20px 0;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: #f3f4f6;
            color: #1d4ed8;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .stats-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stats-card.primary {
            background-color: #3b82f6;
            color: white;
        }
        .stats-card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #3b82f6;
        }
        .stats-card.primary .stats-card-icon {
            color: white;
        }
        .stats-card-title {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 5px;
            color: #6b7280;
        }
        .stats-card.primary .stats-card-title {
            color: rgba(255,255,255,0.8);
        }
        .stats-card-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .stats-card-link {
            font-size: 0.875rem;
            color: #3b82f6;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .stats-card.primary .stats-card-link {
            color: white;
        }
        .stats-card-link i {
            margin-left: 5px;
            transition: transform 0.2s;
        }
        .stats-card-link:hover i {
            transform: translateX(3px);
        }
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            padding: 15px 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h5 class="text-center mb-4 mt-2">Certificate Admin</h5>
                <div class="mb-4">
                    <a href="index.php" class="sidebar-link active">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                    <a href="admin/certificates.php" class="sidebar-link">
                        <i class="bi bi-card-checklist"></i> Certificates
                    </a>
                    <a href="admin/create_certificate.php" class="sidebar-link">
                        <i class="bi bi-plus-circle"></i> Create Certificate
                    </a>
                    <a href="admin/bulk_certificates.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-plus"></i> Bulk Certificates
                    </a>
                    <a href="admin/templates.php" class="sidebar-link">
                        <i class="bi bi-file-earmark-text"></i> Templates
                    </a>
                    <a href="admin/verification_logs.php" class="sidebar-link">
                        <i class="bi bi-clock-history"></i> Verification Logs
                    </a>
                    <a href="admin/users.php" class="sidebar-link">
                        <i class="bi bi-people"></i> Users
                    </a>
                </div>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Advanced</span>
                </h6>
                <div>
                    <a href="admin/api_management.php" class="sidebar-link">
                        <i class="bi bi-code-square"></i> API Management
                    </a>
                    <a href="admin/settings.php" class="sidebar-link">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                    <a href="admin/security_log.php" class="sidebar-link">
                        <i class="bi bi-shield-check"></i> Security Log
                    </a>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Dashboard</h1>
                    <div>
                        <a href="admin/create_certificate.php" class="btn btn-primary me-2">Create Certificate</a>
                        <a href="admin/bulk_certificates.php" class="btn btn-outline-primary">Bulk Generation</a>
                    </div>
                </div>
                
                <?php
                // Display any session messages
                $message = getMessage();
                if ($message): ?>
                    <div class="alert alert-<?php echo $message['type']; ?>" role="alert">
                        <?php echo $message['text']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card primary">
                            <div class="stats-card-icon">
                                <i class="bi bi-award"></i>
                            </div>
                            <div class="stats-card-title">Total Certificates</div>
                            <div class="stats-card-value"><?php echo $certificateCount; ?></div>
                            <a href="admin/certificates.php" class="stats-card-link">View all <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-card-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stats-card-title">Active Certificates</div>
                            <div class="stats-card-value"><?php echo $activeCertificateCount; ?></div>
                            <a href="admin/certificates.php?status=active" class="stats-card-link">View active <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="stats-card-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="stats-card-title">Total Verifications</div>
                            <div class="stats-card-value"><?php echo $verificationCount; ?></div>
                            <a href="admin/verification_logs.php" class="stats-card-link">View logs <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <!-- Recent Certificates -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Certificates</h5>
                                <a href="admin/certificates.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Recipient</th>
                                                <th>Created By</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recentCertificates) > 0): ?>
                                                <?php foreach ($recentCertificates as $cert): ?>
                                                <tr>
                                                    <td><?php echo substr($cert['certificate_id'], 0, 8) . '...'; ?></td>
                                                    <td><?php echo htmlspecialchars($cert['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($cert['username'] ?? 'System'); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($cert['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="admin/view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-outline-primary">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="verify/index.php?id=<?php echo $cert['certificate_id']; ?>" target="_blank" class="btn btn-outline-primary">
                                                                <i class="bi bi-shield-check"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No certificates found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Verifications -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Verifications</h5>
                                <a href="admin/verification_logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Certificate</th>
                                                <th>Name</th>
                                                <th>IP Address</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recentVerifications) > 0): ?>
                                                <?php foreach ($recentVerifications as $verification): ?>
                                                <tr>
                                                    <td><?php echo substr($verification['certificate_id'], 0, 8) . '...'; ?></td>
                                                    <td><?php echo htmlspecialchars($verification['full_name'] ?? 'Unknown'); ?></td>
                                                    <td><?php echo htmlspecialchars($verification['ip_address']); ?></td>
                                                    <td><?php echo date('d M Y H:i', strtotime($verification['verified_at'])); ?></td>
                                                    <td>
                                                        <a href="admin/verification_detail.php?id=<?php echo $verification['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-info-circle"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No verifications found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <a href="admin/create_certificate.php" class="btn btn-outline-primary d-block py-3">
                                    <i class="bi bi-plus-circle fs-4 d-block mb-2"></i>
                                    Create Certificate
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin/certificates.php" class="btn btn-outline-primary d-block py-3">
                                    <i class="bi bi-list-check fs-4 d-block mb-2"></i>
                                    Manage Certificates
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="verify/index.php" class="btn btn-outline-primary d-block py-3">
                                    <i class="bi bi-shield-check fs-4 d-block mb-2"></i>
                                    Verify Certificate
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="admin/login.php" class="btn btn-outline-primary d-block py-3">
                                    <i class="bi bi-box-arrow-in-right fs-4 d-block mb-2"></i>
                                    Admin Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
