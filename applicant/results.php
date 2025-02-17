<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../includes/layout.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();;

try {
    // Get exam results with exam details
    $stmt = $db->query(
        "SELECT er.*, e.title as exam_title, e.description as exam_description, 
                e.duration_minutes, DATE_FORMAT(er.created_at, '%M %d, %Y %h:%i %p') as completion_date
         FROM exam_results er
         JOIN exams e ON er.exam_id = e.id
         JOIN applicants a ON er.applicant_id = a.id  -- ✅ Ensure it matches applicants table
         WHERE a.user_id = ?  -- ✅ Use applicants.user_id to filter correctly
         ORDER BY er.created_at DESC",
        [$user_id]
    );
    
    $results = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Results Error: " . $e->getMessage());
    $results = [];
}

get_header('My Results');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php get_sidebar('applicant'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Exam Results</h1>
            </div>

            <?php if (empty($results)): ?>
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    You haven't taken any exams yet. Visit the <a href="exams.php" class="alert-link">Exams page</a> to start an exam.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($results as $result): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($result['exam_title']); ?></h5>
                                    
                                    <div class="score-circle mb-3 <?php echo getScoreClass($result['score']); ?>">
                                        <?php echo number_format($result['score'], 1); ?>%
                                    </div>
                                    
                                    <div class="exam-details">
                                        <p class="text-muted mb-2">
                                            <i class="bx bx-time"></i>
                                            Completion Time: <?php echo number_format($result['completion_time'], 1); ?> minutes
                                        </p>
                                        <p class="text-muted mb-2">
                                            <i class="bx bx-calendar"></i>
                                            Completed: <?php echo $result['completion_date']; ?>
                                        </p>
                                    </div>

                                    <?php if (!empty($result['exam_description'])): ?>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars($result['exam_description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Performance Summary</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Exam</th>
                                                <th>Score</th>
                                                <th>Duration</th>
                                                <th>Completion Time</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $result): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo getScoreBadgeClass($result['score']); ?>">
                                                            <?php echo number_format($result['score'], 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td><?php echo $result['duration_minutes']; ?> min</td>
                                                    <td><?php echo number_format($result['completion_time'], 1); ?> min</td>
                                                    <td><?php echo $result['completion_date']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.score-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 auto;
    color: white;
}

.score-excellent {
    background-color: #27ae60;
}

.score-good {
    background-color: #2980b9;
}

.score-average {
    background-color: #f39c12;
}

.score-poor {
    background-color: #c0392b;
}

.exam-details {
    margin: 1rem 0;
}

.exam-details i {
    margin-right: 0.5rem;
}

.badge {
    padding: 0.5rem 1rem;
}

.badge-excellent {
    background-color: #27ae60;
}

.badge-good {
    background-color: #2980b9;
}

.badge-average {
    background-color: #f39c12;
}

.badge-poor {
    background-color: #c0392b;
}
</style>

<?php
function getScoreClass($score) {
    if ($score >= 90) return 'score-excellent';
    if ($score >= 80) return 'score-good';
    if ($score >= 70) return 'score-average';
    return 'score-poor';
}

function getScoreBadgeClass($score) {
    if ($score >= 90) return 'badge-excellent';
    if ($score >= 80) return 'badge-good';
    if ($score >= 70) return 'badge-average';
    return 'badge-poor';
}

get_footer();
?>
