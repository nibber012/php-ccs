<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
    
    // Add interview_status column
    try {
        $conn->exec("ALTER TABLE interview_schedules ADD COLUMN interview_status ENUM('pending', 'passed', 'failed') NOT NULL DEFAULT 'pending'");
        echo "Added interview_status column<br>";
    } catch (PDOException $e) {
        if ($e->getCode() != '42S21') { // Ignore if column already exists
            throw $e;
        }
        echo "interview_status column already exists<br>";
    }

    // Add total_score column
    try {
        $conn->exec("ALTER TABLE interview_schedules ADD COLUMN total_score INT DEFAULT NULL");
        echo "Added total_score column<br>";
    } catch (PDOException $e) {
        if ($e->getCode() != '42S21') { // Ignore if column already exists
            throw $e;
        }
        echo "total_score column already exists<br>";
    }

    // Recreate interview_scores table
    $conn->exec("DROP TABLE IF EXISTS interview_scores");
    $conn->exec("
        CREATE TABLE interview_scores (
            id INT PRIMARY KEY AUTO_INCREMENT,
            interview_id INT NOT NULL,
            category ENUM('technical_skills', 'communication', 'problem_solving', 'cultural_fit', 'overall_impression') NOT NULL,
            score INT NOT NULL,
            remarks TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
            FOREIGN KEY (interview_id) REFERENCES interview_schedules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "Recreated interview_scores table<br>";
    
    echo "<br>Database update completed successfully!";
    
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
