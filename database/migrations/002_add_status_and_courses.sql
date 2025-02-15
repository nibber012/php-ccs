-- Add new status tables
CREATE TABLE application_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE exam_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add status columns to applicants table
ALTER TABLE applicants 
ADD COLUMN application_status_id INT,
ADD COLUMN exam_status_id INT,
ADD FOREIGN KEY (application_status_id) REFERENCES application_status(id),
ADD FOREIGN KEY (exam_status_id) REFERENCES exam_status(id);

-- Insert default status values
INSERT INTO application_status (name, description) VALUES
('pending', 'Application is under review'),
('approved', 'Application has been approved'),
('rejected', 'Application has been rejected'),
('incomplete', 'Application is missing required documents');

INSERT INTO exam_status (name, description) VALUES
('not_started', 'Exam has not been started'),
('in_progress', 'Exam is currently in progress'),
('completed', 'Exam has been completed'),
('graded', 'Exam has been graded'),
('failed', 'Failed to meet the required score'),
('passed', 'Passed the required score');
