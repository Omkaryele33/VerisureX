<?php
/**
 * Security Log
 * This page displays security-related events and logs
 */

// Start session
require_once __DIR__ . "/../includes/session.php";

// Include master initialization file
require_once 'master_init.php';

// Set page title
$pageTitle = 'Security Log';

// Include master initialization file
require_once 'master_init.php';

// Set page title
$pageTitle = 'Security Log';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Get log entries
$query = "SELECT * FROM security_logs ORDER BY timestamp DESC LIMIT :offset, :records_per_page";
$stmt = $db->prepare($query);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total logs count
$count_query = "SELECT COUNT(*) as total FROM security_logs";
$count_stmt = $db->query($count_query);
$total_logs = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_logs / $records_per_page);

// Create table to store logs if it doesn't exist
function ensureLogTableExists($db) {
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE 'security_logs'");
        if ($tableCheck->rowCount() === 0) {
            $createTable = "CREATE TABLE security_logs (
                id INT(11) NOT NULL AUTO_INCREMENT,
                event_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                user_id INT(11) NULL,
                username VARCHAR(50) NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_timestamp (timestamp),
                KEY idx_event_type (event_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $db->exec($createTable);
            return true;
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Ensure the log table exists
ensureLogTableExists($db);

// Page title
$page_title = "Security Logs";?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Logs - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php';?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php';?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Security Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshBtn">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($logs)):;?>
                <div class="alert alert-info">No security logs found.</div>
                <?php else:;?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Type</th>
                                <th>Description</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log):;?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['id']);?></td>
                                <td>
                                    <?php 
                                    $badgeClass = 'bg-secondary';
                                    if (strpos($log['event_type'], 'login') !== false) {
                                        $badgeClass = strpos($log['event_type'], 'failed') !== false ? 'bg-danger' : 'bg-success';
                                    } elseif (strpos($log['event_type'], 'create') !== false) {
                                        $badgeClass = 'bg-primary';
                                    } elseif (strpos($log['event_type'], 'update') !== false) {
                                        $badgeClass = 'bg-warning text-dark';
                                    } elseif (strpos($log['event_type'], 'delete') !== false) {
                                        $badgeClass = 'bg-danger';
                                    }
;?>
                                    <span class="badge <?php echo $badgeClass;?>">
                                        <?php echo htmlspecialchars($log['event_type']);?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['description']);?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'N/A');?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']);?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp']));?></td>
                            </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1):;?>
                <nav aria-label="Security logs pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : '';?>">
                            <a class="page-link" href="?page=<?php echo $page - 1;?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++):;?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : '';?>">
                            <a class="page-link" href="?page=<?php echo $i;?>"><?php echo $i;?></a>
                        </li>
                        <?php endfor;?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : '';?>">
                            <a class="page-link" href="?page=<?php echo $page + 1;?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif;?>
                <?php endif;?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            window.location.reload();
        });
        
        // Export button
        document.getElementById('exportBtn').addEventListener('click', function() {
            window.location.href = 'export_logs.php?type=security';
        });
    </script>
</body>
</html>
