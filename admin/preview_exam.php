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
$exam_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

    // Fetch exam questions
    $stmt = $db->query(
        "SELECT q.*, c.category_name
         FROM questions q
         LEFT JOIN categories c ON q.category_id = c.id
         WHERE q.exam_id = ?
         ORDER BY q.order_number",
        [$exam_id]
    );
    $questions = $stmt->fetchAll();

    // Group questions by category
    $categorized_questions = [];
    foreach ($questions as $question) {
        $category = $question['category_name'] ?? 'Uncategorized';
        if (!isset($categorized_questions[$category])) {
            $categorized_questions[$category] = [];
        }
        $categorized_questions[$category][] = $question;
    }

} catch (Exception $e) {
    error_log("Error in preview_exam.php: " . $e->getMessage());
    $error = "An error occurred while fetching exam details.";
}

admin_header('Preview Exam');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Preview Exam</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="list_exams.php">Exams</a></li>
                        <li class="breadcrumb-item active">Preview</li>
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
                        <div class="col-md-8">
                            <h4 class="card-title"><?php echo htmlspecialchars($exam['exam_title']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($exam['description']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-end">
                                <div class="text-end me-4">
                                    <h6 class="mb-1">Duration</h6>
                                    <p class="mb-0 fw-bold"><?php echo $exam['duration']; ?> minutes</p>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-1">Passing Score</h6>
                                    <p class="mb-0 fw-bold"><?php echo $exam['passing_score']; ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="accordion" id="examQuestions">
                <?php if (!empty($categorized_questions)): ?>
                    <?php foreach ($categorized_questions as $category => $questions): ?>
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#category<?php echo md5($category); ?>">
                                    <?php echo htmlspecialchars($category); ?> 
                                    <span class="badge bg-primary ms-2"><?php echo count($questions); ?> questions</span>
                                </button>
                            </h2>
                            <div id="category<?php echo md5($category); ?>" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="list-group">
                                        <?php foreach ($questions as $index => $question): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between mb-2">
                                                    <h6 class="mb-1">Question <?php echo $index + 1; ?></h6>
                                                    <small class="text-muted">
                                                        Points: <?php echo $question['points']; ?>
                                                    </small>
                                                </div>
                                                <p class="mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                                
                                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                    <div class="list-group">
                                                        <?php 
                                                            $choices = json_decode($question['choices'], true);
                                                            foreach ($choices as $choice):
                                                        ?>
                                                            <div class="list-group-item list-group-item-action <?php 
                                                                echo $choice === $question['correct_answer'] ? 'list-group-item-success' : ''; 
                                                            ?>">
                                                                <?php echo htmlspecialchars($choice); ?>
                                                                <?php if ($choice === $question['correct_answer']): ?>
                                                                    <i class="bx bx-check float-end text-success"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-success">
                                                        <strong>Correct Answer:</strong> 
                                                        <?php echo htmlspecialchars($question['correct_answer']); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($question['explanation'])): ?>
                                                    <div class="mt-3">
                                                        <strong>Explanation:</strong>
                                                        <p class="mb-0 text-muted">
                                                            <?php echo htmlspecialchars($question['explanation']); ?>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        No questions found for this exam.
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<style>
.card {
    margin-bottom: 1.5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}

.accordion-item {
    border: 1px solid rgba(0,0,0,.125);
    border-radius: .25rem;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.list-group-item {
    border: 1px solid rgba(0,0,0,.125);
}

.list-group-item-success {
    color: #0f5132;
    background-color: #d1e7dd;
}

@media print {
    .sidebar-wrapper {
        display: none !important;
    }
    
    .col-md-9 {
        width: 100% !important;
        margin: 0 !important;
    }

    .accordion-button::after {
        display: none;
    }

    .accordion-collapse {
        display: block !important;
    }
}
</style>

<?php admin_footer(); ?>
