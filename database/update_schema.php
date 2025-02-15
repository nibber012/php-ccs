<?php
require_once '../config/database.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
    
    // Read and execute schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $conn->exec($query);
        }
    }
    
    echo "Database schema updated successfully!";
    
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage();
}
?>
