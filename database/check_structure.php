<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
    
    // Check interview_schedules table structure
    $stmt = $conn->query("DESCRIBE interview_schedules");
    echo "<h3>Interview Schedules Table Structure:</h3>";
    echo "<pre>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Try to add columns if they don't exist
    try {
        $conn->exec("ALTER TABLE interview_schedules 
                    ADD COLUMN IF NOT EXISTS interview_status ENUM('pending', 'passed', 'failed') NOT NULL DEFAULT 'pending',
                    ADD COLUMN IF NOT EXISTS total_score INT DEFAULT NULL");
        echo "<div style='color: green;'>Columns added successfully!</div>";
    } catch (PDOException $e) {
        // If the above fails, try adding them one by one
        try {
            $conn->exec("ALTER TABLE interview_schedules 
                        ADD COLUMN interview_status ENUM('pending', 'passed', 'failed') NOT NULL DEFAULT 'pending'");
            echo "<div style='color: green;'>interview_status column added!</div>";
        } catch (PDOException $e) {
            echo "<div style='color: orange;'>interview_status: " . $e->getMessage() . "</div>";
        }
        
        try {
            $conn->exec("ALTER TABLE interview_schedules 
                        ADD COLUMN total_score INT DEFAULT NULL");
            echo "<div style='color: green;'>total_score column added!</div>";
        } catch (PDOException $e) {
            echo "<div style='color: orange;'>total_score: " . $e->getMessage() . "</div>";
        }
    }
    
    // Check the structure again
    $stmt = $conn->query("DESCRIBE interview_schedules");
    echo "<h3>Updated Interview Schedules Table Structure:</h3>";
    echo "<pre>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
