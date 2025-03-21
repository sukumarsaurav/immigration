<?php
/**
 * Custom error handler for the application
 * This file manages error reporting, logging, and displaying errors
 */

// Error reporting settings
// In development: Set to E_ALL for catching all errors
// In production: Set to E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT
ini_set('display_errors', 0); // Don't show errors directly (we'll handle display ourselves)
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../logs/error.log'); // Set error log path

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Debug mode setting (should be false in production)
// You can toggle this in system settings later
$debug_mode = false;

// Get debug mode from system settings if available
if (function_exists('getSystemSetting')) {
    $debug_mode_setting = getSystemSetting('debug_mode');
    if ($debug_mode_setting !== false) {
        $debug_mode = filter_var($debug_mode_setting, FILTER_VALIDATE_BOOLEAN);
    }
}

// Store PHP errors
$php_errors = [];

/**
 * Custom error handler function
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    global $php_errors, $debug_mode, $is_admin_page;
    
    // Don't handle errors if they're suppressed with @
    if (error_reporting() === 0) {
        return false;
    }
    
    $error_type = '';
    switch ($errno) {
        case E_ERROR:
        case E_USER_ERROR:
            $error_type = 'Fatal Error';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $error_type = 'Warning';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $error_type = 'Notice';
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $error_type = 'Deprecated';
            break;
        default:
            $error_type = 'Unknown Error';
            break;
    }
    
    // Format the error message
    $error = [
        'type' => $error_type,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Always log the error
    error_log("[{$error['time']}] {$error['type']}: {$error['message']} in {$error['file']} on line {$error['line']}");
    
    // Store for display if in debug mode
    $php_errors[] = $error;
    
    // If this is an E_ERROR or E_USER_ERROR, we need to show something
    if (($errno == E_ERROR || $errno == E_USER_ERROR) && !$debug_mode) {
        showFatalErrorPage($error);
        exit(1);
    }
    
    // Let PHP handle the error normally as well
    return false;
}

/**
 * Handle uncaught exceptions
 */
function exceptionHandler($exception) {
    global $debug_mode, $is_admin_page;
    
    $error = [
        'type' => get_class($exception),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Log the exception
    error_log("[{$error['time']}] Uncaught Exception {$error['type']}: {$error['message']} in {$error['file']} on line {$error['line']}");
    error_log("Stack trace: " . $error['trace']);
    
    // Display friendly or detailed error based on debug mode
    if ($debug_mode) {
        showDebugErrorPage($error, true);
    } else {
        showFatalErrorPage($error);
    }
    
    exit(1);
}

/**
 * Handle fatal errors
 */
function fatalErrorHandler() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        global $debug_mode, $is_admin_page;
        
        $error_details = [
            'type' => 'Fatal Error',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'time' => date('Y-m-d H:i:s')
        ];
        
        // Log the error
        error_log("[{$error_details['time']}] {$error_details['type']}: {$error_details['message']} in {$error_details['file']} on line {$error_details['line']}");
        
        // Display friendly or detailed error based on debug mode
        if ($debug_mode) {
            showDebugErrorPage($error_details, true);
        } else {
            showFatalErrorPage($error_details);
        }
    }
}

/**
 * Display a user-friendly error page for fatal errors
 */
function showFatalErrorPage($error) {
    global $is_admin_page;
    
    // Clear any output that might have been generated
    ob_clean();
    
    // Set appropriate header
    header('HTTP/1.1 500 Internal Server Error');
    
    // Simple, user-friendly error page
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .error-container { margin-top: 100px; }
            .error-code { font-size: 80px; color: #dc3545; }
            .error-text { font-size: 24px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container text-center error-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="error-code">500</div>
                    <div class="error-text">Sorry, something went wrong</div>
                    <p class="lead">Our team has been notified. Please try again later.</p>
                    <p>Error Reference: ' . substr(md5($error['file'] . $error['line'] . $error['time']), 0, 8) . '</p>
                    <div class="mt-4">
                        <a href="' . ($is_admin_page ? '../admin/' : './') . '" class="btn btn-primary">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Display detailed error information for debugging
 */
function showDebugErrorPage($error, $is_exception = false) {
    global $php_errors, $is_admin_page;
    
    // Clear any output that might have been generated
    ob_clean();
    
    // Set appropriate header
    header('HTTP/1.1 500 Internal Server Error');
    
    // Detailed error page for developers
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Application Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .error-container { margin-top: 30px; margin-bottom: 50px; }
            .error-type { color: #dc3545; }
            pre { background-color: #f1f1f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
            .file-path { font-family: monospace; word-break: break-all; }
            .stack-trace { font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container error-container">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="error-type">' . htmlspecialchars($error['type']) . '</h1>
                    <p class="lead">' . htmlspecialchars($error['message']) . '</p>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            Error Details
                        </div>
                        <div class="card-body">
                            <p><strong>Time:</strong> ' . htmlspecialchars($error['time']) . '</p>
                            <p><strong>File:</strong> <span class="file-path">' . htmlspecialchars($error['file']) . '</span></p>
                            <p><strong>Line:</strong> ' . htmlspecialchars($error['line']) . '</p>';
    
    if ($is_exception && isset($error['trace'])) {
        echo '<div class="stack-trace">
                <h5>Stack Trace:</h5>
                <pre>' . htmlspecialchars($error['trace']) . '</pre>
             </div>';
    }
    
    echo '</div>
        </div>';
    
    // Show other errors if available
    if (!empty($php_errors)) {
        echo '<div class="card mt-4">
                <div class="card-header">
                    Other PHP Errors
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Message</th>
                                <th>File</th>
                                <th>Line</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($php_errors as $err) {
            echo '<tr>
                    <td>' . htmlspecialchars($err['type']) . '</td>
                    <td>' . htmlspecialchars($err['message']) . '</td>
                    <td class="file-path">' . htmlspecialchars($err['file']) . '</td>
                    <td>' . htmlspecialchars($err['line']) . '</td>
                    <td>' . htmlspecialchars($err['time']) . '</td>
                  </tr>';
        }
        
        echo '</tbody>
                    </table>
                </div>
            </div>';
    }
    
    echo '<div class="mt-4">
                    <a href="' . ($is_admin_page ? '../admin/' : './') . '" class="btn btn-primary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>';
}

/**
 * Function to display all collected errors at the end of page for admins
 */
function displayDebugInfo() {
    global $php_errors, $debug_mode, $is_admin_page;
    
    if ($debug_mode && $is_admin_page && !empty($php_errors)) {
        echo '<div class="container-fluid mt-5 debug-container" style="margin-bottom: 50px;">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>PHP Errors/Warnings/Notices:</h6>
                        <table class="table table-sm table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Message</th>
                                    <th>File</th>
                                    <th>Line</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>';
        
        foreach ($php_errors as $err) {
            echo '<tr>
                    <td>' . htmlspecialchars($err['type']) . '</td>
                    <td>' . htmlspecialchars($err['message']) . '</td>
                    <td style="font-family: monospace; font-size: 12px;">' . htmlspecialchars($err['file']) . '</td>
                    <td>' . htmlspecialchars($err['line']) . '</td>
                    <td>' . htmlspecialchars($err['time']) . '</td>
                  </tr>';
        }
        
        echo '</tbody>
                        </table>
                    </div>
                </div>
            </div>';
    }
}

// Register error handlers
set_error_handler('customErrorHandler');
set_exception_handler('exceptionHandler');
register_shutdown_function('fatalErrorHandler');

// Start output buffering to capture all output
ob_start(); 