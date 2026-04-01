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
?>
