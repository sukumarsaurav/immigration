<?php
/**
 * Database Configuration File
 * 
 * This file contains the database connection settings for the application.
 */

// Database credentials
$db_host = '193.203.184.121';
$db_name = 'u911550082_immigration'; 
$db_user = 'u911550082_immigration';      
$db_pass = 'Ro4=ey?^'; 

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    // Log the error
    $error_message = "Database connection failed: " . $conn->connect_error;
    error_log($error_message);
    
    // For development only - you may want to comment this out in production
    if (isset($debug_mode) && $debug_mode === true) {
        echo "<div class='alert alert-danger'>Database connection error. Please try again later.</div>";
        echo "<!-- Error details: " . $conn->connect_error . " -->";
    } else {
        echo "<div class='alert alert-danger'>Database connection error. Please try again later.</div>";
    }
}

// Set character set
$conn->set_charset("utf8mb4");
?> 