<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Analytics Dashboard
 */

// Include master initialization file which already includes session.php
require_once 'master_init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get admin information
$query = "SELECT username, role, email, last_login FROM admins WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Get revoked certificate count
$query = "SELECT COUNT(*) as revoked FROM certificates WHERE is_active = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$revokedCertificateCount = $stmt->fetch(PDO::FETCH_ASSOC)['revoked'];

// Get verification count
$query = "SELECT COUNT(*) as total FROM verification_logs";
$stmt = $db->prepare($query);
$stmt->execute();
$verificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get today's verification count
$query = "SELECT COUNT(*) as today FROM verification_logs WHERE DATE(verified_at) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$todayVerificationCount = $stmt->fetch(PDO::FETCH_ASSOC)['today'];

// Get monthly verification data for the past 6 months
$query = "SELECT 
            DATE_FORMAT(verified_at, '%b %Y') as month,
            COUNT(*) as count
          FROM 
            verification_logs
          WHERE 
            verified_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY 
            DATE_FORMAT(verified_at, '%Y-%m')
          ORDER BY 
            DATE_FORMAT(verified_at, '%Y-%m') ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$monthlyVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for chart JS
$months = [];
$verificationCounts = [];
foreach ($monthlyVerifications as $data) {
    $months[] = $data['month'];
    $verificationCounts[] = $data['count'];
}

// Get daily verification data for the past 10 days
$query = "SELECT 
            DATE_FORMAT(verified_at, '%d %b') as day,
            COUNT(*) as count
          FROM 
            verification_logs
          WHERE 
            verified_at >= DATE_SUB(NOW(), INTERVAL 10 DAY)
          GROUP BY 
            DATE(verified_at)
          ORDER BY 
            DATE(verified_at) ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$dailyVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for chart JS
$days = [];
$dailyCounts = [];
foreach ($dailyVerifications as $data) {
    $days[] = $data['day'];
    $dailyCounts[] = $data['count'];
}

// Get certificate issuance data by month
$query = "SELECT 
            DATE_FORMAT(created_at, '%b %Y') as month,
            COUNT(*) as count
          FROM 
            certificates
          WHERE 
            created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY 
            DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY 
            DATE_FORMAT(created_at, '%Y-%m') ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$monthlyCertificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for chart JS
$certMonths = [];
$certificateIssueCounts = [];
foreach ($monthlyCertificates as $data) {
    $certMonths[] = $data['month'];
    $certificateIssueCounts[] = $data['count'];
}

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
$query = "SELECT v.id, v.certificate_id, v.ip_address, v.verified_at AS verified_at, c.holder_name AS full_name 
          FROM verification_logs v 
          LEFT JOIN certificates c ON v.certificate_id = c.certificate_id 
          ORDER BY v.verified_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recentVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Analytics Dashboard";?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CertifyPro Analytics - Premium Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php';?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php';?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div id="alert-container"></div>
                
                <div class="page-header pt-3 pb-2 mb-4">
                    <div>
                        <h1>Analytics Dashboard</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Analytics</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <button class="btn btn-outline-primary me-2" id="refresh-analytics">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Data
                        </button>
                        <button class="btn btn-primary" id="export-analytics">
                            <i class="bi bi-download me-1"></i> Export Analytics
                        </button>
                    </div>
                </div>
                
                <?php if ($flash = getFlashMessage()):;?>
                <div class="alert alert-<?php echo $flash['type'];?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message'];?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif;?>
                
                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <label for="dashboard-date-range" class="form-label mb-0">Date Range:</label>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="dashboard-date-range">
                                    <option value="7days">Last 7 Days</option>
                                    <option value="30days" selected>Last 30 Days</option>
                                    <option value="90days">Last 90 Days</option>
                                    <option value="year">Last Year</option>
                                    <option value="all">All Time</option>
                                </select>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <div class="d-inline-block me-3">
                                    <span class="badge bg-primary p-2">Total Certificates: <?php echo $certificateCount;?></span>
                                </div>
                                <div class="d-inline-block">
                                    <span class="badge bg-success p-2">Total Verifications: <?php echo $verificationCount;?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Key Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card primary">
                            <div class="stats-card-icon">
                                <i class="bi bi-award"></i>
                            </div>
                            <div class="stats-card-title">Total Certificates</div>
                            <div class="stats-card-value"><?php echo $certificateCount;?></div>
                            <a href="certificates.php" class="stats-card-link">View all <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-card-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stats-card-title">Active Certificates</div>
                            <div class="stats-card-value"><?php echo $activeCertificateCount;?></div>
                            <a href="certificates.php?status=active" class="stats-card-link">View active <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-card-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="stats-card-title">Total Verifications</div>
                            <div class="stats-card-value"><?php echo $verificationCount;?></div>
                            <a href="verification_logs.php" class="stats-card-link">View logs <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-card-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div class="stats-card-title">Today's Verifications</div>
                            <div class="stats-card-value"><?php echo $todayVerificationCount;?></div>
                            <a href="verification_logs.php?period=today" class="stats-card-link">View today <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Charts -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Verification Trends</h5>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" id="monthly-chart-btn">Monthly</button>
                                    <button type="button" class="btn btn-outline-primary" id="daily-chart-btn">Daily</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="verificationsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Certificate Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                                <div class="row text-center mt-3">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="p-2 bg-success rounded me-2" style="width: 12px; height: 12px;"></div>
                                            <span>Active: <?php echo $activeCertificateCount;?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="p-2 bg-danger rounded me-2" style="width: 12px; height: 12px;"></div>
                                            <span>Revoked: <?php echo $revokedCertificateCount;?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Certificate Issuance Trends</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="certificateChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Verification Heat Map</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container text-center">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Geographic verification data visualization will be available in the next update.
                                    </div>
                                    <img src="assets/img/heatmap-preview.jpg" alt="Verification Heat Map Preview" class="img-fluid rounded" style="max-height: 220px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Certificates</h5>
                                <a href="certificates.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-responsive-cards mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Created By</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recentCertificates) > 0):;?>
                                                <?php foreach ($recentCertificates as $cert):;?>
                                                <tr>
                                                    <td data-label="ID"><?php echo substr($cert['certificate_id'], 0, 8) . '...';?></td>
                                                    <td data-label="Name"><?php echo htmlspecialchars($cert['full_name']);?></td>
                                                    <td data-label="Created By"><?php echo htmlspecialchars($cert['username']);?></td>
                                                    <td data-label="Date"><?php echo date('d M Y', strtotime($cert['created_at']));?></td>
                                                    <td data-label="Actions">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="view_certificate.php?id=<?php echo $cert['id'];?>" class="btn btn-outline-primary">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="edit_certificate.php?id=<?php echo $cert['id'];?>" class="btn btn-outline-primary">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="../verify/index.php?id=<?php echo $cert['certificate_id'];?>" target="_blank" class="btn btn-outline-primary">
                                                                <i class="bi bi-shield-check"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach;?>
                                            <?php else:;?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No certificates found</td>
                                                </tr>
                                            <?php endif;?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Verifications</h5>
                                <a href="verification_logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-responsive-cards mb-0">
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
                                            <?php if (count($recentVerifications) > 0):;?>
                                                <?php foreach ($recentVerifications as $verification):;?>
                                                <tr>
                                                    <td data-label="Certificate"><?php echo substr($verification['certificate_id'], 0, 8) . '...';?></td>
                                                    <td data-label="Name"><?php echo htmlspecialchars($verification['full_name']);?></td>
                                                    <td data-label="IP Address"><?php echo htmlspecialchars($verification['ip_address']);?></td>
                                                    <td data-label="Date"><?php echo date('d M Y H:i', strtotime($verification['verified_at']));?></td>
                                                    <td data-label="Actions">
                                                        <a href="verification_detail.php?id=<?php echo $verification['id'];?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-info-circle"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach;?>
                                            <?php else:;?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No verifications found</td>
                                                </tr>
                                            <?php endif;?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    
    <script>
        // Initial chart setup
        document.addEventListener('DOMContentLoaded', function() {
            // Verification trends chart - Monthly
            const verificationCtx = document.getElementById('verificationsChart').getContext('2d');
            const verificationChart = new Chart(verificationCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months);?>,
                    datasets: [{
                        label: 'Monthly Verifications',
                        data: <?php echo json_encode($verificationCounts);?>,
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(37, 99, 235, 1)',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            cornerRadius: 4,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            callbacks: {
                                label: function(context) {
                                    return `Verifications: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }}}}});
            
            // Certificate status chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Active', 'Revoked'],
                    datasets: [{
                        data: [<?php echo $activeCertificateCount;?>, <?php echo $revokedCertificateCount;?>],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)', // Green for active
                            'rgba(239, 68, 68, 0.8)'   // Red for revoked
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
            
            // Certificate issuance chart
            const certificateCtx = document.getElementById('certificateChart').getContext('2d');
            const certificateChart = new Chart(certificateCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($certMonths);?>,
                    datasets: [{
                        label: 'Certificates Issued',
                        data: <?php echo json_encode($certificateIssueCounts);?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }}}}});
            
            // Toggle between monthly and daily verification charts
            const monthlyBtn = document.getElementById('monthly-chart-btn');
            const dailyBtn = document.getElementById('daily-chart-btn');
            
            monthlyBtn.addEventListener('click', function() {
                monthlyBtn.classList.add('active');
                dailyBtn.classList.remove('active');
                
                verificationChart.data.labels = <?php echo json_encode($months);?>;
                verificationChart.data.datasets[0].data = <?php echo json_encode($verificationCounts);?>;
                verificationChart.data.datasets[0].label = 'Monthly Verifications';
                verificationChart.update();
            });
            
            dailyBtn.addEventListener('click', function() {
                dailyBtn.classList.add('active');
                monthlyBtn.classList.remove('active');
                
                verificationChart.data.labels = <?php echo json_encode($days);?>;
                verificationChart.data.datasets[0].data = <?php echo json_encode($dailyCounts);?>;
                verificationChart.data.datasets[0].label = 'Daily Verifications';
                verificationChart.update();
            });
        });
    </script>
</body>
</html>
