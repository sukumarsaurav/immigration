<?php
// Check if database connection exists
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection not available in testimonials_slider.php");
    echo '<div class="alert alert-warning">Testimonials are currently unavailable. Please check back later.</div>';
} else {
    try {
        // Check if the testimonials table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'testimonials'");
        
        if ($table_check->num_rows == 0) {
            // Table doesn't exist, display placeholder
            echo '<div class="testimonial-item">
                    <div class="testimonial-content">
                        <p>"Canada Immigration Consultancy helped me navigate the complex immigration process with ease. Their expertise and guidance were invaluable."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="assets/images/testimonial-1.jpg" alt="John Doe">
                        <div>
                            <h4>John Doe</h4>
                            <p>Express Entry, 2023</p>
                        </div>
                    </div>
                </div>';
        } else {
            // Query testimonials from the database
            $query = "SELECT t.*, u.first_name, u.last_name, u.profile_image 
                      FROM testimonials t 
                      JOIN users u ON t.user_id = u.user_id 
                      WHERE t.status = 'approved' 
                      ORDER BY t.created_at DESC 
                      LIMIT 5";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($testimonial = $result->fetch_assoc()) {
                    echo '<div class="testimonial-item">
                            <div class="testimonial-content">
                                <p>"' . htmlspecialchars($testimonial['content']) . '"</p>
                            </div>
                            <div class="testimonial-author">';
                    
                    if (!empty($testimonial['profile_image'])) {
                        echo '<img src="uploads/profile/' . htmlspecialchars($testimonial['profile_image']) . '" alt="' . htmlspecialchars($testimonial['first_name'] . ' ' . $testimonial['last_name']) . '">';
                    } else {
                        echo '<img src="assets/images/default-profile.jpg" alt="' . htmlspecialchars($testimonial['first_name'] . ' ' . $testimonial['last_name']) . '">';
                    }
                    
                    echo '<div>
                                <h4>' . htmlspecialchars($testimonial['first_name'] . ' ' . $testimonial['last_name']) . '</h4>
                                <p>' . htmlspecialchars($testimonial['visa_type']) . ', ' . date('Y', strtotime($testimonial['created_at'])) . '</p>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                // No testimonials found, display placeholder
                echo '<div class="testimonial-item">
                        <div class="testimonial-content">
                            <p>"Canada Immigration Consultancy helped me navigate the complex immigration process with ease. Their expertise and guidance were invaluable."</p>
                        </div>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonial-1.jpg" alt="John Doe">
                            <div>
                                <h4>John Doe</h4>
                                <p>Express Entry, 2023</p>
                            </div>
                        </div>
                    </div>';
            }
        }
    } catch (Exception $e) {
        error_log("Error in testimonials_slider.php: " . $e->getMessage());
        echo '<div class="alert alert-warning">Testimonials are currently unavailable. Please check back later.</div>';
    }
}
?> 