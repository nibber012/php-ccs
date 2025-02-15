<?php
require_once '../../classes/Auth.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Get exam ID from URL
$exam_id = $_GET['id'] ?? null;

if (!$exam_id) {
    header('Location: list_exams.php');
    exit();
}

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $type = $_POST['type'] ?? '';
        $duration = $_POST['duration'] ?? 60;
        $passing_score = $_POST['passing_score'] ?? 75;
        $part = $_POST['part'] ?? 1;

        // Validate input
        if (empty($title)) {
            throw new Exception('Title is required');
        }
        if (empty($type)) {
            throw new Exception('Type is required');
        }
        if (!is_numeric($duration) || $duration < 1) {
            throw new Exception('Duration must be a positive number');
        }
        if (!is_numeric($passing_score) || $passing_score < 0 || $passing_score > 100) {
            throw new Exception('Passing score must be between 0 and 100');
        }
        if (!in_array($part, [1, 2])) {
            throw new Exception('Invalid part number');
        }

        // Update exam
        $query = "UPDATE exams SET 
                  title = ?, 
                  description = ?, 
                  type = ?, 
                  duration_minutes = ?, 
                  passing_score = ?,
                  part = ?,
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            $title,
            $description,
            $type,
            $duration,
            $passing_score,
            $part,
            $exam_id
        ]);

        if ($result) {
            $auth->logActivity(
                $user['id'],
                'exam_updated',
                "Updated exam ID: $exam_id"
            );
            $_SESSION['success_message'] = 'Exam updated successfully';
            header('Location: list_exams.php');
            exit();
        } else {
            throw new Exception('Failed to update exam');
        }
    }

    // Get exam details
    $query = "SELECT * FROM exams WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        throw new Exception('Exam not found');
    }

} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}

admin_header('Edit Exam');
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Exam</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="list_exams.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($exam['title'] ?? ''); ?>" required>
                <div class="invalid-feedback">
                    Please enter a title
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"
                          placeholder="Enter exam description (optional)"><?php echo htmlspecialchars($exam['description'] ?? ''); ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="part" class="form-label">Part</label>
                    <select class="form-select" id="part" name="part" required>
                        <option value="1" <?php echo ($exam['part'] ?? '') == 1 ? 'selected' : ''; ?>>Part 1 (Multiple Choice)</option>
                        <option value="2" <?php echo ($exam['part'] ?? '') == 2 ? 'selected' : ''; ?>>Part 2 (Code Completion)</option>
                    </select>
                    <div class="invalid-feedback">
                        Please select a part
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="mcq" <?php echo ($exam['type'] ?? '') === 'mcq' ? 'selected' : ''; ?>>Multiple Choice</option>
                        <option value="coding" <?php echo ($exam['type'] ?? '') === 'coding' ? 'selected' : ''; ?>>Code Completion</option>
                    </select>
                    <div class="invalid-feedback">
                        Please select a type
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="duration" class="form-label">Duration (minutes)</label>
                    <input type="number" class="form-control" id="duration" name="duration" 
                           value="<?php echo htmlspecialchars($exam['duration'] ?? 60); ?>" min="1" required>
                    <div class="invalid-feedback">
                        Please enter a valid duration
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="passing_score" class="form-label">Passing Score (%)</label>
                    <input type="number" class="form-control" id="passing_score" name="passing_score" 
                           value="<?php echo htmlspecialchars($exam['passing_score'] ?? 75); ?>" min="0" max="100" required>
                    <div class="invalid-feedback">
                        Please enter a valid passing score (0-100)
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="list_exams.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
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

// Sync part and type selection
document.getElementById('part').addEventListener('change', function() {
    const type = document.getElementById('type')
    if (this.value === '1') {
        type.value = 'mcq'
    } else {
        type.value = 'coding'
    }
})

// Initial sync
document.getElementById('part').dispatchEvent(new Event('change'))
</script>

<?php
admin_footer();
?>
