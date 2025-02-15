<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
    
    // Drop and recreate the columns to ensure they are correct
    $sql = "
        ALTER TABLE interview_schedules 
        DROP COLUMN IF EXISTS interview_status,
        DROP COLUMN IF EXISTS total_score;
        
        ALTER TABLE interview_schedules 
        ADD COLUMN interview_status ENUM('pending', 'passed', 'failed') NOT NULL DEFAULT 'pending',
        ADD COLUMN total_score INT DEFAULT NULL;
    ";
    
    $conn->exec($sql);
    echo "Successfully updated the interview_schedules table!";
    
} catch (PDOException $e) {
    // If the above fails, try individual statements
    try {
        $conn->exec("ALTER TABLE interview_schedules DROP COLUMN IF EXISTS interview_status");
        $conn->exec("ALTER TABLE interview_schedules DROP COLUMN IF EXISTS total_score");
        $conn->exec("ALTER TABLE interview_schedules ADD COLUMN interview_status ENUM('pending', 'passed', 'failed') NOT NULL DEFAULT 'pending'");
        $conn->exec("ALTER TABLE interview_schedules ADD COLUMN total_score INT DEFAULT NULL");
        echo "Successfully updated the interview_schedules table (individual statements)!";
    } catch (PDOException $e2) {
        echo "Error: " . $e2->getMessage();
    }
}

// Verify the columns exist
try {
    $result = $conn->query("DESCRIBE interview_schedules");
    echo "<h3>Current Table Structure:</h3><pre>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error checking structure: " . $e->getMessage();
}
?>
