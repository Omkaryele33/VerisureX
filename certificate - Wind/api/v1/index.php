<?php
/**
 * CertifyPro API - v1
 * 
 * This file handles API requests and enforces authentication and rate limiting
 */

// Include config and database connection
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/security.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Allow restricted cross-origin requests instead of wildcard
$allowedOrigins = [
    'https://trusted-client-domain.com',
    'https://another-trusted-domain.com'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    // Default to same origin if not in allowed list
    header("Access-Control-Allow-Origin: " . parse_url(BASE_URL, PHP_URL_SCHEME) . '://' . parse_url(BASE_URL, PHP_URL_HOST));
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Signature, X-Timestamp, X-Nonce");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and URI
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', trim($request_uri, '/'));

// Find the API path segment to determine the endpoint
$api_segment_index = array_search('api', $uri_segments);
$endpoint_index = $api_segment_index + 2; // api/v1/[endpoint]

// Get API key from Authorization header
$headers = getallheaders();
$api_key = null;

if (isset($headers['Authorization'])) {
    $auth_header = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $api_key = $matches[1];
    }
}

// Check if API key is provided
if (!$api_key) {
    send_response(401, false, 'API key is required. Please provide a valid API key in the Authorization header.');
    exit;
}

// Implement HMAC verification for added security
$signature = isset($headers['X-Signature']) ? $headers['X-Signature'] : null;
$timestamp = isset($headers['X-Timestamp']) ? $headers['X-Timestamp'] : null;
$nonce = isset($headers['X-Nonce']) ? $headers['X-Nonce'] : null;

// Raw request body for signature verification
$request_body = file_get_contents('php://input');

// Verify API key and get permissions
$query = "SELECT * FROM api_keys WHERE api_key = :api_key AND is_active = 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':api_key', $api_key);
$stmt->execute();
$api_key_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if API key exists and is active
if (!$api_key_data) {
    // Log invalid API key attempt
    $client_ip = $_SERVER['REMOTE_ADDR'];
    error_log("Invalid API key attempt from IP: {$client_ip}, API Key: {$api_key}");
    
    send_response(401, false, 'Invalid or inactive API key.');
    exit;
}

// Enhanced security: Check if HMAC verification is enabled for this API key
if ($api_key_data['require_hmac'] && (!$signature || !$timestamp || !$nonce)) {
    send_response(401, false, 'This API key requires HMAC signature verification. Please provide X-Signature, X-Timestamp, and X-Nonce headers.');
    exit;
}

// Verify HMAC signature if provided and required
if ($api_key_data['require_hmac'] && $signature && $timestamp && $nonce) {
    // Check if timestamp is recent (within 5 minutes)
    $now = time();
    if (abs($now - intval($timestamp)) > 300) {
        send_response(401, false, 'Timestamp is too old or in the future. Please ensure your system clock is accurate.');
        exit;
    }
    
    // Check for nonce replay
    $query = "SELECT * FROM api_nonces WHERE api_key_id = :api_key_id AND nonce = :nonce";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':api_key_id', $api_key_data['id']);
    $stmt->bindParam(':nonce', $nonce);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        send_response(401, false, 'Nonce has already been used. Please use a unique nonce for each request.');
        exit;
    }
    
    // Verify signature
    $data_to_sign = $api_key . $timestamp . $nonce . $request_method . $request_uri . $request_body;
    $expected_signature = hash_hmac('sha256', $data_to_sign, $api_key_data['api_secret']);
    
    if (!hash_equals($expected_signature, $signature)) {
        send_response(401, false, 'Invalid signature. Please ensure you are signing the request correctly.');
        exit;
    }
    
    // Store nonce to prevent replay attacks
    $query = "INSERT INTO api_nonces (api_key_id, nonce, created_at) VALUES (:api_key_id, :nonce, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':api_key_id', $api_key_data['id']);
    $stmt->bindParam(':nonce', $nonce);
    $stmt->execute();
}

// Check rate limit
$api_key_id = $api_key_data['id'];
$today = date('Y-m-d');

$query = "SELECT COUNT(*) as request_count FROM api_requests 
          WHERE api_key_id = :api_key_id 
          AND DATE(created_at) = :today";
$stmt = $db->prepare($query);
$stmt->bindParam(':api_key_id', $api_key_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$request_count = $stmt->fetch(PDO::FETCH_ASSOC)['request_count'];

if ($request_count >= $api_key_data['rate_limit']) {
    send_response(429, false, 'Rate limit exceeded. Please try again tomorrow.');
    exit;
}

// Log API request
$client_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device_type = get_device_type($user_agent);
$request_data = json_encode([
    'method' => $request_method,
    'uri' => $request_uri,
    'params' => $_GET,
    'body' => $request_body
]);

// Determine country from IP (simplified example)
$country = 'Unknown';
if (function_exists('geoip_country_code_by_name')) {
    $country_code = geoip_country_code_by_name($client_ip);
    if ($country_code) {
        $country = $country_code;
    }
}

$query = "INSERT INTO api_requests (api_key_id, endpoint, request_data, ip_address, 
          user_agent, device_type, country, created_at) 
          VALUES (:api_key_id, :endpoint, :request_data, :ip_address, 
          :user_agent, :device_type, :country, NOW())";
$stmt = $db->prepare($query);
$endpoint = isset($uri_segments[$endpoint_index]) ? $uri_segments[$endpoint_index] : '';
$stmt->bindParam(':api_key_id', $api_key_id);
$stmt->bindParam(':endpoint', $endpoint);
$stmt->bindParam(':request_data', $request_data);
$stmt->bindParam(':ip_address', $client_ip);
$stmt->bindParam(':user_agent', $user_agent);
$stmt->bindParam(':device_type', $device_type);
$stmt->bindParam(':country', $country);
$stmt->execute();

// Get the inserted request ID for response tracking
$request_id = $db->lastInsertId();

// Process API endpoints
if (isset($uri_segments[$endpoint_index])) {
    $endpoint = $uri_segments[$endpoint_index];
    
    // Certificate verification endpoint
    if ($endpoint === 'verify' && isset($uri_segments[$endpoint_index + 1])) {
        $certificate_id = $uri_segments[$endpoint_index + 1];
        handle_certificate_verification($db, $certificate_id, $request_id);
    }
    // List certificates endpoint (requires 'read' permission)
    elseif ($endpoint === 'certificates' && $request_method === 'GET') {
        $permissions = json_decode($api_key_data['permissions'], true);
        if (!in_array('read', $permissions)) {
            send_response(403, false, 'Permission denied. Your API key does not have \'read\' permission.');
            exit;
        }
        
        handle_certificates_list($db, $request_id);
    }
    // Create certificate endpoint (requires 'create' permission)
    elseif ($endpoint === 'certificates' && $request_method === 'POST') {
        $permissions = json_decode($api_key_data['permissions'], true);
        if (!in_array('create', $permissions)) {
            send_response(403, false, 'Permission denied. Your API key does not have \'create\' permission.');
            exit;
        }
        
        handle_certificate_creation($db, $request_id);
    }
    // Certificate status update endpoint (requires 'update' permission)
    elseif ($endpoint === 'certificates' && isset($uri_segments[$endpoint_index + 1]) && $request_method === 'PUT') {
        $permissions = json_decode($api_key_data['permissions'], true);
        if (!in_array('update', $permissions)) {
            send_response(403, false, 'Permission denied. Your API key does not have \'update\' permission.');
            exit;
        }
        
        $certificate_id = $uri_segments[$endpoint_index + 1];
        handle_certificate_update($db, $certificate_id, $request_id);
    }
    // API information endpoint
    elseif ($endpoint === 'info') {
        handle_api_info($api_key_data);
    }
    else {
        send_response(404, false, 'Endpoint not found.');
    }
} else {
    // Default API root response with available endpoints
    $response = [
        'status' => true,
        'message' => 'CertifyPro API v1',
        'endpoints' => [
            'GET /api/v1/verify/{certificate_id}' => 'Verify a certificate',
            'GET /api/v1/certificates' => 'List certificates (requires read permission)',
            'POST /api/v1/certificates' => 'Create a new certificate (requires create permission)',
            'PUT /api/v1/certificates/{certificate_id}' => 'Update certificate status (requires update permission)',
            'GET /api/v1/info' => 'Get API key information'
        ]
    ];
    echo json_encode($response);
}

/**
 * Handle certificate verification
 */
function handle_certificate_verification($db, $certificate_id, $request_id) {
    // Query the certificate
    $query = "SELECT * FROM certificates WHERE certificate_id = :certificate_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':certificate_id', $certificate_id);
    $stmt->execute();
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log verification
    $verification_successful = ($certificate && $certificate['is_active'] == 1);
    $query = "INSERT INTO verification_logs (certificate_id, verification_time, ip_address, successful) 
              VALUES (:certificate_id, NOW(), :ip_address, :successful)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':certificate_id', $certificate_id);
    $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':successful', $verification_successful, PDO::PARAM_BOOL);
    $stmt->execute();
    
    // Update API request status
    $query = "UPDATE api_requests SET response_code = :response_code WHERE id = :id";
    $stmt = $db->prepare($query);
    $response_code = $verification_successful ? 200 : 404;
    $stmt->bindParam(':response_code', $response_code);
    $stmt->bindParam(':id', $request_id);
    $stmt->execute();
    
    if ($certificate && $certificate['is_active'] == 1) {
        // Certificate is valid
        send_response(200, true, 'Certificate is valid', [
            'certificate' => [
                'id' => $certificate['certificate_id'],
                'full_name' => $certificate['full_name'],
                'course_name' => $certificate['course_name'],
                'issue_date' => $certificate['issue_date'],
                'expiry_date' => $certificate['expiry_date'],
                'status' => 'Active'
            ]
        ]);
    } elseif ($certificate && $certificate['is_active'] == 0) {
        // Certificate is revoked
        send_response(200, false, 'Certificate has been revoked', [
            'certificate' => [
                'id' => $certificate['certificate_id'],
                'status' => 'Revoked'
            ]
        ]);
    } else {
        // Certificate not found
        send_response(404, false, 'Certificate not found');
    }
}

/**
 * Handle certificates listing
 */
function handle_certificates_list($db, $request_id) {
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Validate parameters
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 10;
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build query
    $query = "SELECT * FROM certificates WHERE 1=1";
    $count_query = "SELECT COUNT(*) AS total FROM certificates WHERE 1=1";
    
    $params = [];
    
    // Add status filter if provided
    if ($status !== null) {
        if ($status === 'active') {
            $query .= " AND is_active = 1";
            $count_query .= " AND is_active = 1";
        } elseif ($status === 'revoked') {
            $query .= " AND is_active = 0";
            $count_query .= " AND is_active = 0";
        }
    }
    
    // Add order and limit
    $query .= " ORDER BY created_at DESC LIMIT :offset, :limit";
    
    // Get total count
    $stmt = $db->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get certificates
    $stmt = $db->prepare($query);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    
    // Bind other params if they exist
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format certificates for response
    $formatted_certificates = [];
    foreach ($certificates as $cert) {
        $formatted_certificates[] = [
            'id' => $cert['certificate_id'],
            'full_name' => $cert['full_name'],
            'course_name' => $cert['course_name'],
            'issue_date' => $cert['issue_date'],
            'expiry_date' => $cert['expiry_date'],
            'status' => $cert['is_active'] ? 'Active' : 'Revoked',
            'created_at' => $cert['created_at']
        ];
    }
    
    // Update API request status
    $query = "UPDATE api_requests SET response_code = 200 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $request_id);
    $stmt->execute();
    
    // Send response
    send_response(200, true, 'Certificates retrieved successfully', [
        'certificates' => $formatted_certificates,
        'pagination' => [
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Handle certificate creation
 */
function handle_certificate_creation($db, $request_id) {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['full_name', 'course_name', 'issue_date'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            send_response(400, false, "Missing required field: {$field}");
            exit;
        }
    }
    
    // Extract and sanitize data
    $full_name = trim($data['full_name']);
    $course_name = trim($data['course_name']);
    $issue_date = trim($data['issue_date']);
    $expiry_date = isset($data['expiry_date']) ? trim($data['expiry_date']) : null;
    $additional_fields = isset($data['additional_fields']) ? json_encode($data['additional_fields']) : null;
    
    // Generate certificate ID
    $certificate_id = generate_certificate_id();
    
    // Insert into database
    try {
        $query = "INSERT INTO certificates 
                 (certificate_id, full_name, course_name, issue_date, expiry_date, 
                  additional_fields, is_active, created_at) 
                 VALUES 
                 (:certificate_id, :full_name, :course_name, :issue_date, :expiry_date, 
                  :additional_fields, 1, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':certificate_id', $certificate_id);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':course_name', $course_name);
        $stmt->bindParam(':issue_date', $issue_date);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->bindParam(':additional_fields', $additional_fields);
        
        if ($stmt->execute()) {
            // Update API request status
            $query = "UPDATE api_requests SET response_code = 201 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            
            send_response(201, true, 'Certificate created successfully', [
                'certificate_id' => $certificate_id,
                'verification_url' => BASE_URL . '/verify/?id=' . $certificate_id
            ]);
        } else {
            throw new Exception("Database error occurred");
        }
    } catch (Exception $e) {
        send_response(500, false, 'Failed to create certificate: ' . $e->getMessage());
    }
}

/**
 * Handle certificate update (status change)
 */
function handle_certificate_update($db, $certificate_id, $request_id) {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if certificate exists
    $query = "SELECT * FROM certificates WHERE certificate_id = :certificate_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':certificate_id', $certificate_id);
    $stmt->execute();
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$certificate) {
        send_response(404, false, 'Certificate not found');
        exit;
    }
    
    // Check if status is provided
    if (!isset($data['status'])) {
        send_response(400, false, 'Status field is required');
        exit;
    }
    
    // Convert status to is_active
    $status = strtolower($data['status']);
    if ($status === 'active') {
        $is_active = 1;
    } elseif ($status === 'revoked') {
        $is_active = 0;
    } else {
        send_response(400, false, 'Invalid status value. Use "active" or "revoked"');
        exit;
    }
    
    // Update certificate status
    try {
        $query = "UPDATE certificates SET is_active = :is_active, updated_at = NOW() WHERE certificate_id = :certificate_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':is_active', $is_active);
        $stmt->bindParam(':certificate_id', $certificate_id);
        
        if ($stmt->execute()) {
            // Update API request status
            $query = "UPDATE api_requests SET response_code = 200 WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $request_id);
            $stmt->execute();
            
            send_response(200, true, 'Certificate status updated successfully', [
                'certificate_id' => $certificate_id,
                'status' => $is_active ? 'Active' : 'Revoked'
            ]);
        } else {
            throw new Exception("Database error occurred");
        }
    } catch (Exception $e) {
        send_response(500, false, 'Failed to update certificate: ' . $e->getMessage());
    }
}

/**
 * Handle API info
 */
function handle_api_info($api_key_data) {
    $permissions = json_decode($api_key_data['permissions'], true) ?: [];
    
    $response = [
        'status' => true,
        'rate_limit' => [
            'limit' => (int)$api_key_data['rate_limit'],
            'remaining' => (int)$api_key_data['rate_limit'] - 1 // count current request
        ],
        'permissions' => $permissions
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Send JSON response
 */
function send_response($status_code, $success, $message, $data = null) {
    http_response_code($status_code);
    
    $response = [
        'status' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Generate certificate ID
 * Format: CP-[RANDOM]-[YEAR]
 */
function generate_certificate_id() {
    $prefix = 'CP';
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    $year = date('Y');
    
    return "{$prefix}-{$random}-{$year}";
}

/**
 * Detect device type from user agent
 */
function get_device_type($user_agent) {
    if (empty($user_agent)) {
        return 'Unknown';
    }
    
    if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($user_agent, 0, 4))) {
        return 'Mobile';
    }
    
    if (preg_match('/android|ipad|playbook|silk/i', $user_agent)) {
        return 'Tablet';
    }
    
    return 'Desktop';
}
