<?php
// Include error handler first
require_once __DIR__ . '/error_handler.php';

// Session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get the base URL for the website
 * 
 * @return string The base URL
 */
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove "/includes" from the path if the function is called from an include file
    $base_dir = str_replace('/includes', '', $script_name);
    $base_dir = str_replace('/visa_types', '', $base_dir);
    $base_dir = str_replace('/calculators', '', $base_dir);
    $base_dir = str_replace('/resources', '', $base_dir);
    $base_dir = str_replace('/booking', '', $base_dir);
    $base_dir = str_replace('/contact', '', $base_dir);
    $base_dir = str_replace('/user', '', $base_dir);
    $base_dir = str_replace('/admin', '', $base_dir);
    
    // If we're at the root, don't add a trailing slash
    if ($base_dir == '/') {
        $base_dir = '';
    }
    
    return $protocol . '://' . $host . $base_dir;
}

/**
 * Sanitize user input
 * 
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is an admin
 * 
 * @return bool True if user is an admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

/**
 * Check if user is a consultant
 * 
 * @return bool True if user is a consultant, false otherwise
 */
function isConsultant() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'consultant';
}

/**
 * Redirect to login page if user is not logged in
 * 
 * @param string $redirect_url The URL to redirect to after login
 */
function requireLogin($redirect_url = '') {
    if (!isLoggedIn()) {
        $redirect = empty($redirect_url) ? '' : '?redirect=' . urlencode($redirect_url);
        header("Location: " . getBaseURL() . "/user/login.php" . $redirect);
        exit();
    }
}

/**
 * Redirect to home page if user is not an admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: " . getBaseURL() . "/index.php");
        exit();
    }
}

/**
 * Generate a random token
 * 
 * @param int $length The length of the token
 * @return string The generated token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format a date in a user-friendly way
 * 
 * @param string $date The date to format
 * @param string $format The format to use
 * @return string The formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Get visa type name by ID
 * 
 * @param int $visa_id The visa ID
 * @param mysqli $conn The database connection
 * @return string The visa name
 */
function getVisaName($visa_id, $conn) {
    $stmt = $conn->prepare("SELECT visa_name FROM visa_types WHERE visa_id = ?");
    $stmt->bind_param("i", $visa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['visa_name'];
    }
    
    return 'Unknown Visa';
}

/**
 * Get user name by ID
 * 
 * @param int $user_id The user ID
 * @param mysqli $conn The database connection
 * @return string The user's full name
 */
function getUserName($user_id, $conn) {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['first_name'] . ' ' . $row['last_name'];
    }
    
    return 'Unknown User';
}

/**
 * Log an action in the system
 * 
 * @param string $action The action to log
 * @param int $user_id The user ID
 * @param mysqli $conn The database connection
 */
function logAction($action, $user_id, $conn) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
}

/**
 * Load SEO settings for a specific page
 * 
 * @param string $page_identifier The unique identifier for the page
 * @return array SEO settings for the page
 */
function loadSeoSettings($page_identifier) {
    global $conn;
    
    $settings = array();
    
    $stmt = $conn->prepare("SELECT * FROM seo_settings WHERE page_identifier = ?");
    $stmt->bind_param("s", $page_identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $settings = $result->fetch_assoc();
    }
    
    return $settings;
}

/**
 * Get a system setting from the database
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if setting is not found
 * @param bool $public_only Whether to only return public settings
 * @return mixed The setting value or default
 */
function getSystemSetting($key, $default = false, $public_only = false) {
    global $conn;
    
    try {
        $sql = "SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?";
        if ($public_only) {
            $sql .= " AND is_public = 1";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $value = $row['setting_value'];
            
            // Convert value based on setting_type
            switch ($row['setting_type']) {
                case 'boolean':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'number':
                    return is_numeric($value) ? (strpos($value, '.') !== false ? (float)$value : (int)$value) : $default;
                case 'json':
                    $decoded = json_decode($value, true);
                    return $decoded === null ? $default : $decoded;
                default:
                    return $value;
            }
        }
        
        return $default;
    } catch (Exception $e) {
        error_log("Error getting system setting '$key': " . $e->getMessage());
        return $default;
    }
}

/**
 * Log activity for audit trail
 * 
 * @param int $user_id User ID
 * @param string $action Action description
 * @param string $ip_address IP address (optional)
 */
function logActivity($user_id, $action, $ip_address = null) {
    global $conn;
    
    try {
        if ($ip_address === null) {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $action, $ip_address);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Check if user has a specific user type
 */
function isUserType($type) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $type;
} 