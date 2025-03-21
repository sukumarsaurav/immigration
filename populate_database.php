<?php
// Include the debug helper
require_once 'debug_helper.php';

// Include the database connection
require_once 'includes/db_config.php';

echo "<h1>Database Population Tool</h1>";

// Check connection
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green'>Database connection successful!</p>";

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Add sample testimonials if table is empty
    $testimonials_count = $conn->query("SELECT COUNT(*) FROM testimonials")->fetch_row()[0];
    if ($testimonials_count == 0) {
        echo "<p>Adding sample testimonials...</p>";
        
        // Get user IDs for clients
        $client_users = $conn->query("SELECT user_id FROM users WHERE user_type = 'client' LIMIT 2");
        if ($client_users->num_rows > 0) {
            $client_ids = [];
            while ($row = $client_users->fetch_assoc()) {
                $client_ids[] = $row['user_id'];
            }
            
            // Insert testimonials
            $stmt = $conn->prepare("INSERT INTO testimonials (client_name, client_location, visa_type, testimonial_text, rating, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
            
            $testimonials = [
                ['John Smith', 'Toronto, Canada', 'Express Entry', 'The Express Entry process was much smoother with the help of Canada Immigration Consultancy. Their team guided me through every step and helped me maximize my CRS score.', 5],
                ['Emily Davis', 'Vancouver, Canada', 'Study Permit', 'I was struggling with my study permit application until I found Canada Immigration Consultancy. Their expertise made all the difference, and I got my approval within weeks!', 5],
                ['Michael Brown', 'Montreal, Canada', 'Provincial Nominee Program', 'The PNP application process was complex, but the consultants at CIC made it straightforward. Their knowledge of provincial programs is exceptional.', 4]
            ];
            
            foreach ($testimonials as $t) {
                $stmt->bind_param("ssssi", $t[0], $t[1], $t[2], $t[3], $t[4]);
                $stmt->execute();
            }
            
            echo "<p style='color:green'>Added " . count($testimonials) . " testimonials.</p>";
        } else {
            echo "<p style='color:orange'>No client users found. Skipping testimonials.</p>";
        }
    } else {
        echo "<p>Testimonials table already has data. Skipping.</p>";
    }
    
    // Add sample blog categories if table is empty
    $categories_count = $conn->query("SELECT COUNT(*) FROM blog_categories")->fetch_row()[0];
    if ($categories_count == 0) {
        echo "<p>Adding sample blog categories...</p>";
        
        $categories = [
            ['Express Entry', 'express-entry', 'Updates and information about the Express Entry immigration system'],
            ['Study Permits', 'study-permits', 'Information about studying in Canada and obtaining study permits'],
            ['Work Permits', 'work-permits', 'Guidance on Canadian work permits and employment opportunities'],
            ['Family Sponsorship', 'family-sponsorship', 'Information about sponsoring family members for Canadian immigration'],
            ['Immigration News', 'immigration-news', 'Latest updates and changes to Canadian immigration policies']
        ];
        
        $stmt = $conn->prepare("INSERT INTO blog_categories (category_name, slug, description) VALUES (?, ?, ?)");
        
        foreach ($categories as $c) {
            $stmt->bind_param("sss", $c[0], $c[1], $c[2]);
            $stmt->execute();
        }
        
        echo "<p style='color:green'>Added " . count($categories) . " blog categories.</p>";
    } else {
        echo "<p>Blog categories table already has data. Skipping.</p>";
    }
    
    // Add sample blog posts if table is empty
    $posts_count = $conn->query("SELECT COUNT(*) FROM blog_posts")->fetch_row()[0];
    if ($posts_count == 0) {
        echo "<p>Adding sample blog posts...</p>";
        
        // Get author ID (any consultant or admin)
        $author_query = $conn->query("SELECT user_id FROM users WHERE user_type IN ('admin', 'consultant') LIMIT 1");
        if ($author_query->num_rows > 0) {
            $author_id = $author_query->fetch_row()[0];
            
            $posts = [
                [
                    'title' => '2023 Express Entry Draw Results and Predictions',
                    'slug' => '2023-express-entry-draw-results-predictions',
                    'content' => '<p>The Express Entry system has seen significant changes in 2023. This article analyzes recent draw results and offers predictions for upcoming draws.</p><p>In the first quarter of 2023, Immigration, Refugees and Citizenship Canada (IRCC) has conducted several Express Entry draws with varying CRS score requirements. The most recent draws have shown a trend toward lower CRS score thresholds, making it easier for candidates to receive an Invitation to Apply (ITA).</p><p>Experts predict this trend will continue throughout the year as Canada aims to meet its ambitious immigration targets for 2023-2025.</p>',
                    'excerpt' => 'Analysis of recent Express Entry draws and predictions for upcoming invitation rounds in 2023.',
                    'featured_image' => 'express-entry-2023.jpg',
                    'status' => 'published'
                ],
                [
                    'title' => 'New Study Permit Processing Times and How to Prepare',
                    'slug' => 'study-permit-processing-times',
                    'content' => '<p>IRCC has announced changes to study permit processing times. This article explains the new timelines and provides tips for a successful application.</p><p>The Student Direct Stream (SDS) continues to offer expedited processing for applicants from eligible countries. However, standard study permit applications are now seeing processing times of 8-12 weeks on average.</p><p>To ensure the fastest possible processing, applicants should ensure all documentation is complete and accurate, apply well in advance of their intended start date, and consider using the online application portal rather than paper applications.</p>',
                    'excerpt' => 'Learn about the latest changes to study permit processing times and how to prepare your application for success.',
                    'featured_image' => 'study-permit-2023.jpg',
                    'status' => 'published'
                ]
            ];
            
            $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, status, author_id, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            
            foreach ($posts as $p) {
                $stmt->bind_param("ssssssi", $p['title'], $p['slug'], $p['content'], $p['excerpt'], $p['featured_image'], $p['status'], $author_id);
                $stmt->execute();
                $post_id = $conn->insert_id;
                
                // Add categories for this post
                if ($p['title'] == '2023 Express Entry Draw Results and Predictions') {
                    $express_entry_cat = $conn->query("SELECT category_id FROM blog_categories WHERE slug = 'express-entry'")->fetch_row()[0];
                    $news_cat = $conn->query("SELECT category_id FROM blog_categories WHERE slug = 'immigration-news'")->fetch_row()[0];
                    
                    $conn->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($post_id, $express_entry_cat), ($post_id, $news_cat)");
                } else {
                    $study_cat = $conn->query("SELECT category_id FROM blog_categories WHERE slug = 'study-permits'")->fetch_row()[0];
                    $news_cat = $conn->query("SELECT category_id FROM blog_categories WHERE slug = 'immigration-news'")->fetch_row()[0];
                    
                    $conn->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($post_id, $study_cat), ($post_id, $news_cat)");
                }
            }
            
            echo "<p style='color:green'>Added " . count($posts) . " blog posts with categories.</p>";
        } else {
            echo "<p style='color:orange'>No admin or consultant users found. Skipping blog posts.</p>";
        }
    } else {
        echo "<p>Blog posts table already has data. Skipping.</p>";
    }
    
    // Commit transaction
    $conn->commit();
    echo "<p style='color:green'>Database population completed successfully!</p>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?> 