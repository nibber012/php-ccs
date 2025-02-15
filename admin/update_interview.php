<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Get interview ID from URL
$interview_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$interview_id) {
    header('Location: interview_list.php');
    exit;
}

try {
    // Fetch interview details
    $stmt = $db->query(
        "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                u.email as applicant_email,
                u.contact_number,
                u.program
         FROM interviews i
         JOIN users u ON i.user_id = u.id
         WHERE i.id = ?",
        [$interview_id]
    );
    $interview = $stmt->fetch();

    if (!$interview) {
        throw new Exception('Interview not found.');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $reschedule_date = isset($_POST['reschedule_date']) ? trim($_POST['reschedule_date']) : '';
        $reschedule_time = isset($_POST['reschedule_time']) ? trim($_POST['reschedule_time']) : '';

        if (!$status) {
            throw new Exception('Please select a status.');
        }

        // Start transaction
        $db->beginTransaction();

        try {
            // If rescheduling
            if ($status === 'rescheduled' && $reschedule_date && $reschedule_time) {
                $new_schedule = date('Y-m-d H:i:s', strtotime("$reschedule_date $reschedule_time"));
                
                if (strtotime($new_schedule) <= time()) {
                    throw new Exception('Please select a future date and time for rescheduling.');
                }

                // Update schedule
                $db->query(
                    "UPDATE interviews 
                     SET schedule = ?, status = 'scheduled', notes = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$new_schedule, $notes, $interview_id]
                );
            } else {
                // Update status
                $db->query(
                    "UPDATE interviews 
                     SET status = ?, notes = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$status, $notes, $interview_id]
                );
            }

            // Add to interview history
            $db->query(
                "INSERT INTO interview_history (interview_id, status, notes, created_by)
                 VALUES (?, ?, ?, ?)",
                [$interview_id, $status, $notes, $auth->getCurrentUser()['id']]
            );

            $db->commit();
            $success = "Interview status has been updated successfully.";

            // Redirect to prevent form resubmission
            header('Location: interview_list.php?success=' . urlencode($success));
            exit;

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    // Fetch interview history
    $stmt = $db->query(
        "SELECT ih.*,
                CONCAT(u.first_name, ' ', u.last_name) as updated_by
         FROM interview_history ih
         JOIN users u ON ih.created_by = u.id
         WHERE ih.interview_id = ?
         ORDER BY ih.created_at DESC",
        [$interview_id]
    );
    $history = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error in update_interview.php: " . $e->getMessage());
    $error = $e->getMessage();
}

admin_header('Update Interview');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Update Interview</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="interview_list.php">Interviews</a></li>
                        <li class="breadcrumb-item active">Update Interview</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Interview Details -->
            <div class="row">
                <div class="col-md-8">
                    <!-- Update Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Update Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="">Select status...</option>
                                            <option value="completed" <?php echo $interview['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $interview['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="rescheduled">Reschedule</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a status.
                                        </div>
                                    </div>

                                    <div class="col-md-6 reschedule-fields d-none">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="reschedule_date" class="form-label">New Date</label>
                                                <input type="date" class="form-control" id="reschedule_date" 
                                                       name="reschedule_date" min="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="reschedule_time" class="form-label">New Time</label>
                                                <input type="time" class="form-control" id="reschedule_time" 
                                                       name="reschedule_time">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="notes" class="form-label">Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" required
                                                  placeholder="Add notes about the status change"></textarea>
                                        <div class="invalid-feedback">
                                            Please add notes about this update.
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-save"></i> Update Status
                                        </button>
                                        <a href="interview_list.php" class="btn btn-secondary">
                                            <i class="bx bx-x"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Interview History -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Interview History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($history)): ?>
                                <p class="text-muted">No history available.</p>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($history as $record): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">
                                                    Status changed to: <?php echo ucfirst($record['status']); ?>
                                                </h6>
                                                <p class="timeline-text"><?php echo htmlspecialchars($record['notes']); ?></p>
                                                <p class="timeline-date text-muted">
                                                    <small>
                                                        By <?php echo htmlspecialchars($record['updated_by']); ?> on
                                                        <?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?>
                                                    </small>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Applicant Details -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Applicant Details</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($interview['applicant_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Email:</strong><br>
                                <?php echo htmlspecialchars($interview['applicant_email']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Contact:</strong><br>
                                <?php echo htmlspecialchars($interview['contact_number']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Program:</strong><br>
                                <?php echo htmlspecialchars($interview['program']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Interview Schedule:</strong><br>
                                <?php echo date('F d, Y h:i A', strtotime($interview['schedule'])); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Current Status:</strong><br>
                                <span class="badge bg-<?php 
                                    echo $interview['status'] === 'completed' ? 'success' : 
                                        ($interview['status'] === 'scheduled' ? 'info' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($interview['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}

/* Timeline styles */
.timeline {
    position: relative;
    padding: 1rem 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 2px;
    height: 100%;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-left: 2.5rem;
    padding-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 2px solid #fff;
}

.timeline-content {
    padding: 0.5rem 0;
}

.timeline-title {
    margin-bottom: 0.5rem;
}

.timeline-date {
    margin-top: 0.5rem;
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

    // Show/hide reschedule fields
    const statusSelect = document.getElementById('status');
    const rescheduleFields = document.querySelector('.reschedule-fields');
    const rescheduleInputs = rescheduleFields.querySelectorAll('input');

    statusSelect.addEventListener('change', function() {
        if (this.value === 'rescheduled') {
            rescheduleFields.classList.remove('d-none');
            rescheduleInputs.forEach(input => input.required = true);
        } else {
            rescheduleFields.classList.add('d-none');
            rescheduleInputs.forEach(input => {
                input.required = false;
                input.value = '';
            });
        }
    });
});
</script>

<?php admin_footer(); ?>
