<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
error_log("DEBUG: Session data: " . print_r($_SESSION, true));
error_log("DEBUG: Current user: " . print_r($user, true));

try {
    $auth->requireRole('applicant');
    error_log("DEBUG: Role verification successful.");
} catch (Exception $e) {
    error_log("ERROR: Role verification failed: " . $e->getMessage());
    exit;
}




// Get applicant details
$database = Database::getInstance();;
$conn = $database->getConnection();

$query = "SELECT * FROM applicants WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$user['id']]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC) ?: []; // Ensure it's an array

if (!$applicant) {
    error_log("ERROR: No applicant record found for user ID: {$user['id']}");
    $applicant = [
        'first_name' => 'Unknown',
        'last_name' => '',
        'preferred_course' => 'N/A',
        'contact_number' => 'Not provided',
        'address' => 'Not provided',
        'progress_status' => 'Registered',
        'id' => 0,
    ];
}


// Handle profile update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    
    // Update user email
    $query = "UPDATE users SET email = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $emailUpdated = $stmt->execute([$email, $user['id']]);
    
    // Update applicant details
    $query = "UPDATE applicants SET 
              contact_number = ?,
              address = ?
              WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $applicantUpdated = $stmt->execute([$phone, $address, $user['id']]);
    
    if ($emailUpdated && $applicantUpdated) {
        $message = "Profile updated successfully!";
        $messageType = "success";
        
        // Refresh applicant data
        $query = "SELECT * FROM applicants WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user['id']]);
        $applicant = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "Failed to update profile. Please try again.";
        $messageType = "danger";
    }
}

get_header('My Profile');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid px-4 py-4" style="margin-top: 60px;">
        <div class="row">
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></h5>
                        <p class="text-muted mb-3"><?php echo ucfirst($applicant['preferred_course'] ?? 'N/A'); ?> Applicant</p>
                        <div class="d-flex justify-content-center">
                            <span class="badge bg-primary me-2">
                                <i class="bi bi-person-badge me-1"></i>
                                ID: <?php echo str_pad($applicant['id'] ?? 0, 6, '0', STR_PAD_LEFT); ?>
                            </span>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo ucfirst($applicant['progress_status'] ?? 'Registered'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="card-title">Contact Information</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-envelope me-2 text-muted"></i>
                                <?php echo htmlspecialchars($user['email']); ?>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-telephone me-2 text-muted"></i>
                                <?php echo htmlspecialchars($applicant['contact_number'] ?? 'Not provided'); ?>
                            </li>
                            <li>
                                <i class="bi bi-geo-alt me-2 text-muted"></i>
                                <?php echo htmlspecialchars($applicant['address'] ?? 'Not provided'); ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Application Status -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Application Status</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Registration
                                <span class="badge bg-success rounded-pill">
                                    <i class="bi bi-check"></i>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Entrance Exam
                                <?php if (isset($applicant['exams_completed']) && $applicant['exams_completed'] > 0): ?>
                                    <span class="badge bg-success rounded-pill">
                                        <i class="bi bi-check"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning rounded-pill">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Interview
                                <?php if ($applicant['interview_completed'] ?? false): ?>
                                    <span class="badge bg-success rounded-pill">
                                        <i class="bi bi-check"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning rounded-pill">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Profile Update Form -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Edit Profile</h5>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="mt-4">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($applicant['first_name']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($applicant['last_name']); ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($applicant['contact_number'] ?? 'Not provided'); ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($applicant['address'] ?? 'Not provided'); ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Preferred Course</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($applicant['preferred_course']); ?>" readonly>
                                <small class="text-muted">Course preference cannot be changed after registration.</small>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
