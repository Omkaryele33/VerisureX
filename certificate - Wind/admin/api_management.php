<?php
/**
 * CertifyPro - Premium Certificate Validation System
 * API Management
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

// Handle API key generation
$generatedKey = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_key'])) {
    $description = sanitizeInput($_POST['description']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    $rate_limit = (int)$_POST['rate_limit'];
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $created_by = $_SESSION['user_id'];
    
    // Generate API key (secure random string)
    $api_key = bin2hex(random_bytes(32));
    
    // Insert API key into database
    $query = "INSERT INTO api_keys 
              (api_key, description, permissions, rate_limit, created_by, created_at, expires_at) 
              VALUES 
              (:api_key, :description, :permissions, :rate_limit, :created_by, NOW(), :expires_at)";
    
    $stmt = $db->prepare($query);
    $permissions_json = json_encode($permissions);
    
    $stmt->bindParam(':api_key', $api_key);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':permissions', $permissions_json);
    $stmt->bindParam(':rate_limit', $rate_limit);
    $stmt->bindParam(':created_by', $created_by);
    $stmt->bindParam(':expires_at', $expires_at);
    
    if ($stmt->execute()) {
        $generatedKey = $api_key;
        setFlashMessage('success', 'API key generated successfully.');
    } else {
        setFlashMessage('error', 'Failed to generate API key.');
    }
}

// Handle API key deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "DELETE FROM api_keys WHERE id = :id AND created_by = :created_by";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'API key deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete API key.');
    }
    
    redirect('api_management.php');
}

// Toggle API key status (active/inactive)
if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "UPDATE api_keys SET is_active = NOT is_active WHERE id = :id AND created_by = :created_by";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':created_by', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'API key status updated successfully.');
    } else {
        setFlashMessage('error', 'Failed to update API key status.');
    }
    
    redirect('api_management.php');
}

// Get API keys for the current user
$query = "SELECT * FROM api_keys WHERE created_by = :created_by ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':created_by', $_SESSION['user_id']);
$stmt->execute();
$api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get API request statistics
$query = "SELECT ak.id, ak.description, COUNT(ar.id) as request_count, 
          MAX(ar.created_at) as last_request_time
          FROM api_keys ak 
          LEFT JOIN api_requests ar ON ak.id = ar.api_key_id
          WHERE ak.created_by = :created_by
          GROUP BY ak.id
          ORDER BY request_count DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':created_by', $_SESSION['user_id']);
$stmt->execute();
$api_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format as associative array for easier access
$api_stats_map = [];
foreach ($api_stats as $stat) {
    $api_stats_map[$stat['id']] = [
        'request_count' => $stat['request_count'],
        'last_request_time' => $stat['last_request_time']
    ];
}

// Page title
$pageTitle = "API Management";
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
                                <li class="breadcrumb-item active" aria-current="page">API Management</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateApiKeyModal">
                            <i class="bi bi-key"></i> Generate New API Key
                        </button>
                    </div>
                </div>
                
                <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($generatedKey): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h5><i class="bi bi-check-circle me-2"></i> API Key Generated Successfully</h5>
                    <p class="mb-2">This is your API key. Please copy it now, you won't be able to see it again.</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="apiKeyDisplay" value="<?php echo $generatedKey; ?>" readonly>
                        <button class="btn btn-outline-primary" type="button" onclick="copyApiKey()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Your API Keys</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (count($api_keys) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Description</th>
                                                <th>Created</th>
                                                <th>Expires</th>
                                                <th>Rate Limit</th>
                                                <th>Usage</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($api_keys as $key): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($key['description']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($key['created_at'])); ?></td>
                                                <td>
                                                    <?php echo (!empty($key['expires_at'])) ? 
                                                          date('M j, Y', strtotime($key['expires_at'])) : 
                                                          '<span class="text-muted">Never</span>'; ?>
                                                </td>
                                                <td><?php echo $key['rate_limit']; ?> req/day</td>
                                                <td>
                                                    <?php 
                                                    $requests = isset($api_stats_map[$key['id']]) ? $api_stats_map[$key['id']]['request_count'] : 0;
                                                    $last_used = isset($api_stats_map[$key['id']]['last_request_time']) ? $api_stats_map[$key['id']]['last_request_time'] : null;
                                                    
                                                    echo $requests . ' requests';
                                                    if ($last_used) {
                                                        echo '<br><small class="text-muted">Last: ' . date('M j, Y', strtotime($last_used)) . '</small>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($key['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="api_management.php?action=toggle&id=<?php echo $key['id']; ?>" class="btn btn-outline-<?php echo $key['is_active'] ? 'warning' : 'success'; ?>" title="<?php echo $key['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="bi bi-<?php echo $key['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-primary" title="View Details" data-bs-toggle="modal" data-bs-target="#apiDetailsModal" data-id="<?php echo $key['id']; ?>" data-description="<?php echo htmlspecialchars($key['description']); ?>" data-permissions='<?php echo htmlspecialchars($key['permissions']); ?>'>
                                                            <i class="bi bi-info-circle"></i>
                                                        </button>
                                                        <a href="api_management.php?action=delete&id=<?php echo $key['id']; ?>" class="btn btn-outline-danger delete-confirm" title="Delete" data-confirm-message="Are you sure you want to delete this API key? This action cannot be undone.">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info m-4">
                                    <i class="bi bi-info-circle me-2"></i> You don't have any API keys yet. <a href="#" data-bs-toggle="modal" data-bs-target="#generateApiKeyModal" class="alert-link">Generate your first key</a>.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">API Documentation</h5>
                            </div>
                            <div class="card-body">
                                <p>Use our RESTful API to integrate your systems with CertifyPro. The API provides endpoints for:</p>
                                <ul class="mb-3">
                                    <li>Verifying certificates</li>
                                    <li>Creating new certificates</li>
                                    <li>Retrieving certificate information</li>
                                    <li>Managing certificate status</li>
                                </ul>
                                <div class="d-grid">
                                    <a href="api_documentation.php" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-text me-2"></i> View Full Documentation
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">API Usage Tips</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="fw-bold"><i class="bi bi-shield-lock me-2"></i> Security</h6>
                                    <p class="small text-muted">Always include your API key in the authorization header. Never expose your API key in client-side code.</p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="fw-bold"><i class="bi bi-speedometer me-2"></i> Rate Limiting</h6>
                                    <p class="small text-muted">Each API key has a daily request limit. Monitor your usage to avoid disruptions.</p>
                                </div>
                                <div>
                                    <h6 class="fw-bold"><i class="bi bi-code-slash me-2"></i> Error Handling</h6>
                                    <p class="small text-muted">Our API returns standard HTTP status codes and detailed error messages to help you debug issues.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">API Code Examples</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="codeExampleTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="php-tab" data-bs-toggle="tab" data-bs-target="#php" type="button" role="tab" aria-controls="php" aria-selected="true">PHP</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="javascript-tab" data-bs-toggle="tab" data-bs-target="#javascript" type="button" role="tab" aria-controls="javascript" aria-selected="false">JavaScript</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="python-tab" data-bs-toggle="tab" data-bs-target="#python" type="button" role="tab" aria-controls="python" aria-selected="false">Python</button>
                                    </li>
                                </ul>
                                <div class="tab-content p-3" id="codeExampleTabsContent">
                                    <div class="tab-pane fade show active" id="php" role="tabpanel" aria-labelledby="php-tab">
                                        <pre class="bg-light p-3 rounded"><code>// Verify a certificate using PHP
$apiKey = 'YOUR_API_KEY';
$certificateId = 'CERTIFICATE_ID';
$apiUrl = '<?php echo BASE_URL; ?>/api/v1/verify/' . $certificateId;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);
if ($statusCode == 200 && $result['valid']) {
    echo "Certificate is valid!";
} else {
    echo "Certificate validation failed: " . $result['message'];
}</code></pre>
                                    </div>
                                    <div class="tab-pane fade" id="javascript" role="tabpanel" aria-labelledby="javascript-tab">
                                        <pre class="bg-light p-3 rounded"><code>// Verify a certificate using JavaScript
const apiKey = 'YOUR_API_KEY';
const certificateId = 'CERTIFICATE_ID';
const apiUrl = '<?php echo BASE_URL; ?>/api/v1/verify/' + certificateId;

fetch(apiUrl, {
    method: 'GET',
    headers: {
        'Authorization': 'Bearer ' + apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.valid) {
        console.log('Certificate is valid!');
        console.log('Details:', data.certificate);
    } else {
        console.error('Certificate validation failed:', data.message);
    }
})
.catch(error => {
    console.error('Error:', error);
});</code></pre>
                                    </div>
                                    <div class="tab-pane fade" id="python" role="tabpanel" aria-labelledby="python-tab">
                                        <pre class="bg-light p-3 rounded"><code>import requests

# Verify a certificate using Python
api_key = 'YOUR_API_KEY'
certificate_id = 'CERTIFICATE_ID'
api_url = '<?php echo BASE_URL; ?>/api/v1/verify/' + certificate_id

headers = {
    'Authorization': 'Bearer ' + api_key,
    'Content-Type': 'application/json'
}

response = requests.get(api_url, headers=headers)
result = response.json()

if response.status_code == 200 and result['valid']:
    print('Certificate is valid!')
    print('Details:', result['certificate'])
else:
    print('Certificate validation failed:', result['message'])</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Generate API Key Modal -->
    <div class="modal fade" id="generateApiKeyModal" tabindex="-1" aria-labelledby="generateApiKeyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="api_management.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="generateApiKeyModalLabel">Generate New API Key</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="e.g., Integration with Company X" required>
                            <div class="form-text">Provide a meaningful description to help you identify this API key.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_verify" name="permissions[]" value="verify" checked>
                                <label class="form-check-label" for="perm_verify">
                                    Verify certificates
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_read" name="permissions[]" value="read">
                                <label class="form-check-label" for="perm_read">
                                    Read certificate data
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_create" name="permissions[]" value="create">
                                <label class="form-check-label" for="perm_create">
                                    Create certificates
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="perm_update" name="permissions[]" value="update">
                                <label class="form-check-label" for="perm_update">
                                    Update certificates
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="rate_limit" class="form-label">Rate Limit (requests per day)</label>
                            <input type="number" class="form-control" id="rate_limit" name="rate_limit" min="10" max="10000" value="100" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="expires_at" class="form-label">Expiration Date (optional)</label>
                            <input type="date" class="form-control" id="expires_at" name="expires_at">
                            <div class="form-text">Leave blank for keys that never expire (not recommended for production).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="generate_key" class="btn btn-primary">Generate Key</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- API Details Modal -->
    <div class="modal fade" id="apiDetailsModal" tabindex="-1" aria-labelledby="apiDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="apiDetailsModalLabel">API Key Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Description</h6>
                        <p id="detailDescription"></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Permissions</h6>
                        <ul id="detailPermissions" class="list-group">
                            <!-- Permissions will be loaded here -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
    <script>
        // Copy API key to clipboard
        function copyApiKey() {
            const apiKeyInput = document.getElementById('apiKeyDisplay');
            apiKeyInput.select();
            document.execCommand('copy');
            
            // Show toast or change button text temporarily
            const copyButton = apiKeyInput.nextElementSibling;
            const originalText = copyButton.innerHTML;
            copyButton.innerHTML = '<i class="bi bi-check"></i> Copied!';
            
            setTimeout(function() {
                copyButton.innerHTML = originalText;
            }, 2000);
        }
        
        // API details modal
        document.addEventListener('DOMContentLoaded', function() {
            const apiDetailsModal = document.getElementById('apiDetailsModal');
            
            apiDetailsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const description = button.getAttribute('data-description');
                const permissions = JSON.parse(button.getAttribute('data-permissions') || '[]');
                
                document.getElementById('detailDescription').textContent = description;
                
                // Display permissions
                const permissionsList = document.getElementById('detailPermissions');
                permissionsList.innerHTML = '';
                
                const permissionLabels = {
                    'verify': 'Verify certificates',
                    'read': 'Read certificate data',
                    'create': 'Create certificates',
                    'update': 'Update certificates'
                };
                
                if (permissions.length === 0) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.textContent = 'No specific permissions (verify only)';
                    permissionsList.appendChild(li);
                } else {
                    permissions.forEach(function(permission) {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';
                        li.innerHTML = '<i class="bi bi-check-circle text-success me-2"></i> ' + (permissionLabels[permission] || permission);
                        permissionsList.appendChild(li);
                    });
                }
            });
            
            // Confirm delete
            document.querySelectorAll('.delete-confirm').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm-message') || 'Are you sure you want to delete this item?';
                    if (!confirm(message)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
