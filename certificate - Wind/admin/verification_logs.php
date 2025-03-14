<?php
/**
 * Admin Panel - Verification Logs
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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchClause = '';
$params = [];

if (!empty($search)) {
    $searchClause = "WHERE (certificate_id LIKE :search OR ip_address LIKE :search)";
    $params[':search'] = "%$search%";
}

// Get total logs count
$countQuery = "SELECT COUNT(*) FROM verification_logs $searchClause";
$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalLogs = $countStmt->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalLogs / $perPage);

// Get logs data
$query = "SELECT l.*, c.holder_name AS full_name 
          FROM verification_logs l
          LEFT JOIN certificates c ON l.certificate_id = c.certificate_id
          $searchClause
          ORDER BY l.verified_at DESC
          LIMIT :offset, :perPage";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Verification Logs";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Logs - Certificate Validation System</title>
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
                    <h1 class="h2">Verification Logs</h1>
                </div>
                
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                        <?php echo $_SESSION['flash_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="verification_logs.php" method="get" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search by certificate ID or IP address" value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="verification_logs.php" class="btn btn-secondary ms-2">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="text-muted">
                            Showing <?php echo $totalLogs > 0 ? $offset + 1 : 0; ?>-<?php echo min($offset + $perPage, $totalLogs); ?> of <?php echo $totalLogs; ?> logs
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Certificate ID</th>
                                <th>Student Name</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Status</th>
                                <th>Verified At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No verification logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['certificate_id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></td>
                                        <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>
                                        </td>
                                        <td>
                                            <?php if (isset($log['is_valid']) && $log['is_valid']): ?>
                                                <span class="badge bg-success">Valid</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Invalid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($log['verified_at']) ? date('Y-m-d H:i:s', strtotime($log['verified_at'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php if (!empty($log['full_name'])): ?>
                                                <a href="view_certificate.php?id=<?php
                                                    // Get certificate ID from certificate_id
                                                    $certQuery = "SELECT id FROM certificates WHERE certificate_id = :certificate_id";
                                                    $certStmt = $db->prepare($certQuery);
                                                    $certStmt->bindParam(':certificate_id', $log['certificate_id'] ?? '');
                                                    $certStmt->execute();
                                                    $cert = $certStmt->fetch(PDO::FETCH_ASSOC);
                                                    echo $cert && isset($cert['id']) ? $cert['id'] : '';
                                                ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Certificate not found</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page <= 1 ? '#' : "verification_logs.php?page=" . ($page - 1) . (!empty($search) ? "&search=" . urlencode($search) : ""); ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="verification_logs.php?page=<?php echo $i; ?><?php echo !empty($search) ? "&search=" . urlencode($search) : ""; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $page >= $totalPages ? '#' : "verification_logs.php?page=" . ($page + 1) . (!empty($search) ? "&search=" . urlencode($search) : ""); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
