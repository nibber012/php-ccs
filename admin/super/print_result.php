<?php
require_once '../../classes/Auth.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$result_id = $_GET['result_id'] ?? null;

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

    // Get questions and answers
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
    $correctAnswers = 0;
    $totalScore = 0;
    $maxScore = 0;

    foreach ($questions as $question) {
        if ($question['is_correct']) {
            $correctAnswers++;
        }
        $totalScore += $question['score'] ?? 0;
        $maxScore += $question['points'];
    }

    $scorePercentage = ($totalScore / $maxScore) * 100;
    $passed = $scorePercentage >= $result['passing_score'];

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result - <?php echo htmlspecialchars($result['applicant_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
            }
            .page-break {
                page-break-before: always;
            }
        }
        .header-logo {
            max-width: 100px;
            height: auto;
        }
        .result-header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 10px;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <button class="btn btn-primary mb-4 no-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Result
        </button>

        <!-- Header -->
        <div class="result-header text-center">
            <img src="<?php echo $base_path; ?>/assets/images/ccs-logo.png" alt="CCS Logo" class="header-logo mb-3">
            <h2 class="mb-1">College of Computer Studies</h2>
            <h3 class="mb-3">Screening Examination Result</h3>
            <p class="text-muted">Date: <?php echo date('F d, Y', strtotime($result['completion_date'])); ?></p>
        </div>

        <!-- Applicant Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h4>Applicant Information</h4>
                <table class="table table-bordered">
                    <tr>
                        <th class="w-25">Name:</th>
                        <td><?php echo htmlspecialchars($result['applicant_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($result['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Contact:</th>
                        <td><?php echo htmlspecialchars($result['contact_number']); ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h4>Exam Information</h4>
                <table class="table table-bordered">
                    <tr>
                        <th class="w-25">Exam:</th>
                        <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                    </tr>
                    <tr>
                        <th>Part:</th>
                        <td><?php echo htmlspecialchars($result['exam_part']); ?></td>
                    </tr>
                    <tr>
                        <th>Type:</th>
                        <td><?php echo strtoupper(htmlspecialchars($result['exam_type'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Result Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Result Summary</h4>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h5>Final Score</h5>
                                <h2><?php echo number_format($scorePercentage, 1); ?>%</h2>
                                <span class="badge <?php echo $passed ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $passed ? 'PASSED' : 'FAILED'; ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <h5>Questions</h5>
                                <h2><?php echo $correctAnswers; ?>/<?php echo $totalQuestions; ?></h2>
                                <small class="text-muted">Correct Answers</small>
                            </div>
                            <div class="col-md-3">
                                <h5>Points</h5>
                                <h2><?php echo $totalScore; ?>/<?php echo $maxScore; ?></h2>
                                <small class="text-muted">Total Points</small>
                            </div>
                            <div class="col-md-3">
                                <h5>Passing Score</h5>
                                <h2><?php echo $result['passing_score']; ?>%</h2>
                                <small class="text-muted">Required to Pass</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Summary -->
        <div class="page-break"></div>
        <h4 class="mb-4">Question Summary</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Question Type</th>
                        <th>Points</th>
                        <th>Score</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $index => $question): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?></td>
                            <td><?php echo $question['points']; ?></td>
                            <td><?php echo $question['score'] ?? 0; ?></td>
                            <td>
                                <span class="badge <?php echo $question['is_correct'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $question['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Signature Section -->
        <div class="mt-5">
            <div class="row">
                <div class="col-md-6">
                    <div class="signature-line">
                        <p class="mb-0">Applicant's Signature</p>
                        <p class="text-muted">Date: ________________</p>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="signature-line ms-auto">
                        <p class="mb-0">Administrator's Signature</p>
                        <p class="text-muted">Date: ________________</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-5 text-center">
            <p class="text-muted small">This is an official examination result from the College of Computer Studies.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html><?php
// End the script here to prevent any unwanted output
exit();
?>
