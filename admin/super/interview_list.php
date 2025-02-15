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
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Get filter parameters
    $status = $_GET['status'] ?? 'all';
    $date = $_GET['date'] ?? '';
    $search = $_GET['search'] ?? '';

    // Build query conditions
    $conditions = [];
    $params = [];

    if ($status !== 'all') {
        $conditions[] = "i.status = ?";
        $params[] = $status;
    }

    if ($date) {
        $conditions[] = "i.schedule_date = ?";
        $params[] = $date;
    }

    if ($search) {
        $conditions[] = "(CONCAT(a.first_name, ' ', a.last_name) LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Get interview schedules
    $query = "SELECT 
                i.*,
                CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                a.contact_number as applicant_contact,
                u.email as applicant_email,
                CONCAT(u2.first_name, ' ', u2.last_name) as interviewer_name,
                u2.role as interviewer_role
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              JOIN users u ON a.user_id = u.id
              JOIN users u2 ON i.interviewer_id = u2.id
              $where_clause
              ORDER BY i.schedule_date ASC, i.start_time ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $interviews = $stmt->fetchAll();

    // Handle status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $interview_id = $_POST['interview_id'] ?? '';
        $new_status = $_POST['status'] ?? '';
        $cancel_reason = $_POST['cancel_reason'] ?? '';

        if (!$interview_id || !$new_status) {
            throw new Exception('Invalid request.');
        }

        // Start transaction
        $conn->beginTransaction();

        try {
            // Update interview status
            $query = "UPDATE interview_schedules SET status = ?, notes = CONCAT(COALESCE(notes, ''), ?\n) WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $new_status,
                $new_status === 'cancelled' ? "Cancellation reason: $cancel_reason" : '',
                $interview_id
            ]);

            // If cancelled, update applicant status back to part2_completed
            if ($new_status === 'cancelled') {
                $query = "UPDATE applicants a
                         JOIN interview_schedules i ON a.id = i.applicant_id
                         SET a.progress_status = 'part2_completed'
                         WHERE i.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$interview_id]);

                // Send cancellation email
                $email = new Email();
                $query = "SELECT 
                            i.*,
                            CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                            u.email,
                            CONCAT(u2.first_name, ' ', u2.last_name) as interviewer_name
                          FROM interview_schedules i
                          JOIN applicants a ON i.applicant_id = a.id
                          JOIN users u ON a.user_id = u.id
                          JOIN users u2 ON i.interviewer_id = u2.id
                          WHERE i.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$interview_id]);
                $interview_data = $stmt->fetch();
                $interview_data['cancel_reason'] = $cancel_reason;

                if (!$email->sendInterviewCancellation($interview_data['email'], $interview_data['applicant_name'], $interview_data)) {
                    // Log email error but don't stop the process
                    error_log("Failed to send interview cancellation email: " . $email->getError());
                }
            }

            // Send notification
            $query = "INSERT INTO notifications (user_id, title, message, type)
                      SELECT 
                        a.user_id,
                        CASE ? 
                            WHEN 'cancelled' THEN 'Interview Cancelled'
                            WHEN 'completed' THEN 'Interview Completed'
                            ELSE 'Interview Status Updated'
                        END,
                        CASE ? 
                            WHEN 'cancelled' THEN CONCAT('Your interview scheduled for ', 
                                DATE_FORMAT(i.schedule_date, '%M %d, %Y'), ' has been cancelled. Reason: ', ?)
                            WHEN 'completed' THEN 'Your interview has been marked as completed.'
                            ELSE 'Your interview status has been updated.'
                        END,
                        'interview'
                      FROM interview_schedules i
                      JOIN applicants a ON i.applicant_id = a.id
                      WHERE i.id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $new_status,
                $new_status,
                $cancel_reason,
                $interview_id
            ]);

            $conn->commit();
            $success = 'Interview status has been updated successfully.';

            // Refresh interview list
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $interviews = $stmt->fetchAll();

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Interview Schedule';
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
                                    <li class="breadcrumb-item active">Interview Schedule</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="interview_results.php" class="btn btn-sm btn-outline-primary me-2">
                                <i class='bx bx-clipboard'></i> View Results
                            </a>
                            <a href="schedule_interview.php" class="btn btn-sm btn-primary">
                                <i class='bx bx-plus'></i> Schedule New Interview
                            </a>
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

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">
                                <i class='bx bx-search'></i> Search
                            </label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by applicant or interviewer name">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">
                                <i class='bx bx-filter'></i> Status
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="all">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">
                                <i class='bx bx-calendar'></i> Date
                            </label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?php echo htmlspecialchars($date); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class='bx bx-filter-alt'></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Interview List -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class='bx bx-list-ul'></i> Interview Schedule List
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($interviews)): ?>
                        <div class="text-center py-5">
                            <i class='bx bx-calendar-x fs-1 text-muted'></i>
                            <p class="text-muted mt-2">No interviews found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Applicant</th>
                                        <th>Interviewer</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($interviews as $interview): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo date('F d, Y', strtotime($interview['schedule_date'])); ?></div>
                                                <small class="text-muted">
                                                    <?php echo date('h:i A', strtotime($interview['start_time'])) . ' - ' . 
                                                              date('h:i A', strtotime($interview['end_time'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($interview['applicant_name']); ?></div>
                                                <small class="text-muted">
                                                    <i class='bx bx-phone'></i> <?php echo htmlspecialchars($interview['applicant_contact']); ?><br>
                                                    <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($interview['applicant_email']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($interview['interviewer_name']); ?></div>
                                                <small class="text-muted text-capitalize">
                                                    <i class='bx bx-user'></i> <?php echo str_replace('_', ' ', $interview['interviewer_role']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = match($interview['status']) {
                                                    'pending' => 'bg-warning',
                                                    'completed' => 'bg-success',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($interview['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                            data-bs-toggle="dropdown">
                                                        <i class='bx bx-dots-vertical-rounded'></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="view_interview.php?id=<?php echo $interview['id']; ?>">
                                                                <i class='bx bx-show'></i> View Details
                                                            </a>
                                                        </li>
                                                        <?php if ($interview['status'] === 'pending'): ?>
                                                            <li>
                                                                <form method="post" class="d-inline" 
                                                                      onsubmit="return confirm('Are you sure you want to mark this interview as completed?');">
                                                                    <input type="hidden" name="interview_id" value="<?php echo $interview['id']; ?>">
                                                                    <input type="hidden" name="status" value="completed">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class='bx bx-check'></i> Mark as Completed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <button type="button" class="dropdown-item text-danger"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#cancelModal"
                                                                        data-interview-id="<?php echo $interview['id']; ?>">
                                                                    <i class='bx bx-x'></i> Cancel Interview
                                                                </button>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Interview Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="interview_id" id="cancelInterviewId">
                    <input type="hidden" name="status" value="cancelled">
                    
                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label">Reason for Cancellation</label>
                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3" required></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class='bx bx-info-circle'></i> 
                        Cancelling the interview will:
                        <ul class="mb-0">
                            <li>Send a notification to the applicant</li>
                            <li>Reset the applicant's status to allow rescheduling</li>
                            <li>Remove this slot from the interviewer's schedule</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle cancel modal
    const cancelModal = document.getElementById('cancelModal');
    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const interviewId = button.getAttribute('data-interview-id');
            document.getElementById('cancelInterviewId').value = interviewId;
        });
    }
});
</script>

<?php admin_footer(); ?>
