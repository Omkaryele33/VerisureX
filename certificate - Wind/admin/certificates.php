<?php
/**
 * Admin Panel - Certificates List
 */

// Include master initialization file
require_once 'master_init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize variables
$search = '';
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($currentPage - 1) * $perPage;

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitizeInput($_GET['search']);
}

// Get certificates count
$countQuery = "SELECT COUNT(*) as total FROM certificates";
$params = [];

if (!empty($search)) {
    $countQuery .= " WHERE certificate_id LIKE :search OR full_name LIKE :search OR certificate_number LIKE :search OR branch_name LIKE :search";
    $params[':search'] = "%$search%";
}

$stmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalCertificates = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCertificates / $perPage);

// Get certificates
$query = "SELECT c.id, c.certificate_id, c.certificate_number, c.full_name, c.holder_name, 
          c.branch_name, c.grade, c.course_name, c.issue_date, c.pass_date, c.expiry_date, 
          c.is_active, c.validation_status, a.username 
          FROM certificates c 
          LEFT JOIN admins a ON c.created_by = a.id";

if (!empty($search)) {
    $query .= " WHERE c.certificate_id LIKE :search OR c.full_name LIKE :search OR c.certificate_number LIKE :search OR c.branch_name LIKE :search";
}

$query .= " ORDER BY c.created_at DESC LIMIT :offset, :perPage";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = "Certificates";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates - Certificate Validation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .grade-badge {
            display: inline-block;
            background-color: #007bff;
            color: white;
            font-weight: bold;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
        }
        .badge-valid {
            background-color: #28a745;
        }
        .badge-invalid {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Certificates</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="create_certificate.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg"></i> New Certificate
                        </a>
                    </div>
                </div>
                
                <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by certificate ID, number, name, or branch" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                                    <?php if (!empty($search)): ?>
                                    <a href="certificates.php" class="btn btn-outline-secondary">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Cert ID/Number</th>
                                        <th>Full Name</th>
                                        <th>Branch</th>
                                        <th>Grade</th>
                                        <th>Issue Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($certificates) > 0): ?>
                                        <?php foreach ($certificates as $cert): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($cert['certificate_id'] ?? $cert['certificate_number'] ?? ''); ?>
                                                <?php if (!empty($cert['certificate_number'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($cert['certificate_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($cert['full_name'] ?? $cert['holder_name'] ?? ''); ?></td>
                                            <td><?php echo !empty($cert['branch_name']) ? htmlspecialchars($cert['branch_name']) : '-'; ?></td>
                                            <td>
                                                <?php if (!empty($cert['grade'])): ?>
                                                <span class="grade-badge"><?php echo htmlspecialchars($cert['grade']); ?></span>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo isset($cert['issue_date']) ? date('d M Y', strtotime($cert['issue_date'])) : '-'; ?></td>
                                            <td>
                                                <?php if (isset($cert['validation_status'])): ?>
                                                    <?php if ($cert['validation_status']): ?>
                                                        <span class="badge bg-success">Valid</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Invalid</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unknown</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $cert['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $cert['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $cert['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $cert['id']; ?>">Confirm Delete</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to delete the certificate for <strong><?php echo htmlspecialchars($cert['full_name']); ?></strong>?
                                                                <p class="text-danger mt-2">This action cannot be undone.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <a href="delete_certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-danger">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No certificates found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" tabindex="-1" aria-disabled="<?php echo $currentPage <= 1 ? 'true' : 'false'; ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
