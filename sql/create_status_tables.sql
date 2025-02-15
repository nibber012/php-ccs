-- Create applicant_status table
CREATE TABLE IF NOT EXISTS applicant_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT NOT NULL,
    status ENUM('Registered', 'Approved', 'Pending', 'Rejected') NOT NULL DEFAULT 'Registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_applicant (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create exam_status table
CREATE TABLE IF NOT EXISTS exam_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_id INT NOT NULL,
    part1_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    part2_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL DEFAULT 'Not Started',
    interview_status ENUM('Not Started', 'Scheduled', 'Completed', 'Pending') NOT NULL DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (applicant_id) REFERENCES applicants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_applicant (applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create export_history table for tracking exports
CREATE TABLE IF NOT EXISTS export_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    export_type ENUM('XLSX', 'CSV') NOT NULL,
    filters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
