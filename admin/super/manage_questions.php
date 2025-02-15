<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Get exam ID from URL
$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    header('Location: list_exams.php');
    exit();
}

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Get exam details
    $query = "SELECT * FROM exams WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        header('Location: list_exams.php');
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_question') {
            $question_text = $_POST['question_text'] ?? '';
            $question_type = $_POST['question_type'] ?? '';
            $points = $_POST['points'] ?? 1;
            
            if (empty($question_text) || empty($question_type)) {
                throw new Exception('Question text and type are required');
            }

            if ($question_type === 'multiple_choice') {
                // Handle multiple choice question
                $options = array_values(array_filter($_POST['options'] ?? [], fn($opt) => !empty($opt)));
                $correct_answer = $_POST['correct_answer'] ?? '';
                $explanation = $_POST['explanation'] ?? '';

                if (count($options) < 2) {
                    throw new Exception('At least two options are required');
                }
                if (!isset($options[$correct_answer])) {
                    throw new Exception('Please select a valid correct answer');
                }

                $query = "INSERT INTO questions (exam_id, question_text, question_type, points, options, correct_answer, explanation) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    $exam_id,
                    $question_text,
                    $question_type,
                    $points,
                    json_encode($options),
                    $correct_answer,
                    $explanation
                ]);

                if (!$result) {
                    throw new Exception('Failed to add question');
                }
            } else {
                // Handle coding question
                $coding_template = $_POST['coding_template'] ?? '';
                $solution = $_POST['solution'] ?? '';
                $explanation = $_POST['explanation'] ?? '';
                
                if (empty($coding_template)) {
                    throw new Exception('Code snippet is required for coding questions');
                }

                $query = "INSERT INTO questions (exam_id, question_text, question_type, points, coding_template, solution, explanation) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $result = $stmt->execute([
                    $exam_id,
                    $question_text,
                    $question_type,
                    $points,
                    $coding_template,
                    $solution,
                    $explanation
                ]);

                if (!$result) {
                    throw new Exception('Failed to add question');
                }
            }

            $auth->logActivity(
                $user['id'],
                'question_added',
                "Added new question to exam ID: $exam_id"
            );

            $_SESSION['success_message'] = 'Question added successfully';
            header("Location: manage_questions.php?exam_id=$exam_id");
            exit();
        }
    }

    // Get all questions for this exam
    $query = "SELECT * FROM questions WHERE exam_id = ? ORDER BY id ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total points
    $total_points = array_sum(array_column($questions, 'points'));

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Manage Questions';
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
                                    <li class="breadcrumb-item active" aria-current="page">Manage Questions</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                                <i class='bx bx-plus'></i> Add Question
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exam Info Card -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Exam Details</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="120">Title:</th>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type:</th>
                                        <td><?php echo ucfirst($exam['type']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Part:</th>
                                        <td>Part <?php echo $exam['part']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Duration:</th>
                                        <td><?php echo $exam['duration_minutes']; ?> minutes</td>
                                    </tr>
                                    <tr>
                                        <th>Questions:</th>
                                        <td><?php echo count($questions); ?> questions (<?php echo $total_points; ?> points total)</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Questions List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($questions)): ?>
                                <div class="text-center py-5">
                                    <i class='bx bx-question-mark bx-lg text-muted'></i>
                                    <p class="text-muted mt-2">No questions added yet. Click the "Add Question" button to add your first question.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($questions as $index => $question): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between align-items-center mb-2">
                                                <h5 class="mb-0">
                                                    Question <?php echo $index + 1; ?>
                                                    <span class="badge bg-primary ms-2"><?php echo $question['points']; ?> points</span>
                                                </h5>
                                                <div>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($question['question_type']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="question-content">
                                                <p class="mb-3"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                                
                                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                                    <?php 
                                                    $options = json_decode($question['options'], true);
                                                    if ($options): 
                                                    ?>
                                                        <div class="ms-4 mb-3">
                                                            <?php foreach ($options as $i => $option): ?>
                                                                <div class="option-item <?php echo $i == $question['correct_answer'] ? 'text-success fw-bold' : ''; ?>">
                                                                    <?php echo chr(65 + $i) . '. ' . htmlspecialchars($option); ?>
                                                                    <?php if ($i == $question['correct_answer']) echo ' <i class="bx bx-check-circle"></i>'; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (!empty($question['coding_template'])): ?>
                                                        <div class="mb-3">
                                                            <strong>Code Template:</strong>
                                                            <pre class="bg-light p-3 mt-2 rounded"><code class="language-php"><?php echo htmlspecialchars($question['coding_template']); ?></code></pre>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($question['solution'])): ?>
                                                        <div class="mb-3">
                                                            <strong>Solution:</strong>
                                                            <pre class="bg-light p-3 mt-2 rounded"><code class="language-php"><?php echo htmlspecialchars($question['solution']); ?></code></pre>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if (!empty($question['explanation'])): ?>
                                                    <div class="mt-2">
                                                        <strong>Explanation:</strong>
                                                        <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($question['explanation'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addQuestionForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_question">
                    
                    <div class="mb-3">
                        <label for="question_type" class="form-label">Question Type</label>
                        <select class="form-select" id="question_type" name="question_type" required>
                            <option value="">Select question type...</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="coding">Coding</option>
                        </select>
                        <div class="invalid-feedback">Please select a question type</div>
                    </div>

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                        <div class="invalid-feedback">Please enter the question text</div>
                    </div>

                    <div class="mb-3">
                        <label for="points" class="form-label">Points</label>
                        <input type="number" class="form-control" id="points" name="points" value="1" min="1" required>
                        <div class="invalid-feedback">Please enter a valid point value</div>
                    </div>

                    <!-- Multiple Choice Options -->
                    <div id="multipleChoiceOptions" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Options</label>
                            <div id="optionsContainer">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text"><?php echo chr(65 + $i); ?></span>
                                        <input type="text" class="form-control" name="options[]" placeholder="Enter option <?php echo chr(65 + $i); ?>">
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="form-text">Enter at least two options</div>
                        </div>

                        <div class="mb-3">
                            <label for="correct_answer" class="form-label">Correct Answer</label>
                            <select class="form-select" id="correct_answer" name="correct_answer">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <option value="<?php echo $i; ?>">Option <?php echo chr(65 + $i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Coding Question Options -->
                    <div id="codingOptions" style="display: none;">
                        <div class="mb-3">
                            <label for="coding_template" class="form-label">Code Template</label>
                            <textarea class="form-control font-monospace" id="coding_template" name="coding_template" rows="6" 
                                    placeholder="Enter the code template with missing parts..."></textarea>
                            <div class="form-text">The code template that students need to complete</div>
                        </div>

                        <div class="mb-3">
                            <label for="solution" class="form-label">Solution</label>
                            <textarea class="form-control font-monospace" id="solution" name="solution" rows="6" 
                                    placeholder="Enter the complete solution..."></textarea>
                            <div class="form-text">The complete code with correct syntax</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="explanation" class="form-label">Explanation (Optional)</label>
                        <textarea class="form-control" id="explanation" name="explanation" rows="3" 
                                placeholder="Enter explanation for the correct answer..."></textarea>
                        <div class="form-text">Provide an explanation for why the answer is correct</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Question type toggle
document.getElementById('question_type').addEventListener('change', function() {
    const multipleChoiceOptions = document.getElementById('multipleChoiceOptions')
    const codingOptions = document.getElementById('codingOptions')
    
    if (this.value === 'multiple_choice') {
        multipleChoiceOptions.style.display = 'block'
        codingOptions.style.display = 'none'
    } else if (this.value === 'coding') {
        multipleChoiceOptions.style.display = 'none'
        codingOptions.style.display = 'block'
    } else {
        multipleChoiceOptions.style.display = 'none'
        codingOptions.style.display = 'none'
    }
})

// Form submission validation
document.getElementById('addQuestionForm').addEventListener('submit', function(event) {
    const questionType = this.question_type.value
    
    if (questionType === 'multiple_choice') {
        // Validate multiple choice options
        const options = Array.from(this.querySelectorAll('input[name="options[]"]'))
            .map(input => input.value.trim())
            .filter(value => value !== '')
        
        if (options.length < 2) {
            event.preventDefault()
            alert('Please enter at least two options for multiple choice questions')
            return
        }
    } else if (questionType === 'coding') {
        // Validate coding template
        if (!this.coding_template.value.trim()) {
            event.preventDefault()
            alert('Please enter a code template for coding questions')
            return
        }
    }
})
</script>

<?php admin_footer(); ?>
