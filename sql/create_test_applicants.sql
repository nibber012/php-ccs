-- Insert test applicants
INSERT INTO applicants (first_name, last_name, email, phone, status)
VALUES 
('John', 'Smith', 'john.smith@example.com', '1234567890', 'approved'),
('Jane', 'Doe', 'jane.doe@example.com', '0987654321', 'approved');

-- Get the IDs of the inserted applicants
SET @john_id = LAST_INSERT_ID();
SET @jane_id = @john_id + 1;

-- Create user accounts for the applicants
INSERT INTO users (email, password, role, status)
VALUES 
('john.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'applicant', 'active'), -- password: password
('jane.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'applicant', 'active'); -- password: password

-- Insert exam results for Part 1 (assuming exam_id = 1 for Part 1)
INSERT INTO exam_results (applicant_id, exam_id, score, passing_score, status)
VALUES 
(@john_id, 1, 85, 75, 'pass'),
(@jane_id, 1, 90, 75, 'pass');

-- Insert exam results for Part 2 (assuming exam_id = 2 for Part 2)
INSERT INTO exam_results (applicant_id, exam_id, score, passing_score, status)
VALUES 
(@john_id, 2, 88, 75, 'pass'),
(@jane_id, 2, 92, 75, 'pass');
