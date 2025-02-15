<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Initialize variables
$stats = [
    'total_interviews' => 0,
    'passed_count' => 0,
    'failed_count' => 0,
    'average_score' => 0
];
$interviews = [];
$total_pages = 0;
$total_count = 0;

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // First, ensure the columns exist
    try {
        $conn->exec("
            ALTER TABLE interview_schedules 
            ADD COLUMN IF NOT EXISTS interview_status ENUM('pending', 'passed', 'failed') NOT NULL DEFAULT 'pending',
            ADD COLUMN IF NOT EXISTS total_score INT DEFAULT NULL
        ");
    } catch (PDOException $e) {
        // Ignore if columns already exist
    }

    // Get filters
    $status = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 10;

    // Build where clause
    $where_conditions = ["i.status = 'completed'"];
    $params = [];

    if ($status) {
        $where_conditions[] = "i.interview_status = ?";
        $params[] = $status;
    }

    if ($date_from) {
        $where_conditions[] = "DATE(i.schedule_date) >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = "DATE(i.schedule_date) <= ?";
        $params[] = $date_to;
    }

    if ($search) {
        $where_conditions[] = "(a.first_name LIKE ? OR a.last_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    // Get total count
    $query = "SELECT COUNT(*) as total 
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              $where_clause";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $total_count = $stmt->fetch()['total'];
    $total_pages = ceil($total_count / $per_page);

    // Get statistics
    $query = "SELECT 
                COUNT(*) as total_interviews,
                SUM(CASE WHEN COALESCE(i.interview_status, 'pending') = 'passed' THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN COALESCE(i.interview_status, 'pending') = 'failed' THEN 1 ELSE 0 END) as failed_count,
                COALESCE(AVG(NULLIF(i.total_score, 0)), 0) as average_score
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              $where_clause";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Set default values for stats if null
    $stats['total_interviews'] = $stats['total_interviews'] ?? 0;
    $stats['passed_count'] = $stats['passed_count'] ?? 0;
    $stats['failed_count'] = $stats['failed_count'] ?? 0;
    $stats['average_score'] = $stats['average_score'] ?? 0;

    // Get interviews with pagination
    $offset = ($page - 1) * $per_page;
    $query = "SELECT 
                i.id,
                a.first_name,
                a.last_name,
                i.schedule_date,
                i.start_time,
                i.end_time,
                i.interview_status,
                i.total_score,
                CONCAT(u.first_name, ' ', u.last_name) as interviewer_name
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              JOIN users u ON i.interviewer_id = u.id
              $where_clause
              ORDER BY i.schedule_date DESC, i.start_time DESC
              LIMIT $per_page OFFSET $offset";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('Interview Results');
?>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0">Interview Results</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Interview</li>
                    <li class="breadcrumb-item active" aria-current="page">Results</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error-circle me-1"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-primary border-start border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-light-primary rounded">
                            <i class="bx bx-user-voice fs-1 text-primary"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1"><?php echo number_format($stats['total_interviews']); ?></h4>
                            <p class="text-muted mb-0">Total Interviews</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-success border-start border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-light-success rounded">
                            <i class="bx bx-check-circle fs-1 text-success"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1"><?php echo number_format($stats['passed_count']); ?></h4>
                            <p class="text-muted mb-0">Passed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-danger border-start border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-light-danger rounded">
                            <i class="bx bx-x-circle fs-1 text-danger"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1"><?php echo number_format($stats['failed_count']); ?></h4>
                            <p class="text-muted mb-0">Failed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-info border-start border-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-light-info rounded">
                            <i class="bx bx-bar-chart-alt-2 fs-1 text-info"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1"><?php echo number_format($stats['average_score'], 1); ?>%</h4>
                            <p class="text-muted mb-0">Average Score</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search applicant name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="passed" <?php echo $status === 'passed' ? 'selected' : ''; ?>>Passed</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-filter-alt me-1"></i> Apply Filters
                    </button>
                    <a href="interview_results.php" class="btn btn-outline-secondary">
                        <i class="bx bx-reset me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0">Interview Results</h5>
        </div>
        <div class="card-body">
            <?php if (empty($interviews)): ?>
                <div class="text-center py-5">
                    <i class="bx bx-info-circle text-muted fs-1"></i>
                    <p class="text-muted mb-0 mt-3">No interview results found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Interviewer</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interviews as $interview): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($interview['schedule_date'])); ?></td>
                                    <td>
                                        <?php 
                                        echo date('h:i A', strtotime($interview['start_time'])) . ' - ' . 
                                             date('h:i A', strtotime($interview['end_time'])); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($interview['interviewer_name']); ?></td>
                                    <td>
                                        <?php if ($interview['total_score']): ?>
                                            <span class="badge bg-primary"><?php echo $interview['total_score']; ?>%</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'passed' => 'success',
                                            'failed' => 'danger',
                                            'pending' => 'warning'
                                        ][$interview['interview_status'] ?? 'pending'];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($interview['interview_status'] ?? 'Pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_interview.php?id=<?php echo $interview['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-show"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bx bx-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.avatar-lg {
    width: 4rem;
    height: 4rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bg-light-primary {
    background-color: rgba(78, 115, 223, 0.1) !important;
}
.bg-light-success {
    background-color: rgba(28, 200, 138, 0.1) !important;
}
.bg-light-danger {
    background-color: rgba(231, 74, 59, 0.1) !important;
}
.bg-light-info {
    background-color: rgba(54, 185, 204, 0.1) !important;
}
</style>

<?php
admin_footer();
?>
