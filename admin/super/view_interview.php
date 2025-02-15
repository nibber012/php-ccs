<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Define scoring categories
$categories = [
    'technical_knowledge' => [
        'name' => 'Technical Knowledge',
        'max_score' => 30,
        'criteria' => [
            'Understanding of programming concepts',
            'Problem-solving skills',
            'Knowledge of relevant technologies'
        ]
    ],
    'communication' => [
        'name' => 'Communication Skills',
        'max_score' => 20,
        'criteria' => [
            'Clarity of expression',
            'Active listening',
            'Professional demeanor'
        ]
    ],
    'analytical_skills' => [
        'name' => 'Analytical Skills',
        'max_score' => 20,
        'criteria' => [
            'Problem analysis',
            'Logical reasoning',
            'Solution design'
        ]
    ],
    'attitude' => [
        'name' => 'Attitude & Personality',
        'max_score' => 15,
        'criteria' => [
            'Enthusiasm and motivation',
            'Team player mindset',
            'Learning attitude'
        ]
    ],
    'experience' => [
        'name' => 'Experience & Projects',
        'max_score' => 15,
        'criteria' => [
            'Relevant project experience',
            'Academic achievements',
            'Extra-curricular activities'
        ]
    ]
];

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    $interview_id = $_GET['id'] ?? null;

    if (!$interview_id) {
        throw new Exception('Interview ID is required.');
    }

    // Get interview details
    $query = "SELECT 
                i.*,
                CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                u.email as applicant_email,
                a.contact_number as applicant_contact,
                CONCAT(u2.first_name, ' ', u2.last_name) as interviewer_name,
                u2.role as interviewer_role
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              JOIN users u ON a.user_id = u.id
              JOIN users u2 ON i.interviewer_id = u2.id
              WHERE i.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$interview_id]);
    $interview = $stmt->fetch();

    if (!$interview) {
        throw new Exception('Interview not found.');
    }

    // Get existing scores
    $query = "SELECT * FROM interview_scores WHERE interview_schedule_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$interview_id]);
    $existing_scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $interview['status'] === 'scheduled') {
        $scores = $_POST['scores'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        $total_score = 0;

        // Start transaction
        $conn->beginTransaction();

        try {
            // Delete existing scores
            $query = "DELETE FROM interview_scores WHERE interview_schedule_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$interview_id]);

            // Insert new scores
            $query = "INSERT INTO interview_scores (interview_schedule_id, category, score, remarks) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);

            foreach ($categories as $category => $info) {
                $score = min((int)($scores[$category] ?? 0), $info['max_score']);
                $total_score += $score;

                $stmt->execute([
                    $interview_id,
                    $category,
                    $score,
                    $remarks[$category] ?? ''
                ]);
            }

            // Update interview status
            $query = "UPDATE interview_schedules SET status = 'completed' WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$interview_id]);

            // Update applicant status
            $passing_score = array_sum(array_column($categories, 'max_score')) * 0.7; // 70% passing score
            $new_status = $total_score >= $passing_score ? 'passed' : 'failed';

            $query = "UPDATE applicants a
                     JOIN interview_schedules i ON a.id = i.applicant_id
                     SET a.progress_status = ?
                     WHERE i.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$new_status, $interview_id]);

            // Send notification
            $query = "INSERT INTO notifications (user_id, title, message, type)
                      SELECT 
                        a.user_id,
                        'Interview Results Available',
                        'Your interview results have been processed. Please check your status.',
                        'interview'
                      FROM interview_schedules i
                      JOIN applicants a ON i.applicant_id = a.id
                      WHERE i.id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$interview_id]);

            $conn->commit();
            $success = 'Interview scores have been saved successfully.';

            // Refresh interview details
            $stmt = $conn->prepare($query);
            $stmt->execute([$interview_id]);
            $interview = $stmt->fetch();

            // Refresh scores
            $stmt = $conn->prepare("SELECT * FROM interview_scores WHERE interview_schedule_id = ?");
            $stmt->execute([$interview_id]);
            $existing_scores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('View Interview');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Interview Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="interview_list.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

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

    <?php if (isset($interview)): ?>
        <div class="row">
            <!-- Interview Details -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Interview Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($interview['schedule_date'])); ?></p>
                                <p><strong>Time:</strong> 
                                    <?php echo date('h:i A', strtotime($interview['start_time'])) . ' - ' . 
                                             date('h:i A', strtotime($interview['end_time'])); ?>
                                </p>
                                <p><strong>Status:</strong> 
                                    <span class="badge <?php 
                                        echo match($interview['status']) {
                                            'scheduled' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($interview['status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Meeting Link:</strong> 
                                    <a href="<?php echo htmlspecialchars($interview['meeting_link']); ?>" 
                                       target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-camera-video"></i> Join Meeting
                                    </a>
                                </p>
                                <?php if ($interview['notes']): ?>
                                    <p><strong>Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($interview['notes'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Applicant Information</h5>
                    </div>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($interview['applicant_name']); ?></h6>
                        <p class="mb-0">
                            <i class="bi bi-envelope"></i> 
                            <?php echo htmlspecialchars($interview['applicant_email']); ?>
                        </p>
                        <p class="mb-0">
                            <i class="bi bi-telephone"></i> 
                            <?php echo htmlspecialchars($interview['applicant_contact']); ?>
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Interviewer</h5>
                    </div>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($interview['interviewer_name']); ?></h6>
                        <span class="badge bg-secondary">
                            <?php echo strtoupper($interview['interviewer_role']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Scoring Form -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Interview Evaluation</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($interview['status'] === 'scheduled'): ?>
                            <form method="post">
                                <?php foreach ($categories as $category => $info): ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0"><?php echo $info['name']; ?></h6>
                                            <small class="text-muted">Max Score: <?php echo $info['max_score']; ?></small>
                                        </div>

                                        <div class="criteria-list small text-muted mb-2">
                                            <strong>Criteria:</strong>
                                            <ul class="mb-2">
                                                <?php foreach ($info['criteria'] as $criterion): ?>
                                                    <li><?php echo $criterion; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="score_<?php echo $category; ?>" class="form-label">Score</label>
                                                <input type="number" class="form-control" 
                                                       id="score_<?php echo $category; ?>"
                                                       name="scores[<?php echo $category; ?>]"
                                                       min="0" max="<?php echo $info['max_score']; ?>"
                                                       value="<?php echo $existing_scores[$category] ?? 0; ?>"
                                                       required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="remarks_<?php echo $category; ?>" class="form-label">Remarks</label>
                                                <textarea class="form-control" 
                                                          id="remarks_<?php echo $category; ?>"
                                                          name="remarks[<?php echo $category; ?>]"
                                                          rows="2"><?php echo $existing_scores[$category] ?? ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        Submit Evaluation
                                    </button>
                                </div>
                            </form>
                        <?php elseif ($interview['status'] === 'completed'): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Interview Results</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h3 class="h5">Total Score: 
                                                <span class="badge <?php echo $interview['total_score'] >= 70 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $interview['total_score']; ?>/100
                                                </span>
                                            </h3>
                                            <h3 class="h5">Status: 
                                                <span class="badge <?php echo $interview['interview_status'] === 'passed' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($interview['interview_status']); ?>
                                                </span>
                                            </h3>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Score</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query = "SELECT * FROM interview_scores WHERE interview_id = ? ORDER BY category";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([$interview['id']]);
                                                $scores = $stmt->fetchAll();

                                                foreach ($scores as $score):
                                                ?>
                                                    <tr>
                                                        <td><?php echo ucwords(str_replace('_', ' ', $score['category'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo $score['score']; ?>/20
                                                            </span>
                                                        </td>
                                                        <td><?php echo nl2br(htmlspecialchars($score['remarks'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to submit this evaluation? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php
admin_footer();
?>
