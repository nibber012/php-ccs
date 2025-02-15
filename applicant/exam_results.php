<?php
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('applicant');

$user = $auth->getCurrentUser();

// Get applicant's exam results
$database = Database::getInstance();;
$conn = $database->getConnection();

$query = "SELECT er.*, e.title, e.part, e.passing_score
          FROM exam_results er 
          JOIN exams e ON er.exam_id = e.id 
          WHERE er.applicant_id = (SELECT id FROM applicants WHERE user_id = ?)
          ORDER BY er.completed_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$user['id']]);
$exam_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

get_header('Exam Results');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid px-4 py-4" style="margin-top: 60px;">
        <h1 class="h3 mb-4">My Exam Results</h1>
        
        <?php if (empty($exam_results)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                You haven't taken any exams yet.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
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
                                        <td><?php echo htmlspecialchars($result['title']); ?></td>
                                        <td><?php echo htmlspecialchars($result['part']); ?></td>
                                        <td>
                                            <?php 
                                            echo $result['score'] . '/' . $result['total_points'];
                                            $percentage = ($result['score'] / $result['total_points']) * 100;
                                            echo ' (' . number_format($percentage, 1) . '%)';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($result['status'] === 'passed'): ?>
                                                <span class="badge bg-success">Passed</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($result['completed_at'])); ?></td>
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

<?php get_footer(); ?>
