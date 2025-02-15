<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Get applicant ID from URL
$applicant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$applicant_id) {
    header('Location: manage_applicants.php');
    exit;
}

try {
    // Fetch applicant details
    $stmt = $db->query(
        "SELECT * FROM users WHERE id = ? AND role = 'applicant'",
        [$applicant_id]
    );
    $applicant = $stmt->fetch();

    if (!$applicant) {
        throw new Exception('Applicant not found.');
    }

    // Fetch exam results with detailed answers
    $stmt = $db->query(
        "SELECT er.*, e.exam_title, e.passing_score,
                ea.question_id, ea.answer as given_answer,
                q.question_text, q.correct_answer
         FROM exam_results er
         JOIN exams e ON er.exam_id = e.id
         LEFT JOIN exam_answers ea ON er.id = ea.result_id
         LEFT JOIN questions q ON ea.question_id = q.id
         WHERE er.user_id = ?
         ORDER BY er.created_at DESC, q.id ASC",
        [$applicant_id]
    );
    $results = $stmt->fetchAll();

    // Organize results by exam
    $exam_results = [];
    foreach ($results as $row) {
        $result_id = $row['id'];
        if (!isset($exam_results[$result_id])) {
            $exam_results[$result_id] = [
                'exam_title' => $row['exam_title'],
                'score' => $row['score'],
                'status' => $row['status'],
                'passing_score' => $row['passing_score'],
                'created_at' => $row['created_at'],
                'answers' => []
            ];
        }
        if ($row['question_id']) {
            $exam_results[$result_id]['answers'][] = [
                'question' => $row['question_text'],
                'given_answer' => $row['given_answer'],
                'correct_answer' => $row['correct_answer'],
                'is_correct' => $row['given_answer'] === $row['correct_answer']
            ];
        }
    }

} catch (Exception $e) {
    error_log("Error in applicant_results.php: " . $e->getMessage());
    $error = "An error occurred while fetching applicant results.";
}

admin_header('Applicant Results');
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
                        <li class="breadcrumb-item"><a href="manage_applicants.php">Manage Applicants</a></li>
                        <li class="breadcrumb-item active">Exam Results</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Applicant Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="card-title">Applicant Information</h5>
                            <p class="mb-1">
                                <strong>Name:</strong> 
                                <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Email:</strong> 
                                <?php echo htmlspecialchars($applicant['email']); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Program:</strong> 
                                <?php echo htmlspecialchars($applicant['program']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="view_applicant.php?id=<?php echo $applicant_id; ?>" class="btn btn-primary">
                                <i class="bx bx-user"></i> View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exam Results -->
            <?php if (empty($exam_results)): ?>
                <div class="alert alert-info" role="alert">
                    No exam results found for this applicant.
                </div>
            <?php else: ?>
                <?php foreach ($exam_results as $result): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($result['exam_title']); ?></h5>
                                <div>
                                    <span class="badge bg-<?php echo $result['status'] === 'passed' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($result['status']); ?>
                                    </span>
                                    <small class="text-muted ms-2">
                                        <?php echo date('M d, Y h:i A', strtotime($result['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <h6 class="mb-0">Score</h6>
                                            <h2 class="mb-0"><?php echo $result['score']; ?>%</h2>
                                        </div>
                                        <div class="border-start ps-3">
                                            <h6 class="mb-0">Passing Score</h6>
                                            <h2 class="mb-0"><?php echo $result['passing_score']; ?>%</h2>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($result['answers'])): ?>
                                <h6>Detailed Answers</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 50%">Question</th>
                                                <th>Given Answer</th>
                                                <th>Correct Answer</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($result['answers'] as $answer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($answer['question']); ?></td>
                                                    <td><?php echo htmlspecialchars($answer['given_answer']); ?></td>
                                                    <td><?php echo htmlspecialchars($answer['correct_answer']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $answer['is_correct'] ? 'success' : 'danger'; ?>">
                                                            <?php echo $answer['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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

@media print {
    .sidebar-wrapper {
        display: none !important;
    }
    
    .col-md-9 {
        width: 100% !important;
        margin: 0 !important;
    }
}
</style>

<?php admin_footer(); ?>
