<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
    
    // Add timestamp columns to applicants table
    $conn->exec("
        ALTER TABLE applicants 
        ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ");
    
    echo "Successfully added timestamp columns to applicants table!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
