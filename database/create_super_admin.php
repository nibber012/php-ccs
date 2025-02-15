<?php
require_once __DIR__ . '/../config/database.php';

// Create database connection
$database = Database::getInstance();;
$conn = $database->getConnection();

// Super admin details
$email = 'super_admin@ccs.edu.ph';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$first_name = 'Super';
$last_name = 'Admin';
$role = 'super_admin';
$status = 'active';

try {
    // Check if super admin already exists
    $check_query = "SELECT id FROM users WHERE email = ? AND role = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$email, $role]);

    if ($check_stmt->rowCount() === 0) {
        // Insert super admin
        $query = "INSERT INTO users (email, password, first_name, last_name, role, status) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email, $password, $first_name, $last_name, $role, $status]);
        echo "Super admin account created successfully!\n";
        echo "Email: $email\n";
        echo "Password: admin123\n";
    } else {
        echo "Super admin account already exists!\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
