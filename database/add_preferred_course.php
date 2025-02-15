<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
    
    // Add preferred_course column to applicants table
    $conn->exec("
        ALTER TABLE applicants 
        ADD COLUMN IF NOT EXISTS preferred_course ENUM('BSCS', 'BSIT') NOT NULL DEFAULT 'BSCS'
    ");
    
    echo "Successfully added preferred_course column to applicants table!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
