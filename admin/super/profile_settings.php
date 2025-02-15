<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate input
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($first_name) || empty($last_name) || empty($email)) {
            throw new Exception('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Start transaction
        $conn->beginTransaction();

        try {
            // Update user information
            $query = "UPDATE users 
                     SET first_name = ?, last_name = ?, email = ?
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$first_name, $last_name, $email, $user['id']]);

            // Update admin information
            $query = "UPDATE super_admins 
                     SET first_name = ?, last_name = ?
                     WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$first_name, $last_name, $user['id']]);

            // Handle password change if requested
            if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
                if (empty($current_password)) {
                    throw new Exception('Current password is required to change password.');
                }

                if (empty($new_password) || empty($confirm_password)) {
                    throw new Exception('New password and confirmation are required.');
                }

                if ($new_password !== $confirm_password) {
                    throw new Exception('New passwords do not match.');
                }

                if (strlen($new_password) < 8) {
                    throw new Exception('Password must be at least 8 characters long.');
                }

                // Verify current password
                $query = "SELECT password FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$user['id']]);
                $stored_hash = $stmt->fetchColumn();

                if (!password_verify($current_password, $stored_hash)) {
                    throw new Exception('Current password is incorrect.');
                }

                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$new_hash, $user['id']]);
            }

            $conn->commit();
            $success = "Profile updated successfully.";

            // Refresh user data
            $user = $auth->getCurrentUser(true);
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('Profile Settings');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Profile Settings</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <hr>
                        <h5>Change Password</h5>
                        <p class="text-muted">Leave password fields empty if you don't want to change it.</p>

                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Information</h5>
                </div>
                <div class="card-body text-center">
                    <div class="display-1 text-primary mb-3">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted mb-1">Super Administrator</p>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
admin_footer();
?>
