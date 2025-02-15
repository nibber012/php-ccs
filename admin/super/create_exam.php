<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? '';
    $part = $_POST['part'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $passing_score = $_POST['passing_score'] ?? '';
    $instructions = $_POST['instructions'] ?? '';

    if (empty($title) || empty($type) || empty($duration) || empty($passing_score) || empty($part)) {
        $error = 'All fields except description and instructions are required';
    } elseif (!is_numeric($duration) || $duration <= 0) {
        $error = 'Duration must be a positive number';
    } elseif (!is_numeric($passing_score) || $passing_score < 0 || $passing_score > 100) {
        $error = 'Passing score must be between 0 and 100';
    } else {
        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();

            $query = "INSERT INTO exams (title, description, type, part, duration_minutes, passing_score, instructions, status, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$title, $description, $type, $part, $duration, $passing_score, $instructions, $user['id']]);
            
            $exam_id = $conn->lastInsertId();

            $auth->logActivity(
                $user['id'],
                'exam_created',
                "Created new exam: $title (Part $part)"
            );

            // Redirect to question management
            header("Location: manage_questions.php?exam_id=$exam_id");
            exit();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Create New Exam';
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
                        <h1 class="h2"><?php echo $page_title; ?></h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="list_exams.php" class="btn btn-sm btn-outline-secondary">
                                <i class='bx bx-arrow-back'></i> Back to Exams
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Create Exam Form -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Exam Title</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                                   placeholder="Enter exam title" required>
                                            <div class="invalid-feedback">
                                                Please enter an exam title
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description (Optional)</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                      rows="3" placeholder="Enter exam description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label for="instructions" class="form-label">Instructions (Optional)</label>
                                            <textarea class="form-control" id="instructions" name="instructions" 
                                                      rows="4" placeholder="Enter exam instructions"><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : ''; ?></textarea>
                                            <small class="text-muted">
                                                Provide clear instructions for examinees. These will be displayed before starting the exam.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h5 class="card-title">Exam Settings</h5>
                                                
                                                <div class="mb-3">
                                                    <label for="part" class="form-label">Exam Part</label>
                                                    <select class="form-select" id="part" name="part" required>
                                                        <option value="">Select part...</option>
                                                        <option value="1" <?php echo isset($_POST['part']) && $_POST['part'] == '1' ? 'selected' : ''; ?>>Part 1</option>
                                                        <option value="2" <?php echo isset($_POST['part']) && $_POST['part'] == '2' ? 'selected' : ''; ?>>Part 2</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select an exam part
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="type" class="form-label">Exam Type</label>
                                                    <select class="form-select" id="type" name="type" required>
                                                        <option value="">Select type...</option>
                                                        <option value="mcq" <?php echo isset($_POST['type']) && $_POST['type'] == 'mcq' ? 'selected' : ''; ?>>Multiple Choice</option>
                                                        <option value="coding" <?php echo isset($_POST['type']) && $_POST['type'] == 'coding' ? 'selected' : ''; ?>>Coding</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select an exam type
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                                    <input type="number" class="form-control" id="duration" name="duration" 
                                                           value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '60'; ?>"
                                                           min="1" required>
                                                    <div class="invalid-feedback">
                                                        Please enter a valid duration
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="passing_score" class="form-label">Passing Score (%)</label>
                                                    <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                                           value="<?php echo isset($_POST['passing_score']) ? htmlspecialchars($_POST['passing_score']) : '75'; ?>"
                                                           min="0" max="100" required>
                                                    <div class="invalid-feedback">
                                                        Please enter a valid passing score (0-100)
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="list_exams.php" class="btn btn-outline-secondary">
                                        <i class='bx bx-x'></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class='bx bx-save'></i> Create Exam
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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
</script>

<?php admin_footer(); ?>
