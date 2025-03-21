<?php
// Database connection settings
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'canada_immigration';

// Create connection with error handling
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    // Log the error
    error_log("Database Connection Error: " . $conn->connect_error);
    
    // Show friendly error for users
    $error = [
        'type' => 'Database Error',
        'message' => 'Failed to connect to database',
        'file' => __FILE__,
        'line' => __LINE__,
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Use error handler if available, otherwise show simple message
    if (function_exists('showFatalErrorPage')) {
        showFatalErrorPage($error);
    } else {
        echo "<h1>Database Connection Error</h1>";
        echo "<p>Sorry, we're experiencing technical difficulties. Please try again later.</p>";
    }
    
    exit;
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to safely handle database queries with error detection
function safeQuery($conn, $sql, $params = [], $types = "") {
    $result = false;
    
    try {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                throw new Exception("Failed to prepare SQL statement: " . $conn->error);
            }
            
            if (!empty($types) && !empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute SQL statement: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $conn->query($sql);
            
            if ($result === false) {
                throw new Exception("Failed to execute SQL query: " . $conn->error);
            }
        }
        
        return $result;
    } catch (Exception $e) {
        // Log the error
        error_log("Database Query Error: " . $e->getMessage() . " - SQL: " . $sql);
        
        // Re-throw as a custom exception with less sensitive information
        throw new Exception("Database operation failed. Please check error logs.");
    }
}
?> 