<?php
// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection not available in latest_news.php");
    echo '<div class="alert alert-warning">News updates are currently unavailable. Please check back later.</div>';
} else {
    try {
        // Check if the blog_posts table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'blog_posts'");
        
        if ($table_check->num_rows == 0) {
            // Table doesn't exist, display placeholder content
            for ($i = 1; $i <= 3; $i++) {
                echo '<div class="news-item">
                        <img src="assets/images/news-' . $i . '.jpg" alt="News Image">
                        <div class="news-date">May ' . ($i * 5) . ', 2023</div>
                        <h3>Latest Updates to Canadian Immigration Policies</h3>
                        <p>Learn about the recent changes to immigration policies and how they might affect your application.</p>
                        <a href="resources/news-details.php?id=' . $i . '">Read More</a>
                    </div>';
            }
        } else {
            // Query blog posts from the database
            $query = "SELECT bp.*, u.first_name, u.last_name 
                      FROM blog_posts bp 
                      JOIN users u ON bp.author_id = u.user_id 
                      WHERE bp.status = 'published' 
                      ORDER BY bp.published_date DESC 
                      LIMIT 3";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($post = $result->fetch_assoc()) {
                    echo '<div class="news-item">
                            <img src="uploads/blog/' . htmlspecialchars($post['featured_image']) . '" alt="' . htmlspecialchars($post['title']) . '">
                            <div class="news-date">' . date('F j, Y', strtotime($post['published_date'])) . '</div>
                            <h3>' . htmlspecialchars($post['title']) . '</h3>
                            <p>' . htmlspecialchars(substr(strip_tags($post['excerpt']), 0, 120)) . '...</p>
                            <a href="resources/news-details.php?id=' . $post['post_id'] . '">Read More</a>
                        </div>';
                }
            } else {
                // No posts found, display placeholder content
                for ($i = 1; $i <= 3; $i++) {
                    echo '<div class="news-item">
                            <img src="assets/images/news-' . $i . '.jpg" alt="News Image">
                            <div class="news-date">May ' . ($i * 5) . ', 2023</div>
                            <h3>Latest Updates to Canadian Immigration Policies</h3>
                            <p>Learn about the recent changes to immigration policies and how they might affect your application.</p>
                            <a href="resources/news-details.php?id=' . $i . '">Read More</a>
                        </div>';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error in latest_news.php: " . $e->getMessage());
        echo '<div class="alert alert-warning">News updates are currently unavailable. Please check back later.</div>';
    }
}
?> 