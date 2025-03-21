<?php
// Include the debug helper
require_once 'debug_helper.php';

// Database credentials - temporary for testing
$db_host = 'localhost';
$db_name = 'u911550082_canada_imm'; // Update with your actual database name
$db_user = 'u911550082_admin';      // Update with your actual database username
$db_pass = 'YourPassword123';       // Update with your actual database password

echo "<h1>Database Diagnostic Tool</h1>";
echo "<p>Attempting to connect to database: $db_name as user: $db_user on host: $db_host</p>";

try {
    // Create connection
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn->connect_error) {
        die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
    }

    echo "<p style='color:green'>Database connection successful!</p>";

    // Get all tables
    $result = $conn->query("SHOW TABLES");

    if ($result) {
        echo "<h2>Tables in Database</h2>";
        echo "<ul>";
        while ($row = $result->fetch_row()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Error listing tables: " . $conn->error . "</p>";
    }

    // Check specific tables needed for the homepage
    $required_tables = ['users', 'testimonials', 'blog_posts', 'blog_categories', 'blog_post_categories'];

    echo "<h2>Required Tables Check</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";

    foreach ($required_tables as $table) {
        $table_exists = $conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0;
        $status = $table_exists ? "Exists" : "Missing";
        $row_count = $table_exists ? $conn->query("SELECT COUNT(*) FROM $table")->fetch_row()[0] : "N/A";
        
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>" . ($table_exists ? "✅ Exists" : "❌ Missing") . "</td>";
        echo "<td>$row_count</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Check if testimonials table has the expected structure
    if ($conn->query("SHOW TABLES LIKE 'testimonials'")->num_rows > 0) {
        echo "<h2>Testimonials Table Structure</h2>";
        $result = $conn->query("DESCRIBE testimonials");
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . ($value ?? "NULL") . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }

    // Check if blog_posts table has the expected structure
    if ($conn->query("SHOW TABLES LIKE 'blog_posts'")->num_rows > 0) {
        echo "<h2>Blog Posts Table Structure</h2>";
        $result = $conn->query("DESCRIBE blog_posts");
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . ($value ?? "NULL") . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>Exception: " . $e->getMessage() . "</p>";
}
?> 