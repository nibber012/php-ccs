<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

$result_id = $_GET['id'] ?? null;

if (!$result_id) {
    header('Location: exam_results.php');
    exit();
}

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // Get result details
    $query = "SELECT 
                er.*,
                e.title as exam_title,
                e.description as exam_description,
                e.type as exam_type,
                e.part as exam_part,
                e.passing_score,
                e.duration_minutes,
                u.*,
                CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                u.email,
                u.contact_number
              FROM exam_results er
              JOIN exams e ON er.exam_id = e.id
              JOIN users u ON er.user_id = u.id
              WHERE er.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$result_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('Result not found');
    }

    // Get question details and answers
    $query = "SELECT 
                q.*,
                aa.answer as applicant_answer,
                aa.is_correct,
                aa.score
              FROM questions q
              LEFT JOIN applicant_answers aa ON q.id = aa.question_id 
                AND aa.applicant_id = ? AND aa.exam_id = ?
              WHERE q.exam_id = ?
              ORDER BY q.id ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$result['user_id'], $result['exam_id'], $result['exam_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate statistics
    $totalQuestions = count($questions);
    $answeredQuestions = 0;
    $correctAnswers = 0;
    $totalScore = 0;
    $maxScore = 0;

    foreach ($questions as $question) {
        if ($question['applicant_answer'] !== null) {
            $answeredQuestions++;
        }
        if ($question['is_correct']) {
            $correctAnswers++;
        }
        $totalScore += $question['score'] ?? 0;
        $maxScore += $question['points'];
    }

    $scorePercentage = ($totalScore / $maxScore) * 100;
    $passed = $scorePercentage >= $result['passing_score'];

} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}

$page_title = 'View Exam Result';
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
                                    <li class="breadcrumb-item"><a href="list_exams.php">Exams</a></li>
                                    <li class="breadcrumb-item"><a href="exam_results.php">Results</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">View Result</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class='bx bx-printer'></i> Print Result
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php else: ?>
                <!-- Result Overview -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-user'></i> Applicant Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="100">Name:</th>
                                            <td><?php echo htmlspecialchars($result['applicant_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Contact:</th>
                                            <td><?php echo htmlspecialchars($result['contact_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td><?php echo htmlspecialchars($result['email']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-test-tube'></i> Exam Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="100">Title:</th>
                                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Part:</th>
                                            <td>Part <?php echo htmlspecialchars($result['exam_part']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Type:</th>
                                            <td><?php echo ucfirst(htmlspecialchars($result['exam_type'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Duration:</th>
                                            <td><?php echo htmlspecialchars($result['duration_minutes']); ?> minutes</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class='bx bx-bar-chart-alt-2'></i> Result Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="display-4 fw-bold <?php echo $passed ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo number_format($scorePercentage, 1); ?>%
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge <?php echo $passed ? 'bg-success' : 'bg-danger'; ?> px-3 py-2">
                                            <?php echo $passed ? 'PASSED' : 'FAILED'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="140">Correct Answers:</th>
                                            <td>
                                                <?php echo $correctAnswers; ?>/<?php echo $totalQuestions; ?>
                                                (<?php echo number_format(($correctAnswers / $totalQuestions) * 100, 1); ?>%)
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Total Points:</th>
                                            <td>
                                                <?php echo $totalScore; ?>/<?php echo $maxScore; ?>
                                                (<?php echo number_format(($totalScore / $maxScore) * 100, 1); ?>%)
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Passing Score:</th>
                                            <td><?php echo $result['passing_score']; ?>%</td>
                                        </tr>
                                        <tr>
                                            <th>Completion Time:</th>
                                            <td><?php echo date('M d, Y h:i A', strtotime($result['created_at'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Questions and Answers -->
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class='bx bx-list-check'></i> Detailed Responses
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="questionsAccordion">
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" 
                                                type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $index; ?>">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <span>
                                                    Question <?php echo $index + 1; ?>
                                                    <span class="badge bg-secondary ms-2"><?php echo $question['points']; ?> points</span>
                                                </span>
                                                <span class="badge <?php echo $question['is_correct'] ? 'bg-success' : 'bg-danger'; ?> ms-2">
                                                    <?php echo $question['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>" 
                                         class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                         data-bs-parent="#questionsAccordion">
                                        <div class="accordion-body">
                                            <div class="question-text mb-3">
                                                <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                                            </div>

                                            <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                <?php 
                                                $options = json_decode($question['options'], true);
                                                if ($options): 
                                                ?>
                                                    <div class="options-list">
                                                        <?php foreach ($options as $i => $option): ?>
                                                            <div class="option mb-2 <?php 
                                                                if ($i == $question['correct_answer']) echo 'text-success fw-bold';
                                                                elseif ($i == $question['applicant_answer'] && !$question['is_correct']) echo 'text-danger';
                                                            ?>">
                                                                <?php echo chr(65 + $i) . '. ' . htmlspecialchars($option); ?>
                                                                <?php 
                                                                if ($i == $question['correct_answer']) {
                                                                    echo ' <i class="bx bx-check-circle text-success"></i>';
                                                                } elseif ($i == $question['applicant_answer'] && !$question['is_correct']) {
                                                                    echo ' <i class="bx bx-x-circle text-danger"></i>';
                                                                }
                                                                ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Applicant's Answer:</label>
                                                            <pre class="bg-light p-3 rounded"><code class="language-php"><?php echo htmlspecialchars($question['applicant_answer']); ?></code></pre>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Correct Solution:</label>
                                                            <pre class="bg-light p-3 rounded"><code class="language-php"><?php echo htmlspecialchars($question['solution']); ?></code></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($question['explanation'])): ?>
                                                <div class="mt-3">
                                                    <label class="form-label fw-bold">Explanation:</label>
                                                    <div class="alert alert-info">
                                                        <?php echo nl2br(htmlspecialchars($question['explanation'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media print {
    .wrapper {
        display: block !important;
    }
    .sidebar, .btn-toolbar, .accordion-button::after {
        display: none !important;
    }
    .page-content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .accordion-button {
        padding: 1rem 0;
    }
    .accordion-button:not(.collapsed) {
        color: inherit;
        background-color: transparent;
        box-shadow: none;
    }
    .accordion-body {
        padding: 1rem 0;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: transparent !important;
        color: #000 !important;
        border-bottom: 2px solid #dee2e6 !important;
    }
    .badge {
        border: 1px solid #000;
    }
    .badge.bg-success {
        color: #000 !important;
        background-color: transparent !important;
        border-color: #198754 !important;
    }
    .badge.bg-danger {
        color: #000 !important;
        background-color: transparent !important;
        border-color: #dc3545 !important;
    }
    .alert {
        border: 1px solid #000 !important;
        background-color: transparent !important;
    }
    pre {
        white-space: pre-wrap !important;
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<?php admin_footer(); ?>
