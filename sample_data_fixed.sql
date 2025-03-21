-- Sample data for users table with IGNORE to skip duplicates
INSERT IGNORE INTO users (user_id, first_name, last_name, email, phone, password, user_type, status) VALUES
(1, 'Admin', 'User', 'admin@example.com', '+1-555-000-0000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(2, 'John', 'Smith', 'john.smith@example.com', '+1-555-123-4567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'consultant', 'active'),
(3, 'Sarah', 'Johnson', 'sarah.johnson@example.com', '+1-555-234-5678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'consultant', 'active'),
(4, 'Michael', 'Brown', 'michael.brown@example.com', '+1-555-345-6789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active'),
(5, 'Emily', 'Davis', 'emily.davis@example.com', '+1-555-456-7890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active');

-- Sample data for testimonials table with IGNORE to skip duplicates
INSERT IGNORE INTO testimonials (testimonial_id, user_id, content, visa_type, rating, status, created_at) VALUES
(1, 4, 'The Express Entry process was much smoother with the help of Canada Immigration Consultancy. Their team guided me through every step and helped me maximize my CRS score.', 'Express Entry', 5, 'approved', '2023-03-15 10:30:00'),
(2, 5, 'I was struggling with my study permit application until I found Canada Immigration Consultancy. Their expertise made all the difference, and I got my approval within weeks!', 'Study Permit', 5, 'approved', '2023-04-22 14:45:00'),
(3, 4, 'The PNP application process was complex, but the consultants at CIC made it straightforward. Their knowledge of provincial programs is exceptional.', 'Provincial Nominee Program', 4, 'approved', '2023-05-10 09:15:00');

-- Sample data for blog_categories table with IGNORE to skip duplicates
INSERT IGNORE INTO blog_categories (category_id, category_name, slug, description) VALUES
(1, 'Express Entry', 'express-entry', 'Updates and information about the Express Entry immigration system'),
(2, 'Study Permits', 'study-permits', 'Information about studying in Canada and obtaining study permits'),
(3, 'Work Permits', 'work-permits', 'Guidance on Canadian work permits and employment opportunities'),
(4, 'Family Sponsorship', 'family-sponsorship', 'Information about sponsoring family members for Canadian immigration'),
(5, 'Immigration News', 'immigration-news', 'Latest updates and changes to Canadian immigration policies');

-- Sample data for blog_posts table with IGNORE to skip duplicates
INSERT IGNORE INTO blog_posts (post_id, title, slug, content, excerpt, featured_image, status, author_id, published_date, created_at) VALUES
(1, '2023 Express Entry Draw Results and Predictions', '2023-express-entry-draw-results-predictions', '<p>The Express Entry system has seen significant changes in 2023. This article analyzes recent draw results and offers predictions for upcoming draws.</p><p>In the first quarter of 2023, Immigration, Refugees and Citizenship Canada (IRCC) has conducted several Express Entry draws with varying CRS score requirements. The most recent draws have shown a trend toward lower CRS score thresholds, making it easier for candidates to receive an Invitation to Apply (ITA).</p><p>Experts predict this trend will continue throughout the year as Canada aims to meet its ambitious immigration targets for 2023-2025.</p>', 'Analysis of recent Express Entry draws and predictions for upcoming invitation rounds in 2023.', 'express-entry-2023.jpg', 'published', 2, '2023-06-15 09:00:00', '2023-06-10 14:30:00'),
(2, 'New Study Permit Processing Times and How to Prepare', 'study-permit-processing-times', '<p>IRCC has announced changes to study permit processing times. This article explains the new timelines and provides tips for a successful application.</p><p>The Student Direct Stream (SDS) continues to offer expedited processing for applicants from eligible countries. However, standard study permit applications are now seeing processing times of 8-12 weeks on average.</p><p>To ensure the fastest possible processing, applicants should ensure all documentation is complete and accurate, apply well in advance of their intended start date, and consider using the online application portal rather than paper applications.</p>', 'Learn about the latest changes to study permit processing times and how to prepare your application for success.', 'study-permit-2023.jpg', 'published', 3, '2023-05-20 10:30:00', '2023-05-15 16:45:00'),
(3, 'Changes to the Temporary Foreign Worker Program', 'temporary-foreign-worker-program-changes', '<p>The Canadian government has implemented several changes to the Temporary Foreign Worker Program. This article outlines the key updates and their impact.</p><p>Recent changes include increased flexibility for employers in certain sectors, adjustments to the Labour Market Impact Assessment (LMIA) process, and new pathways to permanent residency for temporary workers.</p><p>These changes aim to address labor shortages while ensuring protections for both Canadian workers and temporary foreign workers.</p>', 'Overview of recent changes to Canada\'s Temporary Foreign Worker Program and what they mean for employers and workers.', 'tfwp-2023.jpg', 'published', 2, '2023-04-10 11:15:00', '2023-04-05 13:20:00');

-- Delete existing blog_post_categories entries to avoid duplicates
DELETE FROM blog_post_categories WHERE post_id IN (1, 2, 3);

-- Sample data for blog_post_categories (many-to-many relationship)
INSERT INTO blog_post_categories (post_id, category_id) VALUES
(1, 1), -- Express Entry article in Express Entry category
(1, 5), -- Express Entry article also in Immigration News category
(2, 2), -- Study Permit article in Study Permits category
(2, 5), -- Study Permit article also in Immigration News category
(3, 3), -- TFWP article in Work Permits category
(3, 5); -- TFWP article also in Immigration News category

-- Add debug mode setting if not already present
INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_description, is_public, setting_group) 
VALUES ('debug_mode', 'true', 'boolean', 'Enable debug mode for detailed error reporting (should be OFF in production)', 0, 'system')
ON DUPLICATE KEY UPDATE setting_value = 'true'; 