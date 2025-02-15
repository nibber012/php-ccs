-- FAQs table
CREATE TABLE IF NOT EXISTS `faqs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `question` varchar(255) NOT NULL,
    `answer` text NOT NULL,
    `target_role` enum('all','applicant','admin') NOT NULL DEFAULT 'all',
    `priority` int(11) NOT NULL DEFAULT 0,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support tickets table
CREATE TABLE IF NOT EXISTS `support_tickets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `category` enum('technical','exam','account','other') NOT NULL,
    `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    `admin_response` text DEFAULT NULL,
    `admin_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `admin_id` (`admin_id`),
    CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample FAQs
INSERT INTO `faqs` (`question`, `answer`, `target_role`, `priority`) VALUES
('How do I take an exam?', 'To take an exam, go to the Exams section in your dashboard. You will see a list of available exams. Click on "Start Exam" for the exam you want to take. Make sure to read the instructions carefully before starting.', 'applicant', 100),
('What happens if I lose internet connection during an exam?', 'If you lose internet connection during an exam, your answers are automatically saved every few minutes. You can log back in and continue from where you left off, as long as the exam time hasn\'t expired.', 'applicant', 90),
('How long do I have to wait for exam results?', 'Exam results are typically available immediately after completion. You can view your results in the "My Results" section of your dashboard.', 'applicant', 80),
('Can I retake an exam if I fail?', 'Retake policies vary depending on the exam. Please contact support for specific information about retaking an exam.', 'applicant', 70),
('What should I do if I encounter technical issues?', 'If you encounter technical issues, first try refreshing your browser. If the problem persists, submit a support ticket through the Help & Support page with details about the issue.', 'all', 60);

-- Add system settings for support contact information if not exists
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('support_email', 'support@ccs-screening.com', 'Support email address'),
('support_phone', '+63 (123) 456-7890', 'Support phone number'),
('office_address', 'College of Computer Studies\nUniversity Campus\nCity, Province 1234', 'Office address');
