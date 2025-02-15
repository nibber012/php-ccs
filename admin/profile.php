<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';

$auth = new Auth();
$db = Database::getInstance();

// Get current user
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'email' => $_POST['email'] ?? ''
        ];

        // Validate input
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            throw new Exception('All fields are required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email is already taken by another user
        $stmt = $db->query("SELECT id FROM users WHERE email = ? AND id != ?", [$data['email'], $user['id']]);
        if ($stmt->fetch()) {
            throw new Exception('Email is already taken');
        }

        // Update user profile
        $db->query(
            "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?",
            [$data['first_name'], $data['last_name'], $data['email'], $user['id']]
        );

        // If user is admin or super_admin, update their respective tables
        if ($user['role'] === 'super_admin') {
            $db->query(
                "UPDATE super_admins SET first_name = ?, last_name = ? WHERE user_id = ?",
                [$data['first_name'], $data['last_name'], $user['id']]
            );
        } elseif ($user['role'] === 'admin') {
            $db->query(
                "UPDATE admins SET first_name = ?, last_name = ? WHERE user_id = ?",
                [$data['first_name'], $data['last_name'], $user['id']]
            );
        }

        $success = 'Profile updated successfully';
        
        // Refresh user data
        $user = $auth->getCurrentUser();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
require_once 'includes/layout.php';
admin_header('My Profile');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin/super/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Profile</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">To change your password, please contact the system administrator.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
