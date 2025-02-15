<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Get exam ID from URL
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if (!$exam_id) {
    header('Location: list_exams.php');
    exit;
}

try {
    // Fetch exam details
    $stmt = $db->query(
        "SELECT * FROM exams WHERE id = ?",
        [$exam_id]
    );
    $exam = $stmt->fetch();

    if (!$exam) {
        throw new Exception('Exam not found.');
    }

    // Get statistics
    $stats = $db->query(
        "SELECT 
            COUNT(*) as total_attempts,
            COUNT(DISTINCT user_id) as unique_takers,
            AVG(score) as average_score,
            MIN(score) as lowest_score,
            MAX(score) as highest_score,
            SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
         FROM exam_results 
         WHERE exam_id = ?",
        [$exam_id]
    )->fetch();

    // Fetch results with user details
    $stmt = $db->query(
        "SELECT er.*, 
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.email, u.program,
                COUNT(ea.id) as total_questions,
                SUM(CASE WHEN ea.answer = q.correct_answer THEN 1 ELSE 0 END) as correct_answers
         FROM exam_results er
         JOIN users u ON er.user_id = u.id
         LEFT JOIN exam_answers ea ON er.id = ea.result_id
         LEFT JOIN questions q ON ea.question_id = q.id
         WHERE er.exam_id = ?
         GROUP BY er.id
         ORDER BY er.created_at DESC",
        [$exam_id]
    );
    $results = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error in exam_results.php: " . $e->getMessage());
    $error = "An error occurred while fetching exam results.";
}

admin_header('Exam Results');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Exam Results</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="list_exams.php">Exams</a></li>
                        <li class="breadcrumb-item active">Results</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Exam Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title"><?php echo htmlspecialchars($exam['exam_title']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($exam['description']); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="preview_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-primary">
                                <i class="bx bx-show"></i> Preview Exam
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Total Attempts</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['total_attempts']; ?></h2>
                            <small class="text-muted">
                                <?php echo $stats['unique_takers']; ?> unique takers
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Average Score</h6>
                            <h2 class="card-title mb-0">
                                <?php echo number_format($stats['average_score'], 1); ?>%
                            </h2>
                            <small class="text-muted">
                                Range: <?php echo floor($stats['lowest_score']); ?>% - <?php echo ceil($stats['highest_score']); ?>%
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Pass Rate</h6>
                            <h2 class="card-title mb-0">
                                <?php 
                                    $pass_rate = $stats['total_attempts'] > 0 
                                        ? ($stats['passed_count'] / $stats['total_attempts']) * 100 
                                        : 0;
                                    echo number_format($pass_rate, 1);
                                ?>%
                            </h2>
                            <small class="text-muted">
                                <?php echo $stats['passed_count']; ?> passed, <?php echo $stats['failed_count']; ?> failed
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Passing Score</h6>
                            <h2 class="card-title mb-0"><?php echo $exam['passing_score']; ?>%</h2>
                            <small class="text-muted">Required to pass</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Program</th>
                                    <th>Score</th>
                                    <th>Questions</th>
                                    <th>Time Taken</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($results)): ?>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td>
                                                <div><?php echo htmlspecialchars($result['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($result['email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($result['program']); ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo $result['score']; ?>%</div>
                                                <small class="text-muted">
                                                    <?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?> correct
                                                </small>
                                            </td>
                                            <td><?php echo $result['total_questions']; ?></td>
                                            <td><?php echo $result['time_taken']; ?> mins</td>
                                            <td>
                                                <span class="badge bg-<?php echo $result['status'] === 'passed' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($result['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($result['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="applicant_results.php?id=<?php echo $result['user_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View All Results">
                                                        <i class="bx bx-bar-chart-alt-2"></i>
                                                    </a>
                                                    <a href="view_applicant.php?id=<?php echo $result['user_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info"
                                                       title="View Profile">
                                                        <i class="bx bx-user"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No results found for this exam</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

.table > :not(caption) > * > * {
    padding: 0.75rem;
}

.btn-group {
    gap: 0.25rem;
}

@media print {
    .sidebar-wrapper, .btn-toolbar, form {
        display: none !important;
    }
    
    .col-md-9 {
        width: 100% !important;
        margin: 0 !important;
    }
}
</style>

<?php admin_footer(); ?>
