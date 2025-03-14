<?php
/**
 * Common utility functions for the certificate validation system
 */

/**
 * Generate a UUID v4
 * @return string UUID v4 string
 */
function generateUUID() {
    // Generate 16 bytes (128 bits) of random data
    $data = random_bytes(16);
    
    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    // Output the 36 character UUID
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Sanitize input data
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate date format (YYYY-MM-DD)
 * @param string $date Date string to validate
 * @return bool True if valid, false otherwise
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Generate QR code for a certificate ID
 * @param string $certificateId The certificate ID
 * @return string Path to the generated QR code image
 */
function generateQRCode($certificateId) {
    // Include the phpqrcode library
    require_once dirname(__DIR__) . '/vendor/phpqrcode/qrlib.php';
    
    // Create the QR code directory if it doesn't exist
    if (!file_exists(QR_DIR)) {
        mkdir(QR_DIR, 0755, true);
    }
    
    // Set the QR code file path
    $qrCodePath = 'qrcodes/' . $certificateId . '.png';
    $qrCodeFullPath = UPLOAD_DIR . $qrCodePath;
    
    // Generate the QR code
    $verificationUrl = VERIFY_URL . '?id=' . urlencode($certificateId);
    
    // Check if file already exists and remove it to avoid permission issues
    if (file_exists($qrCodeFullPath)) {
        @unlink($qrCodeFullPath);
    }
    
    // Create QR code with error handling
    try {
        QRcode::png($verificationUrl, $qrCodeFullPath);
        
        // Verify that the file was created
        if (!file_exists($qrCodeFullPath)) {
            error_log("QR code generation failed: File not created at {$qrCodeFullPath}");
            return false;
        }
        
        // Set appropriate permissions
        @chmod($qrCodeFullPath, 0644);
        
        return $qrCodePath;
    } catch (Exception $e) {
        error_log("QR code generation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a file is a valid image
 * @param array $file The $_FILES array element
 * @return bool True if valid, false otherwise
 */
function isValidImage($file) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Check file extension
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    if (!in_array($extension, explode(',', ALLOWED_EXTENSIONS))) {
        return false;
    }
    
    // Check if file is actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return false;
    }
    
    return true;
}

/**
 * Upload an image file
 * @param array $file The $_FILES array element
 * @param string $certificateId The certificate ID to use in the filename
 * @return string|false Path to the uploaded file or false on failure
 */
function uploadImage($file, $certificateId) {
    // Check if file exists and there are no upload errors
    if (!isset($file) || !is_array($file) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate the image
    if (!isValidImage($file)) {
        return false;
    }
    
    // Create the upload directory if it doesn't exist
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            return false;
        }
    }
    
    // Get file extension
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    // Set the target file path
    $targetPath = 'photos/' . $certificateId . '.' . $extension;
    $targetFullPath = UPLOAD_DIR . $targetPath;
    
    // Create photos directory if it doesn't exist
    if (!file_exists(dirname($targetFullPath))) {
        if (!mkdir(dirname($targetFullPath), 0755, true)) {
            return false;
        }
    }
    
    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFullPath)) {
        // Try to optimize the image, but continue even if it fails
        try {
            optimizeImage($targetFullPath, $extension);
        } catch (Exception $e) {
            // Just log the error, don't fail the upload
            error_log("Failed to optimize image: " . $e->getMessage());
        }
        return $targetPath;
    }
    
    return false;
}

/**
 * Optimize an image for web
 * @param string $filePath Path to the image file
 * @param string $extension File extension
 * @return bool True on success, false on failure
 */
function optimizeImage($filePath, $extension) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        // If GD is not available, just return true (don't optimize)
        return true;
    }

    // Get the image dimensions
    list($width, $height) = getimagesize($filePath);
    
    // Maximum dimensions
    $maxWidth = 1200;
    $maxHeight = 1200;
    
    // Check if resizing is needed
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true; // No need to resize
    }
    
    // Calculate new dimensions
    if ($width > $height) {
        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = round($height * $ratio);
    } else {
        $ratio = $maxHeight / $height;
        $newWidth = round($width * $ratio);
        $newHeight = $maxHeight;
    }
    
    // Create a new image
    $sourceImage = null;
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Load the source image based on file extension
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $sourceImage = imagecreatefromjpeg($filePath);
            break;
        case 'png':
            $sourceImage = imagecreatefrompng($filePath);
            // Preserve transparency
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        default:
            return false;
    }
    
    // Resize the image
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save the optimized image
    $result = false;
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $result = imagejpeg($newImage, $filePath, 85); // 85% quality
            break;
        case 'png':
            $result = imagepng($newImage, $filePath, 8); // Compression level 8
            break;
    }
    
    // Free up memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $result;
}

/**
 * Check if a request is rate limited
 * @param string $ip IP address of the requester
 * @return bool True if rate limited, false otherwise
 */
function isRateLimited($ip) {
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.json';
    
    // Check if cache file exists
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        
        // Clean up old requests
        $now = time();
        $requests = array_filter($data['requests'], function($timestamp) use ($now) {
            return $now - $timestamp < RATE_LIMIT_WINDOW;
        });
        
        // Check if rate limit is exceeded
        if (count($requests) >= RATE_LIMIT) {
            return true;
        }
        
        // Update requests
        $data['requests'] = $requests;
    } else {
        // Create new cache file
        $data = ['requests' => []];
    }
    
    // Add current request
    $data['requests'][] = time();
    
    // Save cache file
    file_put_contents($cacheFile, json_encode($data));
    
    return false;
}

/**
 * Log verification attempt
 * @param string $certificateId Certificate ID
 * @param string $ip IP address of the requester
 * @param string $userAgent User agent of the requester
 * @return bool True on success, false on failure
 */
function logVerification($certificateId, $ip, $userAgent) {
    global $db;
    
    try {
        $query = "INSERT INTO verification_logs (certificate_id, ip_address, user_agent) VALUES (:certificate_id, :ip_address, :user_agent)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':certificate_id', $certificateId);
        $stmt->bindParam(':ip_address', $ip);
        $stmt->bindParam(':user_agent', $userAgent);
        return $stmt->execute();
    } catch (PDOException $e) {
        // Log the error
        error_log('Error logging verification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Redirect to a URL
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Display a flash message (alias for setMessage in session.php)
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    // Use the setMessage function from session.php
    setMessage($type, $message);
}

/**
 * Get and clear flash message (alias for getMessage in session.php)
 * @return array|null Flash message or null if no message
 */
function getFlashMessage() {
    // Use the getMessage function from session.php
    return getMessage();
}
?>
