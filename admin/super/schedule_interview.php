<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../../classes/Email.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // Get list of applicants who have completed Part 2
    $query = "SELECT 
                a.id,
                CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                a.contact_number,
                u.email,
                a.progress_status
              FROM applicants a
              JOIN users u ON a.user_id = u.id
              WHERE a.progress_status = 'part2_completed'
              AND NOT EXISTS (
                SELECT 1 FROM interview_schedules i 
                WHERE i.applicant_id = a.id 
                AND i.status != 'cancelled'
              )
              ORDER BY a.last_name, a.first_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $applicants = $stmt->fetchAll();

    // Get list of available interviewers (admins and super admins)
    $query = "SELECT 
                u.id,
                CONCAT(u.first_name, ' ', u.last_name) as interviewer_name,
                u.role
              FROM users u
              WHERE u.role IN ('admin', 'super_admin')
              AND u.status = 'active'
              ORDER BY u.role DESC, u.last_name, u.first_name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $interviewers = $stmt->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $applicant_id = $_POST['applicant_id'] ?? '';
        $interviewer_id = $_POST['interviewer_id'] ?? '';
        $schedule_date = $_POST['schedule_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $meeting_link = $_POST['meeting_link'] ?? '';

        if (!$applicant_id || !$interviewer_id || !$schedule_date || !$start_time || !$end_time || !$meeting_link) {
            throw new Exception('Please fill in all required fields.');
        }

        // Validate date and time
        $schedule_datetime = new DateTime($schedule_date . ' ' . $start_time);
        $end_datetime = new DateTime($schedule_date . ' ' . $end_time);
        $now = new DateTime();

        if ($schedule_datetime < $now) {
            throw new Exception('Interview date and time must be in the future.');
        }

        if ($end_datetime <= $schedule_datetime) {
            throw new Exception('End time must be after start time.');
        }

        // Validate meeting link
        if (!filter_var($meeting_link, FILTER_VALIDATE_URL)) {
            throw new Exception('Please enter a valid meeting link.');
        }

        // Check for interviewer availability
        $query = "SELECT COUNT(*) as count
                  FROM interview_schedules
                  WHERE interviewer_id = ?
                  AND schedule_date = ?
                  AND status != 'cancelled'
                  AND (
                      (start_time BETWEEN ? AND ?) OR
                      (end_time BETWEEN ? AND ?) OR
                      (start_time <= ? AND end_time >= ?)
                  )";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $interviewer_id,
            $schedule_date,
            $start_time,
            $end_time,
            $start_time,
            $end_time,
            $start_time,
            $end_time
        ]);
        
        if ($stmt->fetch()['count'] > 0) {
            throw new Exception('The interviewer is not available at the selected time.');
        }

        // Create interview schedule
        $query = "INSERT INTO interview_schedules 
                  (applicant_id, interviewer_id, schedule_date, start_time, end_time, notes, meeting_link, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $applicant_id,
            $interviewer_id,
            $schedule_date,
            $start_time,
            $end_time,
            $notes,
            $meeting_link
        ]);

        // Send email notification
        $email = new Email();
        $query = "SELECT 
                    a.id,
                    CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                    u.email
                  FROM applicants a
                  JOIN users u ON a.user_id = u.id
                  WHERE a.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$applicant_id]);
        $applicant = $stmt->fetch();
        $query = "SELECT 
                    u.id,
                    CONCAT(u.first_name, ' ', u.last_name) as interviewer_name
                  FROM users u
                  WHERE u.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$interviewer_id]);
        $interviewer = $stmt->fetch();
        $interview_data = [
            'schedule_date' => $schedule_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'interviewer_name' => $interviewer['interviewer_name'],
            'meeting_link' => $meeting_link,
            'notes' => $notes
        ];
        
        if (!$email->sendInterviewSchedule($applicant['email'], $applicant['applicant_name'], $interview_data)) {
            // Log email error but don't stop the process
            error_log("Failed to send interview schedule email: " . $email->getError());
        }

        // Update applicant status
        $query = "UPDATE applicants SET progress_status = 'interview_pending' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$applicant_id]);

        // Send notification to applicant
        $query = "INSERT INTO notifications (user_id, title, message, type)
                  SELECT 
                    a.user_id,
                    'Interview Scheduled',
                    CONCAT('Your interview has been scheduled for ', 
                           DATE_FORMAT(?, '%M %d, %Y'), ' at ',
                           TIME_FORMAT(?, '%h:%i %p')),
                    'interview'
                  FROM applicants a
                  WHERE a.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $schedule_date,
            $start_time,
            $applicant_id
        ]);

        $success = 'Interview has been successfully scheduled.';
        
        // Refresh the applicants list
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $applicants = $stmt->fetchAll();
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Schedule Interview';
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
                        <div>
                            <h1 class="h2"><?php echo $page_title; ?></h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="interview_list.php">Interview Schedule</a></li>
                                    <li class="breadcrumb-item active">Schedule New Interview</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class='bx bx-calendar-plus'></i> Schedule New Interview
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" id="scheduleForm" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="applicant_id" class="form-label">
                                            <i class='bx bx-user'></i> Select Applicant
                                        </label>
                                        <select class="form-select" id="applicant_id" name="applicant_id" required>
                                            <option value="">Choose applicant...</option>
                                            <?php foreach ($applicants as $applicant): ?>
                                                <option value="<?php echo $applicant['id']; ?>">
                                                    <?php echo htmlspecialchars($applicant['applicant_name']); ?> - 
                                                    <?php echo htmlspecialchars($applicant['email']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select an applicant.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="interviewer_id" class="form-label">
                                            <i class='bx bx-user-voice'></i> Select Interviewer
                                        </label>
                                        <select class="form-select" id="interviewer_id" name="interviewer_id" required>
                                            <option value="">Choose interviewer...</option>
                                            <?php foreach ($interviewers as $interviewer): ?>
                                                <option value="<?php echo $interviewer['id']; ?>">
                                                    <?php echo htmlspecialchars($interviewer['interviewer_name']); ?> 
                                                    (<?php echo ucfirst($interviewer['role']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select an interviewer.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="schedule_date" class="form-label">
                                            <i class='bx bx-calendar'></i> Interview Date
                                        </label>
                                        <input type="date" class="form-control" id="schedule_date" name="schedule_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">Please select a valid date.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="start_time" class="form-label">
                                            <i class='bx bx-time'></i> Start Time
                                        </label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                                        <div class="invalid-feedback">Please select a start time.</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="end_time" class="form-label">
                                            <i class='bx bx-time-five'></i> End Time
                                        </label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                                        <div class="invalid-feedback">Please select an end time.</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="meeting_link" class="form-label">
                                            <i class='bx bx-video'></i> Meeting Link
                                        </label>
                                        <input type="url" class="form-control" id="meeting_link" name="meeting_link" 
                                               placeholder="https://meet.google.com/..." required>
                                        <div class="invalid-feedback">Please enter a valid meeting link.</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="notes" class="form-label">
                                            <i class='bx bx-note'></i> Additional Notes
                                        </label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Any special instructions or notes for the interview..."></textarea>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class='bx bx-save'></i> Schedule Interview
                                        </button>
                                        <a href="interview_list.php" class="btn btn-secondary">
                                            <i class='bx bx-arrow-back'></i> Back to List
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class='bx bx-info-circle'></i> Interview Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class='bx bx-bulb'></i> Important Notes
                                </h6>
                                <ul class="mb-0">
                                    <li>Schedule interviews during business hours (9 AM - 5 PM)</li>
                                    <li>Allow at least 1-hour duration for each interview</li>
                                    <li>Check interviewer availability before scheduling</li>
                                    <li>Provide clear meeting link and instructions</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class='bx bx-error'></i> Before Scheduling
                                </h6>
                                <ul class="mb-0">
                                    <li>Verify applicant's contact information</li>
                                    <li>Ensure the meeting platform is accessible</li>
                                    <li>Consider time zones if applicable</li>
                                    <li>Have backup contact methods ready</li>
                                </ul>
                            </div>

                            <div class="mt-4">
                                <h6><i class='bx bx-link'></i> Recommended Platforms</h6>
                                <ul class="list-unstyled">
                                    <li><i class='bx bxl-google'></i> Google Meet</li>
                                    <li><i class='bx bxl-zoom'></i> Zoom</li>
                                    <li><i class='bx bxl-microsoft-teams'></i> Microsoft Teams</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('scheduleForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Time validation
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    const validateTime = function() {
        if (startTime.value && endTime.value) {
            if (endTime.value <= startTime.value) {
                endTime.setCustomValidity('End time must be after start time');
            } else {
                endTime.setCustomValidity('');
            }
        }
    };
    startTime.addEventListener('change', validateTime);
    endTime.addEventListener('change', validateTime);

    // Date validation
    const scheduleDate = document.getElementById('schedule_date');
    scheduleDate.addEventListener('change', function() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(this.value);
        if (selectedDate < today) {
            this.setCustomValidity('Please select a future date');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php admin_footer(); ?>
