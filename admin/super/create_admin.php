<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $department = $_POST['department'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name) || empty($department)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();

            // Check if email already exists
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([$email]);

            if ($check_stmt->rowCount() > 0) {
                $error = 'Email already exists';
            } else {
                // Start transaction
                $conn->beginTransaction();

                try {
                    // Create user account
                    $query = "INSERT INTO users (email, password, role, status) VALUES (?, ?, 'admin', 'active')";
                    $stmt = $conn->prepare($query);
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->execute([$email, $hashed_password]);
                    
                    $new_user_id = $conn->lastInsertId();

                    // Create admin profile
                    $query = "INSERT INTO admins (user_id, first_name, last_name, department) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$new_user_id, $first_name, $last_name, $department]);

                    // Log activity
                    $auth->logActivity(
                        $user['id'],
                        'admin_created',
                        "Created new admin account for {$first_name} {$last_name} ({$email})"
                    );

                    $conn->commit();
                    $success = 'Admin account created successfully';
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Create Admin Account';
admin_header($page_title);
?>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include_once '../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div class="page-content-wrapper">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><?php echo $page_title; ?></h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="list_admins.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter first name</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter last name</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                               required>
                                        <div class="invalid-feedback">Please enter a valid email address</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="department" class="form-label">Department</label>
                                        <select class="form-select" id="department" name="department" required>
                                            <option value="" disabled selected>Select Department</option>
                                            <option value="CCS" <?php echo (isset($_POST['department']) && $_POST['department'] === 'CCS') ? 'selected' : ''; ?>>College of Computer Studies</option>
                                            <option value="COE" <?php echo (isset($_POST['department']) && $_POST['department'] === 'COE') ? 'selected' : ''; ?>>College of Engineering</option>
                                            <option value="CAS" <?php echo (isset($_POST['department']) && $_POST['department'] === 'CAS') ? 'selected' : ''; ?>>College of Arts and Sciences</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a department</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="invalid-feedback">Please enter a password (minimum 8 characters)</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback">Please confirm your password</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class='bx bx-user-plus'></i> Create Admin Account
                                        </button>
                                        <a href="list_admins.php" class="btn btn-secondary">
                                            <i class='bx bx-x'></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Validation Script -->
<script>
(function () {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php admin_footer(); ?>
