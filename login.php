<?php
require_once 'middleware/SessionManager.php';
require_once 'classes/Auth.php';

SessionManager::start();
$auth = new Auth();

// Clear any existing session if accessing login page directly
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $auth->isLoggedIn()) {
    $auth->logout();
}

$error = '';
$success = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? '';
    
    if (empty($email) || empty($password) || empty($selected_role)) {
        $error = 'All fields are required';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            $user = $result['user'];
            
            if (!$user) {
                $error = 'Failed to retrieve user information';
            } elseif ($user['role'] !== $selected_role) {
                $error = 'Invalid role selected for this account';
                $auth->logout();
            } else {
                // Redirect based on role
                switch ($selected_role) {
                    case 'super_admin':
                        header('Location: /php-ccs/admin/super/dashboard.php');
                        exit();
                    case 'admin':
                        header('Location: /php-ccs/admin/dashboard.php');
                        exit();
                    case 'applicant':
                        header('Location: /php-ccs/applicant/dashboard.php');
                        exit();
                    default:
                        $error = 'Invalid role type';
                        $auth->logout();
                }
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CCS Freshman Screening</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: #0d6efd;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            text-align: center;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
        }
        .school-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        .role-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            margin-bottom: 20px;
        }
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .role-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .role-card .card-body {
            padding: 20px;
            text-align: center;
        }
        .role-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #0d6efd;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <!-- Add your school logo here -->
                <img src="assets/images/logo.png" alt="School Logo" class="school-logo">
                <h4 class="mb-0">CCS Freshman Screening</h4>
                <p class="mb-0">Sign in to continue</p>
            </div>
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="row" id="roleSelection">
                    <div class="col-md-4">
                        <div class="card role-card" data-role="admin">
                            <div class="card-body">
                                <i class="bi bi-person-workspace role-icon"></i>
                                <h5 class="card-title">Admin</h5>
                                <p class="card-text">System administrators</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card role-card" data-role="applicant">
                            <div class="card-body">
                                <i class="bi bi-person-badge role-icon"></i>
                                <h5 class="card-title">Applicant</h5>
                                <p class="card-text">New student applicants</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card role-card" data-role="super_admin">
                            <div class="card-body">
                                <i class="bi bi-shield-lock role-icon"></i>
                                <h5 class="card-title">Super Admin</h5>
                                <p class="card-text">System superusers</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" class="login-form mt-4" style="display: none;">
                    <input type="hidden" name="role" id="selectedRole">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Sign In</button>
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-link" id="backToRoles">← Back to role selection</a>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleCards = document.querySelectorAll('.role-card');
            const loginForm = document.querySelector('.login-form');
            const roleSelection = document.getElementById('roleSelection');
            const selectedRoleInput = document.getElementById('selectedRole');
            const backToRoles = document.getElementById('backToRoles');
            
            roleCards.forEach(card => {
                card.addEventListener('click', function() {
                    const role = this.dataset.role;
                    selectedRoleInput.value = role;
                    roleSelection.style.display = 'none';
                    loginForm.style.display = 'block';
                });
            });
            
            backToRoles.addEventListener('click', function(e) {
                e.preventDefault();
                loginForm.style.display = 'none';
                roleSelection.style.display = 'flex';
            });
        });
    </script>
</body>
</html>
