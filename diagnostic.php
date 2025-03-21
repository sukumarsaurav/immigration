<?php
// Basic error reporting to screen
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Diagnostic Tool</h1>";

echo "<h2>PHP Version</h2>";
echo "PHP Version: " . phpversion();

echo "<h2>File Check</h2>";
$required_files = [
    'includes/header.php',
    'includes/footer.php',
    'includes/testimonials_slider.php',
    'includes/latest_news.php',
    'includes/functions.php',
    'includes/db_connect.php',
    'includes/error_handler.php'
];

foreach ($required_files as $file) {
    echo "File: $file - " . (file_exists($file) ? "EXISTS" : "MISSING") . "<br>";
}

echo "<h2>Database Connection Test</h2>";
try {
    // Database connection settings
    $db_host = 'localhost';
    $db_user = 'root'; // Replace with your actual database user
    $db_pass = '';     // Replace with your actual database password
    $db_name = 'canada_immigration';
    
    $test_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($test_conn->connect_error) {
        echo "Database connection FAILED: " . $test_conn->connect_error;
    } else {
        echo "Database connection SUCCESSFUL!";
        $test_conn->close();
    }
} catch (Exception $e) {
    echo "Database connection test error: " . $e->getMessage();
}

echo "<h2>Testing include files individually</h2>";

echo "Testing header.php...<br>";
try {
    ob_start();
    include 'includes/header.php';
    ob_end_clean();
    echo "header.php included successfully<br>";
} catch (Throwable $e) {
    echo "Error in header.php: " . $e->getMessage() . "<br>";
}

echo "Testing footer.php...<br>";
try {
    ob_start();
    include 'includes/footer.php';
    ob_end_clean();
    echo "footer.php included successfully<br>";
} catch (Throwable $e) {
    echo "Error in footer.php: " . $e->getMessage() . "<br>";
}

echo "<h2>Memory Usage</h2>";
echo "Current Memory Usage: " . (memory_get_usage(true) / 1024 / 1024) . " MB<br>";
echo "Peak Memory Usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB<br>";

echo "<h2>Environment Variables</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>"; 