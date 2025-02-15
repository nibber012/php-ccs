<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Get list of applicants who have passed their exams
try {
    $stmt = $db->query(
        "SELECT DISTINCT u.id, 
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.email,
                u.program,
                MAX(er.created_at) as last_exam_date
         FROM users u
         JOIN exam_results er ON u.id = er.user_id
         WHERE u.role = 'applicant'
         AND u.status = 'approved'
         AND er.status = 'passed'
         AND NOT EXISTS (
             SELECT 1 FROM interviews i 
             WHERE i.user_id = u.id 
             AND i.status IN ('scheduled', 'completed')
         )
         GROUP BY u.id
         ORDER BY u.first_name, u.last_name"
    );
    $eligible_applicants = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching eligible applicants: " . $e->getMessage());
    $error = "An error occurred while fetching eligible applicants.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $schedule_date = isset($_POST['schedule_date']) ? trim($_POST['schedule_date']) : '';
        $schedule_time = isset($_POST['schedule_time']) ? trim($_POST['schedule_time']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        if (!$user_id || !$schedule_date || !$schedule_time) {
            throw new Exception('Please fill in all required fields.');
        }

        // Combine date and time
        $schedule = date('Y-m-d H:i:s', strtotime("$schedule_date $schedule_time"));

        // Check if date is in the future
        if (strtotime($schedule) <= time()) {
            throw new Exception('Please select a future date and time.');
        }

        // Check for existing interview
        $stmt = $db->query(
            "SELECT COUNT(*) as count 
             FROM interviews 
             WHERE user_id = ? 
             AND status IN ('scheduled', 'completed')",
            [$user_id]
        );
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception('This applicant already has a scheduled or completed interview.');
        }

        // Insert new interview
        $db->query(
            "INSERT INTO interviews (user_id, schedule, notes, status, created_by) 
             VALUES (?, ?, ?, 'scheduled', ?)",
            [$user_id, $schedule, $notes, $auth->getCurrentUser()['id']]
        );

        // Send email notification
        $applicant = $db->query(
            "SELECT * FROM users WHERE id = ?",
            [$user_id]
        )->fetch();

        // TODO: Implement email notification
        // sendInterviewNotification($applicant, $schedule);

        $success = "Interview has been scheduled successfully.";

        // Redirect to prevent form resubmission
        header('Location: interview_list.php?success=' . urlencode($success));
        exit;

    } catch (Exception $e) {
        error_log("Error scheduling interview: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

admin_header('Schedule Interview');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Schedule Interview</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="interview_list.php">Interviews</a></li>
                        <li class="breadcrumb-item active">Schedule Interview</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($eligible_applicants)): ?>
                <div class="alert alert-info" role="alert">
                    <i class="bx bx-info-circle me-2"></i>
                    No eligible applicants found. Applicants must have passed their exams and not have any scheduled interviews.
                </div>
            <?php else: ?>
                <!-- Schedule Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="user_id" class="form-label">Select Applicant</label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">Choose an applicant...</option>
                                        <?php foreach ($eligible_applicants as $applicant): ?>
                                            <option value="<?php echo $applicant['id']; ?>">
                                                <?php echo htmlspecialchars($applicant['full_name']); ?> - 
                                                <?php echo htmlspecialchars($applicant['program']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select an applicant.
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label for="schedule_date" class="form-label">Interview Date</label>
                                    <input type="date" class="form-control" id="schedule_date" name="schedule_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a valid date.
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label for="schedule_time" class="form-label">Interview Time</label>
                                    <input type="time" class="form-control" id="schedule_time" name="schedule_time" required>
                                    <div class="invalid-feedback">
                                        Please select a valid time.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Add any notes or special instructions"></textarea>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-calendar-check"></i> Schedule Interview
                                    </button>
                                    <a href="interview_list.php" class="btn btn-secondary">
                                        <i class="bx bx-x"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Selected Applicant Details -->
                <div class="card mt-4 d-none" id="applicantDetails">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Applicant Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> <span id="applicantName"></span></p>
                                <p class="mb-1"><strong>Email:</strong> <span id="applicantEmail"></span></p>
                                <p class="mb-0"><strong>Program:</strong> <span id="applicantProgram"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Last Exam Date:</strong> <span id="lastExamDate"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Show applicant details when selected
    const userSelect = document.getElementById('user_id');
    const applicantDetails = document.getElementById('applicantDetails');
    const applicants = <?php echo json_encode($eligible_applicants); ?>;

    userSelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId) {
            const applicant = applicants.find(a => a.id === selectedId);
            if (applicant) {
                document.getElementById('applicantName').textContent = applicant.full_name;
                document.getElementById('applicantEmail').textContent = applicant.email;
                document.getElementById('applicantProgram').textContent = applicant.program;
                document.getElementById('lastExamDate').textContent = 
                    new Date(applicant.last_exam_date).toLocaleDateString();
                applicantDetails.classList.remove('d-none');
            }
        } else {
            applicantDetails.classList.add('d-none');
        }
    });
});
</script>

<?php admin_footer(); ?>
