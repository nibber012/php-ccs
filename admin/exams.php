<?php
require_once '../classes/Auth.php';
require_once '../classes/Exam.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole(['admin', 'super_admin']);

$exam = new Exam();
$user = $auth->getCurrentUser();

$action = $_GET['action'] ?? 'list';

get_header('Manage Exams');
get_sidebar($user['role']);
?>

<div class="content">
    <div class="container-fluid">
        <?php if($action === 'list'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Manage Exams</h1>
                <a href="?action=create" class="btn btn-primary">Create New Exam</a>
            </div>

            <!-- Exam List -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM exams ORDER BY created_at DESC";
                                $stmt = $exam->conn->prepare($query);
                                $stmt->execute();
                                while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo ucfirst($row['type']); ?></td>
                                    <td><?php echo $row['duration_minutes']; ?> minutes</td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="?action=questions&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Questions</a>
                                        <?php if($row['status'] === 'draft'): ?>
                                            <a href="?action=publish&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">Publish</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif($action === 'create' || $action === 'edit'): ?>
            <?php
            $exam_data = null;
            if($action === 'edit' && isset($_GET['id'])) {
                $exam_data = $exam->getExam($_GET['id']);
            }
            ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo $action === 'create' ? 'Create New Exam' : 'Edit Exam'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="exam_process.php">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <?php if($exam_data): ?>
                            <input type="hidden" name="id" value="<?php echo $exam_data['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="title" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $exam_data ? htmlspecialchars($exam_data['title']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                echo $exam_data ? htmlspecialchars($exam_data['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Exam Type</label>
                                    <select class="form-control" id="type" name="type" required>
                                        <option value="mcq" <?php echo $exam_data && $exam_data['type'] === 'mcq' ? 'selected' : ''; ?>>
                                            Multiple Choice
                                        </option>
                                        <option value="coding" <?php echo $exam_data && $exam_data['type'] === 'coding' ? 'selected' : ''; ?>>
                                            Coding
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" id="duration" name="duration_minutes" 
                                           value="<?php echo $exam_data ? $exam_data['duration_minutes'] : '60'; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="passing_score" class="form-label">Passing Score</label>
                                    <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                           value="<?php echo $exam_data ? $exam_data['passing_score'] : '70'; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Exam</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif($action === 'questions' && isset($_GET['id'])): ?>
            <?php
            $exam_data = $exam->getExam($_GET['id']);
            $questions = $exam->getExamQuestions($_GET['id']);
            ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Questions for: <?php echo htmlspecialchars($exam_data['title']); ?></h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                    Add Question
                </button>
            </div>

            <!-- Questions List -->
            <div class="card">
                <div class="card-body">
                    <?php if(empty($questions)): ?>
                        <p class="text-center text-muted">No questions added yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($questions as $index => $question): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Question <?php echo $index + 1; ?></h5>
                                        <small><?php echo ucfirst($question['question_type']); ?> - <?php echo $question['points']; ?> points</small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                    <?php if($question['question_type'] === 'multiple_choice'): ?>
                                        <?php $options = json_decode($question['options'], true); ?>
                                        <div class="mt-2">
                                            <strong>Options:</strong>
                                            <ul class="list-unstyled">
                                                <?php foreach($options as $option): ?>
                                                    <li>
                                                        <i class="bi <?php echo $option === $question['correct_answer'] ? 'bi-check-circle-fill text-success' : 'bi-circle'; ?>"></i>
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-primary" onclick="editQuestion(<?php echo $question['id']; ?>)">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add Question Modal -->
            <div class="modal fade" id="addQuestionModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="questionForm" method="POST" action="exam_process.php">
                                <input type="hidden" name="action" value="add_question">
                                <input type="hidden" name="exam_id" value="<?php echo $_GET['id']; ?>">

                                <div class="mb-3">
                                    <label for="question_type" class="form-label">Question Type</label>
                                    <select class="form-control" id="question_type" name="question_type" required>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <?php if($exam_data['type'] === 'coding'): ?>
                                            <option value="coding">Coding</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="question_text" class="form-label">Question Text</label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="points" class="form-label">Points</label>
                                    <input type="number" class="form-control" id="points" name="points" value="1" required>
                                </div>

                                <div id="mcqOptions" class="mb-3">
                                    <label class="form-label">Options</label>
                                    <div id="optionsContainer">
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="options[]" required>
                                            <div class="input-group-text">
                                                <input type="radio" name="correct_answer" value="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="addOption()">Add Option</button>
                                </div>

                                <div id="codingOptions" class="mb-3" style="display: none;">
                                    <div class="mb-3">
                                        <label for="coding_template" class="form-label">Code Template</label>
                                        <textarea class="form-control" id="coding_template" name="coding_template" rows="5"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="test_cases" class="form-label">Test Cases (JSON format)</label>
                                        <textarea class="form-control" id="test_cases" name="test_cases" rows="5"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" form="questionForm" class="btn btn-primary">Add Question</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function addOption() {
                    const container = document.getElementById('optionsContainer');
                    const optionCount = container.children.length;
                    
                    const div = document.createElement('div');
                    div.className = 'input-group mb-2';
                    div.innerHTML = `
                        <input type="text" class="form-control" name="options[]" required>
                        <div class="input-group-text">
                            <input type="radio" name="correct_answer" value="${optionCount}" required>
                        </div>
                    `;
                    container.appendChild(div);
                }

                document.getElementById('question_type').addEventListener('change', function() {
                    const mcqOptions = document.getElementById('mcqOptions');
                    const codingOptions = document.getElementById('codingOptions');
                    
                    if(this.value === 'multiple_choice') {
                        mcqOptions.style.display = 'block';
                        codingOptions.style.display = 'none';
                    } else {
                        mcqOptions.style.display = 'none';
                        codingOptions.style.display = 'block';
                    }
                });

                function deleteQuestion(questionId) {
                    if(confirm('Are you sure you want to delete this question?')) {
                        window.location.href = `exam_process.php?action=delete_question&id=${questionId}&exam_id=<?php echo $_GET['id']; ?>`;
                    }
                }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
