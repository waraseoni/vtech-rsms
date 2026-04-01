<?php
/**
 * Security Helper Functions
 * Prevents SQL Injection and XSS attacks
 */

// Sanitize input to prevent SQL injection
function sanitize($input) {
    global $db;
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// Escape string for SQL
function escape($str) {
    global $conn;
    if (!$conn) {
        require_once 'classes/DBConnection.php';
        $db = new DBConnection();
        $conn = $db->conn;
    }
    return $conn->real_escape_string($str);
}

// Validate integer input
function validate_int($value, $default = 0) {
    return isset($value) && is_numeric($value) ? intval($value) : $default;
}

// Validate float input
function validate_float($value, $default = 0.00) {
    return isset($value) && is_numeric($value) ? floatval($value) : $default;
}

// Prepared statement helper
function prepare_query($conn, $sql, $params, $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

// Hash password securely (for new PHP version)
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password securely
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Legacy MD5 check (for backward compatibility with old database)
// Can be removed after all passwords are updated to new hash
function verify_password_md5($password, $md5hash) {
    return md5($password) === $md5hash;
}

// CSRF Token generation
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token validation
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Secure file upload validation
function validate_upload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'], $max_size = 5242880) {
    // $max_size default is 5MB
    
    $result = ['valid' => false, 'error' => '', 'filename' => ''];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $result['error'] = 'No file uploaded';
        return $result;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];
        $result['error'] = $errors[$file['error']] ?? 'Unknown upload error';
        return $result;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $result['error'] = 'File size exceeds maximum allowed (' . round($max_size / 1048576, 1) . 'MB)';
        return $result;
    }
    
    // Get file info
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Define allowed MIME types
    $allowed_mimes = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'pdf' => ['application/pdf']
    ];
    
    // Build allowed MIME types list
    $allowed_mime_types = [];
    foreach ($allowed_types as $type) {
        if (isset($allowed_mimes[strtolower($type)])) {
            $allowed_mime_types = array_merge($allowed_mime_types, $allowed_mimes[strtolower($type)]);
        }
    }
    
    // Check MIME type
    if (!in_array($mime_type, $allowed_mime_types)) {
        $result['error'] = 'File type not allowed';
        return $result;
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        $result['error'] = 'File extension not allowed';
        return $result;
    }
    
    // Generate safe filename
    $new_filename = bin2hex(random_bytes(8)) . '.' . $ext;
    
    $result['valid'] = true;
    $result['filename'] = $new_filename;
    $result['original_name'] = $file['name'];
    $result['mime_type'] = $mime_type;
    $result['size'] = $file['size'];
    
    return $result;
}

// Secure image upload (specific to images)
function validate_image_upload($file, $max_size = 2097152) { // 2MB default
    return validate_upload($file, ['jpg', 'jpeg', 'png', 'gif'], $max_size);
}

// Resize and compress image for avatars
// $max_width, $max_height: max dimensions (keeps aspect ratio)
// $quality: JPEG quality (0-100, default 70)
function resize_image($source_path, $destination_path, $max_width = 300, $max_height = 300, $quality = 70) {
    $ext = strtolower(pathinfo($destination_path, PATHINFO_EXTENSION));
    
    // Get image info
    $image_info = getimagesize($source_path);
    if ($image_info === false) {
        error_log("resize_image: getimagesize failed for " . $source_path);
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime = $image_info['mime'];
    
    // Calculate new dimensions (maintain aspect ratio)
    $ratio = min($max_width / $width, $max_height / $height);
    if ($ratio >= 1) {
        // Image is already smaller than max dimensions
        $new_width = $width;
        $new_height = $height;
    } else {
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
    }
    
    // Create new image resource
    $new_image = imagecreatetruecolor($new_width, $new_height);
    if (!$new_image) {
        error_log("resize_image: imagecreatetruecolor failed");
        return false;
    }
    
    // Preserve transparency for PNG/GIF
    if ($ext === 'png' || $ext === 'gif') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
        imagefill($new_image, 0, 0, $transparent);
    }
    
    // Load source image based on type
    $source = null;
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($source_path);
            break;
        default:
            imagedestroy($new_image);
            error_log("resize_image: unsupported mime type " . $mime);
            return false;
    }
    
    if (!$source) {
        imagedestroy($new_image);
        error_log("resize_image: imagecreatefrom failed for " . $source_path);
        return false;
    }
    
    // Resize with resampling (smooth result)
    $resample_result = imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    if (!$resample_result) {
        error_log("resize_image: imagecopyresampled failed");
        imagedestroy($source);
        imagedestroy($new_image);
        return false;
    }
    
    // Ensure directory exists
    $dir = dirname($destination_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    // Save compressed image
    $success = false;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $success = imagejpeg($new_image, $destination_path, $quality);
            break;
        case 'png':
            // PNG quality is different (0-9, where 9 is smallest)
            $png_quality = round((100 - $quality) / 11.11);
            $success = imagepng($new_image, $destination_path, $png_quality);
            break;
        case 'gif':
            $success = imagegif($new_image, $destination_path);
            break;
    }
    
    // Cleanup
    imagedestroy($source);
    imagedestroy($new_image);
    
    if (!$success) {
        error_log("resize_image: failed to save image to " . $destination_path);
        return false;
    }
    
    error_log("resize_image: SUCCESS - saved to " . $destination_path);
    return true;
}
?>
