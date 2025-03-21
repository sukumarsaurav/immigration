-- Sample data for users table without specifying IDs
INSERT INTO users (first_name, last_name, email, phone, password, user_type, status) 
SELECT 'John', 'Smith', 'john.smith@example.com', '+1-555-123-4567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'consultant', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'john.smith@example.com');

INSERT INTO users (first_name, last_name, email, phone, password, user_type, status) 
SELECT 'Sarah', 'Johnson', 'sarah.johnson@example.com', '+1-555-234-5678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'consultant', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'sarah.johnson@example.com');

INSERT INTO users (first_name, last_name, email, phone, password, user_type, status) 
SELECT 'Michael', 'Brown', 'michael.brown@example.com', '+1-555-345-6789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'michael.brown@example.com');

INSERT INTO users (first_name, last_name, email, phone, password, user_type, status) 
SELECT 'Emily', 'Davis', 'emily.davis@example.com', '+1-555-456-7890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'emily.davis@example.com');

-- Get user IDs for testimonials
SET @michael_id = (SELECT user_id FROM users WHERE email = 'michael.brown@example.com');
SET @emily_id = (SELECT user_id FROM users WHERE email = 'emily.davis@example.com');

-- Sample data for testimonials table
INSERT INTO testimonials (user_id, content, visa_type, rating, status, created_at)
VALUES
(@michael_id, 'The Express Entry process was much smoother with the help of Canada Immigration Consultancy. Their team guided me through every step and helped me maximize my CRS score.', 'Express Entry', 5, 'approved', '2023-03-15 10:30:00'),
(@emily_id, 'I was struggling with my study permit application until I found Canada Immigration Consultancy. Their expertise made all the difference, and I got my approval within weeks!', 'Study Permit', 5, 'approved', '2023-04-22 14:45:00'),
(@michael_id, 'The PNP application process was complex, but the consultants at CIC made it straightforward. Their knowledge of provincial programs is exceptional.', 'Provincial Nominee Program', 4, 'approved', '2023-05-10 09:15:00');

-- Continue with blog categories and posts in a similar way... 