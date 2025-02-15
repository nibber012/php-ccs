<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Initialize variables
$exam_title = '';
$duration = '';

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? trim($_GET['order']) : 'DESC';

// Build the query
$query = "SELECT e.*, 
                 COUNT(DISTINCT er.id) as total_attempts,
                 AVG(er.score) as average_score
          FROM exams e
          LEFT JOIN exam_results er ON e.id = er.exam_id";

$params = [];
$where = [];

if (!empty($search)) {
    $where[] = "(e.exam_title LIKE ? OR e.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $where[] = "e.status = ?";
    $params[] = $status;
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY e.id";
$query .= " ORDER BY e.$sort $order";

try {
    $stmt = $db->query($query, $params);
    $exams = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching exams: " . $e->getMessage());
    $error = "An error occurred while fetching exams.";
}

admin_header('List Exams');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Exams</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Exams</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Exam title or description">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Created</option>
                                <option value="exam_title" <?php echo $sort === 'exam_title' ? 'selected' : ''; ?>>Title</option>
                                <option value="duration" <?php echo $sort === 'duration' ? 'selected' : ''; ?>>Duration</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Exams List -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Passing Score</th>
                                    <th>Total Attempts</th>
                                    <th>Average Score</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($exams)): ?>
                                    <?php foreach ($exams as $exam): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($exam['exam_title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($exam['description']); ?></small>
                                            </td>
                                            <td><?php echo $exam['duration']; ?> minutes</td>
                                            <td><?php echo $exam['passing_score']; ?>%</td>
                                            <td><?php echo $exam['total_attempts']; ?></td>
                                            <td>
                                                <?php 
                                                    echo $exam['total_attempts'] > 0 
                                                        ? number_format($exam['average_score'], 1) . '%'
                                                        : 'N/A';
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $exam['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($exam['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="exam_results.php?exam_id=<?php echo $exam['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="View Results">
                                                        <i class="bx bx-bar-chart-alt-2"></i>
                                                    </a>
                                                    <a href="preview_exam.php?id=<?php echo $exam['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info"
                                                       title="Preview Exam">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No exams found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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

.btn-group {
    gap: 0.25rem;
}

@media print {
    .sidebar-wrapper, .btn-toolbar, form {
        display: none !important;
    }
    
    .col-md-9 {
        width: 100% !important;
        margin: 0 !important;
    }
}
</style>

<?php admin_footer(); ?>
