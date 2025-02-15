<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/CourseManager.php';

// Initialize Auth
$auth = new Auth();

// Check if user is logged in and is an admin
if (!$auth->isLoggedIn() || !$auth->hasRole(['admin', 'super_admin'])) {
    header('Location: ../login.php');
    exit();
}

$courseManager = new CourseManager();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $courseManager->addCourse(
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['description'] ?? ''
                );
                break;
            
            case 'update':
                $courseManager->updateCourse(
                    $_POST['id'],
                    $_POST['code'],
                    $_POST['name'],
                    $_POST['description'] ?? ''
                );
                break;
            
            case 'toggle':
                $courseManager->toggleCourseStatus($_POST['id']);
                break;
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: manage_courses.php');
    exit();
}

// Get all courses
$courses = $courseManager->getCourses(false);

// Include header
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2>Manage Courses</h2>
    
    <!-- Add Course Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add New Course</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="code">Course Code</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Course Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Add Course</button>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Courses List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Existing Courses</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['code']) ?></td>
                            <td><?= htmlspecialchars($course['name']) ?></td>
                            <td><?= htmlspecialchars($course['description']) ?></td>
                            <td>
                                <span class="badge badge-<?= $course['status'] === 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($course['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $course['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= $course['status'] === 'active' ? 'warning' : 'success' ?>">
                                        <?= $course['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-primary edit-course" 
                                        data-id="<?= $course['id'] ?>"
                                        data-code="<?= htmlspecialchars($course['code']) ?>"
                                        data-name="<?= htmlspecialchars($course['name']) ?>"
                                        data-description="<?= htmlspecialchars($course['description']) ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Course</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_code">Course Code</label>
                        <input type="text" class="form-control" id="edit_code" name="code" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Course Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-course').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('editCourseModal');
            
            // Set form values
            modal.querySelector('#edit_id').value = this.dataset.id;
            modal.querySelector('#edit_code').value = this.dataset.code;
            modal.querySelector('#edit_name').value = this.dataset.name;
            modal.querySelector('#edit_description').value = this.dataset.description;
            
            // Show modal
            $(modal).modal('show');
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
