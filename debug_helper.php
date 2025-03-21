<?php
// Debug helper file - include at the top of index.php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture any errors
ob_start();

// Function to log errors to a file
function log_error($message, $file = null, $line = null) {
    $log_file = __DIR__ . '/error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    
    if ($file && $line) {
        $log_message .= " in $file on line $line";
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Custom error handler
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    log_error("Error [$errno] $errstr", $errfile, $errline);
    
    // For development only - display error information
    echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px;'>";
    echo "<h3>PHP Error Detected</h3>";
    echo "<p><strong>Error:</strong> [$errno] $errstr</p>";
    echo "<p><strong>File:</strong> $errfile</p>";
    echo "<p><strong>Line:</strong> $errline</p>";
    echo "</div>";
    
    return true; // Don't execute PHP's internal error handler
}

// Set the custom error handler
set_error_handler("custom_error_handler");

// Custom exception handler
function custom_exception_handler($exception) {
    log_error("Uncaught Exception: " . $exception->getMessage(), $exception->getFile(), $exception->getLine());
    
    // For development only - display exception information
    echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px;'>";
    echo "<h3>Uncaught Exception</h3>";
    echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
    echo "<p><strong>Trace:</strong> <pre>" . $exception->getTraceAsString() . "</pre></p>";
    echo "</div>";
}

// Set the custom exception handler
set_exception_handler("custom_exception_handler");

// Register a shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_error("Fatal Error: " . $error['message'], $error['file'], $error['line']);
        
        // For development only - display fatal error information
        echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px;'>";
        echo "<h3>Fatal Error</h3>";
        echo "<p><strong>Message:</strong> " . $error['message'] . "</p>";
        echo "<p><strong>File:</strong> " . $error['file'] . "</p>";
        echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
        echo "</div>";
    }
    
    // Flush the output buffer
    ob_end_flush();
});
?> 