<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * Advanced Analytics Dashboard
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

// Set time zone
date_default_timezone_set('UTC');

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$filter = isset($_GET['filter']) ? $_GET['filter'] : '30days';

// Adjust dates based on filter
if ($filter == '7days') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
} elseif ($filter == '30days') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
} elseif ($filter == '90days') {
    $start_date = date('Y-m-d', strtotime('-90 days'));
} elseif ($filter == 'year') {
    $start_date = date('Y-m-d', strtotime('-1 year'));
}

// Get overall statistics
$query = "SELECT 
            (SELECT COUNT(*) FROM certificates) AS total_certificates,
            (SELECT COUNT(*) FROM certificates WHERE is_active = 1) AS active_certificates,
            (SELECT COUNT(*) FROM certificates WHERE is_active = 0) AS revoked_certificates,
            (SELECT COUNT(*) FROM verification_logs) AS total_verifications,
            (SELECT COUNT(*) FROM verification_logs WHERE DATE(verification_time) = CURDATE()) AS today_verifications";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get verification trend data (daily for selected period)
$query = "SELECT 
            DATE(verification_time) AS verification_date,
            COUNT(*) AS verification_count,
            SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) AS successful_count,
            SUM(CASE WHEN successful = 0 THEN 1 ELSE 0 END) AS failed_count
          FROM 
            verification_logs
          WHERE 
            verification_time BETWEEN :start_date AND :end_date_extended
          GROUP BY 
            DATE(verification_time)
          ORDER BY 
            verification_date ASC";
$stmt = $db->prepare($query);
$end_date_extended = date('Y-m-d', strtotime($end_date . ' +1 day')); // Include all of end_date
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date_extended', $end_date_extended);
$stmt->execute();
$verification_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format verification trend data for charts
$trend_dates = [];
$trend_counts = [];
$trend_success = [];
$trend_failed = [];

foreach ($verification_trend as $data) {
    $trend_dates[] = date('M d', strtotime($data['verification_date']));
    $trend_counts[] = (int)$data['verification_count'];
    $trend_success[] = (int)$data['successful_count'];
    $trend_failed[] = (int)$data['failed_count'];
}

// Get certificate issuance by month (last 6 months)
$query = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            COUNT(*) AS certificate_count
          FROM 
            certificates
          WHERE 
            created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY 
            DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY 
            month ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$issuance_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format issuance data for charts
$issuance_months = [];
$issuance_counts = [];

foreach ($issuance_by_month as $data) {
    $month_year = date('M Y', strtotime($data['month'] . '-01'));
    $issuance_months[] = $month_year;
    $issuance_counts[] = (int)$data['certificate_count'];
}

// Get most verified certificates
$query = "SELECT 
            c.certificate_id,
            c.full_name,
            c.course_name,
            COUNT(v.id) AS verification_count
          FROM 
            certificates c
          JOIN 
            verification_logs v ON c.certificate_id = v.certificate_id
          WHERE 
            v.verification_time BETWEEN :start_date AND :end_date_extended
          GROUP BY 
            c.certificate_id
          ORDER BY 
            verification_count DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date_extended', $end_date_extended);
$stmt->execute();
$most_verified = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get verification by device type
$query = "SELECT 
            COALESCE(device_type, 'Unknown') AS device_type,
            COUNT(*) AS count
          FROM 
            verification_logs
          WHERE 
            verification_time BETWEEN :start_date AND :end_date_extended
          GROUP BY 
            device_type
          ORDER BY 
            count DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date_extended', $end_date_extended);
$stmt->execute();
$device_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format device data for charts
$device_labels = [];
$device_counts = [];
$device_colors = [
    'Desktop' => '#4e73df',
    'Mobile' => '#1cc88a',
    'Tablet' => '#36b9cc',
    'Unknown' => '#f6c23e'
];

foreach ($device_stats as $data) {
    $device_labels[] = $data['device_type'];
    $device_counts[] = (int)$data['count'];
}

// Get verification by country
$query = "SELECT 
            COALESCE(country, 'Unknown') AS country,
            COUNT(*) AS count
          FROM 
            verification_logs
          WHERE 
            verification_time BETWEEN :start_date AND :end_date_extended
          GROUP BY 
            country
          ORDER BY 
            count DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date_extended', $end_date_extended);
$stmt->execute();
$country_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Analytics";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CertifyPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="page-header pt-3 pb-2 mb-4">
                    <div>
                        <h1><?php echo $pageTitle; ?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Analytics</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="#" id="exportPDF"><i class="bi bi-file-pdf"></i> Export as PDF</a></li>
                                <li><a class="dropdown-item" href="#" id="exportCSV"><i class="bi bi-file-excel"></i> Export as CSV</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-primary" id="printReport">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" action="analytics.php" class="row g-3 align-items-end">
                            <div class="col-md-auto">
                                <label class="form-label">Quick Filters</label>
                                <div class="btn-group" role="group">
                                    <a href="?filter=7days" class="btn btn-outline-secondary <?php echo $filter == '7days' ? 'active' : ''; ?>">7 Days</a>
                                    <a href="?filter=30days" class="btn btn-outline-secondary <?php echo $filter == '30days' ? 'active' : ''; ?>">30 Days</a>
                                    <a href="?filter=90days" class="btn btn-outline-secondary <?php echo $filter == '90days' ? 'active' : ''; ?>">90 Days</a>
                                    <a href="?filter=year" class="btn btn-outline-secondary <?php echo $filter == 'year' ? 'active' : ''; ?>">1 Year</a>
                                </div>
                            </div>
                            <div class="col-md-auto">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-auto">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-primary">Apply</button>
                                <a href="analytics.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-primary h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Certificates</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_certificates']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-award fa-2x text-primary-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-success h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Certificates</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['active_certificates']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle fa-2x text-success-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-warning h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Verifications</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_verifications']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-shield-check fa-2x text-warning-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-left-info h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today's Verifications</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['today_verifications']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-calendar-check fa-2x text-info-light"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Verification Trends Chart -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold">Verification Trends</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="verificationTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold">Verification by Device</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4 pb-2">
                                    <canvas id="deviceTypeChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <?php foreach ($device_stats as $index => $data): ?>
                                    <span class="mr-2">
                                        <i class="fas fa-circle" style="color: <?php echo isset($device_colors[$data['device_type']]) ? $device_colors[$data['device_type']] : '#' . substr(md5($data['device_type']), 0, 6); ?>"></i> <?php echo htmlspecialchars($data['device_type']); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Certificate Issuance Chart and Top Verified Certificates -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold">Certificate Issuance (Last 6 Months)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-bar">
                                    <canvas id="certificateIssuanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold">Most Verified Certificates</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Certificate</th>
                                                <th>Verifications</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($most_verified) > 0): ?>
                                                <?php foreach ($most_verified as $certificate): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold"><?php echo htmlspecialchars($certificate['full_name']); ?></span>
                                                            <small class="text-muted"><?php echo htmlspecialchars($certificate['course_name']); ?></small>
                                                            <small class="text-primary"><?php echo $certificate['certificate_id']; ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo number_format($certificate['verification_count']); ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="text-center py-3">No verification data available for the selected period.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Geographic Distribution and Verification Success Rate -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold">Geographic Distribution</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Country</th>
                                                <th>Verifications</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_country_verifications = array_sum(array_column($country_stats, 'count'));
                                            if (count($country_stats) > 0 && $total_country_verifications > 0): 
                                            ?>
                                                <?php foreach ($country_stats as $country): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($country['country']); ?></td>
                                                    <td><?php echo number_format($country['count']); ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2"><?php echo round(($country['count'] / $total_country_verifications) * 100, 1); ?>%</div>
                                                            <div class="progress flex-grow-1" style="height: 5px;">
                                                                <div class="progress-bar" role="progressbar" style="width: <?php echo ($country['count'] / $total_country_verifications) * 100; ?>%"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3">No geographic data available for the selected period.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold">Verification Success Rate</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4 pb-2">
                                    <canvas id="successRateChart"></canvas>
                                </div>
                                
                                <?php
                                // Calculate success rate
                                $total_success = array_sum($trend_success);
                                $total_failed = array_sum($trend_failed);
                                $total_verifications = $total_success + $total_failed;
                                $success_rate = $total_verifications > 0 ? round(($total_success / $total_verifications) * 100, 1) : 0;
                                ?>
                                
                                <div class="mt-4 text-center">
                                    <h4 class="mb-0"><?php echo $success_rate; ?>%</h4>
                                    <div class="small text-muted">Success Rate</div>
                                    
                                    <div class="mt-3 d-flex justify-content-center">
                                        <div class="mx-3 text-center">
                                            <h5 class="mb-0 text-success"><?php echo number_format($total_success); ?></h5>
                                            <div class="small text-muted">Successful</div>
                                        </div>
                                        <div class="mx-3 text-center">
                                            <h5 class="mb-0 text-danger"><?php echo number_format($total_failed); ?></h5>
                                            <div class="small text-muted">Failed</div>
                                        </div>
                                    </div>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Verification Trend Chart
            var ctx = document.getElementById('verificationTrendChart');
            var verificationTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($trend_dates); ?>,
                    datasets: [{
                        label: 'Successful Verifications',
                        data: <?php echo json_encode($trend_success); ?>,
                        backgroundColor: 'rgba(28, 200, 138, 0.2)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Failed Verifications',
                        data: <?php echo json_encode($trend_failed); ?>,
                        backgroundColor: 'rgba(231, 74, 59, 0.2)',
                        borderColor: 'rgba(231, 74, 59, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(231, 74, 59, 1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Daily Verification Trend'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Device Type Chart
            var deviceCtx = document.getElementById('deviceTypeChart');
            var deviceTypeChart = new Chart(deviceCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($device_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($device_counts); ?>,
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc',
                            '#f6c23e'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Certificate Issuance Chart
            var issuanceCtx = document.getElementById('certificateIssuanceChart');
            var certificateIssuanceChart = new Chart(issuanceCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($issuance_months); ?>,
                    datasets: [{
                        label: 'Certificates Issued',
                        data: <?php echo json_encode($issuance_counts); ?>,
                        backgroundColor: 'rgba(78, 115, 223, 0.7)',
                        borderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Success Rate Chart
            var successRateCtx = document.getElementById('successRateChart');
            var successRateChart = new Chart(successRateCtx, {
                type: 'pie',
                data: {
                    labels: ['Successful', 'Failed'],
                    datasets: [{
                        data: [<?php echo $total_success; ?>, <?php echo $total_failed; ?>],
                        backgroundColor: [
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(231, 74, 59, 0.8)'
                        ],
                        borderColor: [
                            'rgba(28, 200, 138, 1)',
                            'rgba(231, 74, 59, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Print report
            document.getElementById('printReport').addEventListener('click', function() {
                window.print();
            });
            
            // Export as PDF (placeholder - would need a library like jsPDF in production)
            document.getElementById('exportPDF').addEventListener('click', function() {
                alert('PDF export functionality would be implemented with a library like jsPDF in production.');
            });
            
            // Export as CSV (placeholder)
            document.getElementById('exportCSV').addEventListener('click', function() {
                alert('CSV export functionality would be implemented in production.');
            });
        });
    </script>
</body>
</html>
