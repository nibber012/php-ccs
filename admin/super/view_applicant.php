<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    $applicant_id = $_GET['id'] ?? null;

    if (!$applicant_id) {
        throw new Exception('Applicant ID is required.');
    }

    // Get applicant details
    $query = "SELECT 
                a.*,
                CONCAT(a.first_name, ' ', a.last_name) as full_name,
                u.email,
                u.created_at as registration_date
              FROM applicants a
              JOIN users u ON a.user_id = u.id
              WHERE a.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$applicant_id]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        throw new Exception('Applicant not found.');
    }

    // Get exam results
    $query = "SELECT 
                er.*,
                e.title as exam_title,
                e.type as exam_type,
                e.part as exam_part,
                e.passing_score,
                er.created_at,
                (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as total_questions,
                (SELECT COUNT(*) FROM applicant_answers aa 
                 WHERE aa.exam_id = e.id AND aa.applicant_id = ? AND aa.is_correct = 1) as correct_answers
              FROM exam_results er
              JOIN exams e ON er.exam_id = e.id
              WHERE er.applicant_id = ?
              ORDER BY e.part ASC, er.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$applicant['id'], $applicant['id']]);
    $exam_results = $stmt->fetchAll();

    // Get interview history
    $query = "SELECT 
                i.*,
                CONCAT(a.first_name, ' ', a.last_name) as interviewer_name,
                a.id as interviewer_id
              FROM interview_schedules i
              JOIN users u ON i.interviewer_id = u.id
              JOIN admins a ON u.id = a.user_id
              WHERE i.applicant_id = ?
              ORDER BY i.schedule_date DESC, i.start_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$applicant_id]);
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('View Applicant');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Applicant Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Profile
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($applicant)): ?>
        <div class="row">
            <!-- Applicant Information -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="display-1 text-primary mb-2">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($applicant['full_name']); ?></h4>
                            <span class="badge <?php 
                                echo match($applicant['progress_status']) {
                                    'registered' => 'bg-secondary',
                                    'part1_completed' => 'bg-info',
                                    'part2_completed' => 'bg-primary',
                                    'interview_pending' => 'bg-warning',
                                    'passed' => 'bg-success',
                                    'failed' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            ?>">
                                <?php echo ucwords(str_replace('_', ' ', $applicant['progress_status'])); ?>
                            </span>
                        </div>

                        <table class="table table-sm">
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Contact:</th>
                                <td><?php echo htmlspecialchars($applicant['contact_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Registered:</th>
                                <td><?php echo date('M d, Y', strtotime($applicant['registration_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?php echo htmlspecialchars($applicant['address']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Academic Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <?php if (isset($applicant['school'])): ?>
                            <tr>
                                <th>School:</th>
                                <td><?php echo htmlspecialchars($applicant['school']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (isset($applicant['course'])): ?>
                            <tr>
                                <th>Course:</th>
                                <td><?php echo htmlspecialchars($applicant['course']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (isset($applicant['year_level'])): ?>
                            <tr>
                                <th>Year Level:</th>
                                <td><?php echo htmlspecialchars($applicant['year_level']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (empty($applicant['school']) && empty($applicant['course']) && empty($applicant['year_level'])): ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted">No academic information available</td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Exam Results -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Exam Results</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($exam_results)): ?>
                            <p class="text-center text-muted my-3">No exam results found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Exam</th>
                                            <th>Part</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Completed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exam_results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                                <td><?php echo $result['exam_part']; ?></td>
                                                <td>
                                                    <?php 
                                                        echo $result['correct_answers'] . '/' . $result['total_questions'];
                                                        $percentage = ($result['correct_answers'] / $result['total_questions']) * 100;
                                                        echo ' (' . round($percentage, 1) . '%)';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $passed = $percentage >= $result['passing_score'];
                                                    ?>
                                                    <span class="badge <?php echo $passed ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $passed ? 'PASSED' : 'FAILED'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y h:i A', strtotime($result['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Interview History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Interview History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($interviews)): ?>
                            <p class="text-center text-muted my-3">No interviews found.</p>
                        <?php else: ?>
                            <div class="accordion" id="interviewAccordion">
                                <?php foreach ($interviews as $index => $interview): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#interview<?php echo $interview['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <span>
                                                        Interview on 
                                                        <?php echo date('F d, Y', strtotime($interview['schedule_date'])); ?>
                                                    </span>
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
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="interview<?php echo $interview['id']; ?>" 
                                             class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                             data-bs-parent="#interviewAccordion">
                                            <div class="accordion-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Interviewer:</strong>
                                                        <?php echo htmlspecialchars($interview['interviewer_name']); ?>
                                                        <span class="badge bg-secondary">
                                                            Admin
                                                        </span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Schedule:</strong>
                                                        <?php 
                                                            echo date('h:i A', strtotime($interview['start_time'])) . ' - ' . 
                                                                 date('h:i A', strtotime($interview['end_time']));
                                                        ?>
                                                    </div>
                                                </div>

                                                <?php if ($interview['status'] === 'completed' && $interview['score_details']): ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Category</th>
                                                                    <th>Score</th>
                                                                    <th>Remarks</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $scores = array_map(function($item) {
                                                                    list($category, $score, $remarks) = explode(':', $item);
                                                                    return [
                                                                        'category' => ucwords(str_replace('_', ' ', $category)),
                                                                        'score' => $score,
                                                                        'remarks' => $remarks
                                                                    ];
                                                                }, explode('|', $interview['score_details']));

                                                                foreach ($scores as $score):
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $score['category']; ?></td>
                                                                        <td><?php echo $score['score']; ?></td>
                                                                        <td><?php echo htmlspecialchars($score['remarks']); ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                                <tr class="table-active">
                                                                    <td><strong>Total Score</strong></td>
                                                                    <td colspan="2">
                                                                        <strong><?php echo $interview['total_score']; ?>/100</strong>
                                                                        <?php 
                                                                            $passed = $interview['total_score'] >= 70;
                                                                        ?>
                                                                        <span class="badge <?php echo $passed ? 'bg-success' : 'bg-danger'; ?>">
                                                                            <?php echo $passed ? 'PASSED' : 'FAILED'; ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($interview['notes']): ?>
                                                    <div class="mt-3">
                                                        <strong>Notes:</strong>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($interview['notes'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .btn-toolbar,
    .accordion-button::after {
        display: none !important;
    }
    
    .card {
        border: none !important;
    }
    
    .accordion-button {
        padding: 0 !important;
        margin-bottom: 1rem !important;
    }
    
    .accordion-button:not(.collapsed) {
        color: inherit !important;
        background-color: transparent !important;
        box-shadow: none !important;
    }
    
    .accordion-body {
        padding: 0 !important;
    }
}
</style>

<?php
admin_footer();
?>
