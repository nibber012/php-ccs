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

// Get interview ID from URL
$interview_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // If form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->beginTransaction();

        try {
            // Validate scores
            $categories = ['technical_skills', 'communication', 'problem_solving', 'cultural_fit', 'overall_impression'];
            $total_score = 0;
            $scores = [];

            foreach ($categories as $category) {
                $score = isset($_POST['scores'][$category]) ? (int)$_POST['scores'][$category] : 0;
                $remarks = isset($_POST['remarks'][$category]) ? trim($_POST['remarks'][$category]) : '';

                if ($score < 0 || $score > 20) {
                    throw new Exception("Score for $category must be between 0 and 20");
                }

                $total_score += $score;
                $scores[$category] = [
                    'score' => $score,
                    'remarks' => $remarks
                ];

                // Insert score
                $query = "INSERT INTO interview_scores (interview_id, category, score, remarks) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$interview_id, $category, $score, $remarks]);
            }

            // Update interview status
            $interview_status = $total_score >= 70 ? 'passed' : 'failed';
            $query = "UPDATE interview_schedules SET 
                        status = 'completed',
                        interview_status = ?,
                        total_score = ?
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$interview_status, $total_score, $interview_id]);

            // Get interview and applicant details for email
            $query = "SELECT 
                        i.*,
                        a.first_name,
                        a.last_name,
                        u.email,
                        CONCAT(ui.first_name, ' ', ui.last_name) as interviewer_name
                     FROM interview_schedules i
                     JOIN applicants a ON i.applicant_id = a.id
                     JOIN users u ON a.user_id = u.id
                     JOIN users ui ON i.interviewer_id = ui.id
                     WHERE i.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$interview_id]);
            $interview = $stmt->fetch(PDO::FETCH_ASSOC);

            // Send email notification
            $email = new Email();
            $email_data = array_merge($interview, ['scores' => $scores]);
            $email->sendInterviewResult(
                $interview['email'],
                $interview['first_name'] . ' ' . $interview['last_name'],
                $email_data
            );

            $conn->commit();
            $success = "Interview results have been recorded successfully.";

        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    // Get interview details
    $query = "SELECT 
                i.*,
                a.first_name as applicant_first_name,
                a.last_name as applicant_last_name,
                CONCAT(u.first_name, ' ', u.last_name) as interviewer_name
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              JOIN users u ON i.interviewer_id = u.id
              WHERE i.id = ? AND i.status = 'scheduled'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$interview_id]);
    $interview = $stmt->fetch();

    if (!$interview) {
        throw new Exception("Interview not found or already completed");
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('Record Interview Result');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Record Interview Result</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <br>
            <a href="interview_list.php" class="btn btn-primary mt-2">Back to Interview List</a>
        </div>
    <?php else: ?>

        <?php if ($interview): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Interview Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Applicant:</strong> <?php echo htmlspecialchars($interview['applicant_first_name'] . ' ' . $interview['applicant_last_name']); ?></p>
                            <p><strong>Interviewer:</strong> <?php echo htmlspecialchars($interview['interviewer_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($interview['schedule_date'])); ?></p>
                            <p><strong>Time:</strong> 
                                <?php echo date('h:i A', strtotime($interview['start_time'])) . ' - ' . 
                                         date('h:i A', strtotime($interview['end_time'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" class="needs-validation" novalidate>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Evaluation Form</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Score each category from 0 to 20. A total score of 70 or higher is required to pass.
                        </div>

                        <?php
                        $categories = [
                            'technical_skills' => 'Technical Skills',
                            'communication' => 'Communication',
                            'problem_solving' => 'Problem Solving',
                            'cultural_fit' => 'Cultural Fit',
                            'overall_impression' => 'Overall Impression'
                        ];

                        foreach ($categories as $key => $label):
                        ?>
                            <div class="mb-4">
                                <label class="form-label">
                                    <strong><?php echo $label; ?></strong>
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="scores[<?php echo $key; ?>]" 
                                                   min="0" 
                                                   max="20" 
                                                   required>
                                            <span class="input-group-text">/20</span>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <textarea class="form-control" 
                                                  name="remarks[<?php echo $key; ?>]" 
                                                  rows="2" 
                                                  placeholder="Enter remarks (optional)"></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-end">
                            <a href="interview_list.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit Results</button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php
admin_footer();
?>
