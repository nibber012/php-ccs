<?php
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('applicant');

$user = $auth->getCurrentUser();

// Get applicant's interview schedule
$database = Database::getInstance();;
$conn = $database->getConnection();

$query = "SELECT i.*, a.first_name, a.last_name, a.preferred_course
          FROM interview_schedules i
          JOIN applicants a ON i.applicant_id = a.id
          WHERE a.user_id = ?
          ORDER BY i.schedule_date DESC
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$user['id']]);
$interview = $stmt->fetch(PDO::FETCH_ASSOC);

get_header('Interview Schedule');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid px-4 py-4" style="margin-top: 60px;">
        <h1 class="h3 mb-4">Interview Schedule</h1>
        
        <?php if (!$interview): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No interview has been scheduled yet. Please wait for the admin to schedule your interview.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Your Interview Details</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Date:</strong></p>
                            <p class="h5 mb-3"><?php echo date('F j, Y', strtotime($interview['schedule_date'])); ?></p>
                            
                            <p class="mb-1"><strong>Time:</strong></p>
                            <p class="h5 mb-3"><?php echo date('g:i A', strtotime($interview['schedule_time'])); ?></p>
                            
                            <p class="mb-1"><strong>Status:</strong></p>
                            <p class="h5 mb-3">
                                <?php if ($interview['status'] === 'scheduled'): ?>
                                    <span class="badge bg-primary">Scheduled</span>
                                <?php elseif ($interview['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($interview['status'] === 'cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Location:</strong></p>
                            <p class="h5 mb-3"><?php echo htmlspecialchars($interview['location']); ?></p>
                            
                            <p class="mb-1"><strong>Interviewer:</strong></p>
                            <p class="h5 mb-3"><?php echo htmlspecialchars($interview['interviewer_name']); ?></p>
                            
                            <?php if (!empty($interview['notes'])): ?>
                                <p class="mb-1"><strong>Additional Notes:</strong></p>
                                <p class="mb-3"><?php echo nl2br(htmlspecialchars($interview['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($interview['status'] === 'scheduled'): ?>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Please arrive at least 15 minutes before your scheduled interview time.
                            If you need to reschedule, please contact the CCS office immediately.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
