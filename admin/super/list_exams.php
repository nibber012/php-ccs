<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Handle exam status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['exam_id'])) {
        $exam_id = $_POST['exam_id'];
        $action = $_POST['action'];

        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();

            switch ($action) {
                case 'publish':
                    $status = 'published';
                    break;
                case 'archive':
                    $status = 'archived';
                    break;
                case 'draft':
                    $status = 'draft';
                    break;
                default:
                    throw new Exception('Invalid action');
            }

            $query = "UPDATE exams SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$status, $exam_id]);

            if ($stmt->rowCount() > 0) {
                $auth->logActivity(
                    $user['id'],
                    'exam_' . $action,
                    "Exam (ID: $exam_id) status changed to $status"
                );
                $success = 'Exam status updated successfully';
            } else {
                $error = 'Failed to update exam status';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Initialize examsByPart array
$examsByPart = [];

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    $query = "SELECT e.*, 
              CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
              (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
              (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.id) as attempts_count
              FROM exams e
              JOIN users u ON e.created_by = u.id
              ORDER BY e.part ASC, e.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group exams by part
    foreach ($exams as $exam) {
        $part = $exam['part'] ?? 'Unassigned';
        if (!isset($examsByPart[$part])) {
            $examsByPart[$part] = [];
        }
        $examsByPart[$part][] = $exam;
    }

} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
    $exams = [];
}

$page_title = 'Manage Exams';
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
                            <a href="create_exam.php" class="btn btn-sm btn-primary me-2">
                                <i class='bx bx-plus'></i> Create New Exam
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class='bx bx-printer'></i> Print Report
                            </button>
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

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Exam Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Exams
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo count($exams); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-book-content bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Published Exams
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        echo count(array_filter($exams, function($exam) {
                                            return $exam['status'] === 'published';
                                        }));
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-check-circle bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Draft Exams
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        echo count(array_filter($exams, function($exam) {
                                            return $exam['status'] === 'draft';
                                        }));
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-edit bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Attempts
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        echo array_sum(array_column($exams, 'attempts_count'));
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class='bx bx-bar-chart-alt-2 bx-lg text-gray-300'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exams List -->
            <?php foreach ($examsByPart as $part => $partExams): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Part <?php echo $part; ?> Exams</h5>
                    <span class="badge bg-white text-primary"><?php echo count($partExams); ?> Exams</span>
                </div>
                <div class="card-body">
                    <?php if (empty($partExams)): ?>
                    <p class="text-center text-muted my-5">No exams found for Part <?php echo $part; ?></p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th>Questions</th>
                                    <th>Attempts</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($partExams as $exam): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold"><?php echo htmlspecialchars($exam['title']); ?></span>
                                            <?php if (!empty($exam['description'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($exam['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $exam['type'] === 'mcq' ? 'bg-info' : 'bg-warning'; ?>">
                                            <?php echo strtoupper($exam['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($exam['duration_minutes']); ?> mins</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($exam['question_count']); ?> questions
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($exam['attempts_count']); ?> attempts
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($exam['status']) {
                                                'published' => 'bg-success',
                                                'draft' => 'bg-warning',
                                                'archived' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($exam['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class='bx bx-user-circle me-2'></i>
                                            <?php echo htmlspecialchars($exam['created_by_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class='bx bx-calendar me-2'></i>
                                            <?php echo date('M d, Y', strtotime($exam['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="edit_exam.php?id=<?php echo $exam['id']; ?>">
                                                        <i class='bx bx-edit'></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="manage_questions.php?exam_id=<?php echo $exam['id']; ?>">
                                                        <i class='bx bx-list-check'></i> Manage Questions
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="exam_results.php?exam_id=<?php echo $exam['id']; ?>">
                                                        <i class='bx bx-bar-chart-alt-2'></i> View Results
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <?php if ($exam['status'] !== 'published'): ?>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                        <input type="hidden" name="action" value="publish">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class='bx bx-check-circle'></i> Publish
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                                <?php if ($exam['status'] !== 'draft'): ?>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                        <input type="hidden" name="action" value="draft">
                                                        <button type="submit" class="dropdown-item text-warning">
                                                            <i class='bx bx-edit'></i> Move to Draft
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                                <?php if ($exam['status'] !== 'archived'): ?>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                        <input type="hidden" name="action" value="archive">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class='bx bx-archive'></i> Archive
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
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
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
@media print {
    .btn-toolbar,
    .sidebar,
    .dropdown,
    .filters {
        display: none !important;
    }
    .page-content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php admin_footer(); ?>
