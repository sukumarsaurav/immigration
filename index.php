<?php
// Include the debug helper at the very top
require_once 'debug_helper.php';

// Set a flag for development mode
$debug_mode = true;

try {
    // Include the database connection
    require_once 'includes/db_config.php';
    
    // Check if the connection was successful
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }
    
    // Basic page structure
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Canada Immigration Consultancy</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='row'>
                <div class='col-12'>
                    <h1>Canada Immigration Consultancy</h1>
                    <p>Welcome to our website. We're currently working on some improvements.</p>
                </div>
            </div>";
    
    // Check if testimonials table exists and has data
    $testimonials_check = $conn->query("SHOW TABLES LIKE 'testimonials'");
    if ($testimonials_check->num_rows > 0) {
        $testimonials_count = $conn->query("SELECT COUNT(*) FROM testimonials")->fetch_row()[0];
        echo "<div class='row mt-4'>
                <div class='col-12'>
                    <h2>Testimonials</h2>
                    <p>We have {$testimonials_count} testimonials in our database.</p>
                </div>
              </div>";
    }
    
    // Check if blog_posts table exists and has data
    $blog_check = $conn->query("SHOW TABLES LIKE 'blog_posts'");
    if ($blog_check->num_rows > 0) {
        $blog_count = $conn->query("SELECT COUNT(*) FROM blog_posts")->fetch_row()[0];
        echo "<div class='row mt-4'>
                <div class='col-12'>
                    <h2>Blog Posts</h2>
                    <p>We have {$blog_count} blog posts in our database.</p>
                </div>
              </div>";
    }
    
    // Close the HTML structure
    echo "</div>
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    </body>
    </html>";
    
} catch (Exception $e) {
    // This will be caught by our custom exception handler in debug_helper.php
    throw $e;
}
?> 