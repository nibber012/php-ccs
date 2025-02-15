<?php
require_once 'classes/Auth.php';
require_once 'config/database.php';

$auth = new Auth();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $preferred_course = $_POST['preferred_course'] ?? '';

    // Map preferred_course to course ENUM value
$course_map = [
    'BS Computer Science' => 'BSCS',
    'BS Information Technology' => 'BSIT'
];

$course = $course_map[$preferred_course] ?? 'BSCS'; // Default to BSCS if not found

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            // Get database connection
            $database = Database::getInstance();
            $conn = $database->getConnection();

            // ✅ Log registration attempt
            error_log("DEBUG: Calling register() for user: $email");

            // Get the current year
            $year = date('Y');

            // Get the latest applicant number for this year
            $query = "SELECT MAX(CAST(SUBSTRING(applicant_number, 6) AS UNSIGNED)) as max_num 
                     FROM applicants 
                     WHERE applicant_number LIKE ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$year . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Generate new applicant number
            $sequence = ($result['max_num'] ?? 0) + 1;
            $applicant_number = $year . str_pad($sequence, 4, '0', STR_PAD_LEFT);

            // ✅ Call register() and log response
            $result = $auth->register($email, $password);
            error_log("DEBUG: Register() response: " . json_encode($result));

            if ($result['success']) {
                // ✅ Add applicant details
                $query = "INSERT INTO applicants (user_id, applicant_number, first_name, last_name, contact_number, course, preferred_course, progress_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'registered')";
                $stmt = $conn->prepare($query);

                if ($stmt->execute([$result['user_id'], $applicant_number, $first_name, $last_name, $contact_number, $course, $preferred_course])) {
                    $success = 'Registration successful! Your applicant number is ' . $applicant_number . '. Please wait for admin approval before logging in.';
                } else {
                    throw new Exception('Failed to save applicant details');
                }
            } else {
                // ✅ Log failed registration attempt
                error_log("DEBUG: Register() returned failure: " . $result['message']);
                $error = 'Registration failed: ' . $result['message'];
            }
        } catch (Exception $e) {
            error_log("DEBUG: Caught exception in register.php: " . $e->getMessage());
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Get list of available courses
$courses = [
    'BS Computer Science',
    'BS Information Technology'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CCS Freshman Screening</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 600px;
            margin: 2rem auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="card">
            <div class="card-header text-center">
                <h4 class="mb-0">Register for CCS Freshman Screening</h4>
            </div>
            <div class="card-body">
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

                <form method="post" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                            <div class="invalid-feedback">
                                Please enter your first name.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                            <div class="invalid-feedback">
                                Please enter your last name.
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                            <div class="invalid-feedback">
                                Please enter your contact number.
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="preferred_course" class="form-label">Preferred Course</label>
                            <select class="form-select" id="preferred_course" name="preferred_course" required>
                                <option value="">Choose...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo htmlspecialchars($course); ?>"><?php echo htmlspecialchars($course); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select your preferred course.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback">
                                Please enter a password.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">
                                Please confirm your password.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary w-100" type="submit">Register</button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
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
</body>
</html>
