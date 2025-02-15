<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Get current user
$user = $auth->getCurrentUser();

// Initialize statistics
$stats = [
    'recommended' => 0,
    'not_recommended' => 0,
    'pending' => 0,
    'total' => 0
];

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build the query
$query = "SELECT i.*, 
                 CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                 u.email as applicant_email,
                 u.program,
                 CONCAT(a.first_name, ' ', a.last_name) as interviewer_name
          FROM interviews i
          JOIN users u ON i.user_id = u.id
          LEFT JOIN users a ON i.created_by = a.id
          WHERE i.status = 'completed'";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($date_from)) {
    $query .= " AND DATE(i.schedule) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(i.schedule) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY i.schedule DESC";

try {
    $stmt = $db->query($query, $params);
    $interviews = $stmt->fetchAll();

    // Get statistics
    $stats_query = "SELECT 
            COUNT(CASE WHEN recommendation = 'hire' THEN 1 END) as recommended_hire,
            COUNT(CASE WHEN recommendation = 'reject' THEN 1 END) as recommended_reject,
            COUNT(CASE WHEN recommendation = 'pending' THEN 1 END) as pending_decision,
            COUNT(*) as total_interviews
         FROM interviews 
         WHERE status = 'completed'";
    $stats_result = $db->query($stats_query)->fetch();

    if ($stats_result) {
        $stats = [
            'recommended_hire' => $stats_result['recommended_hire'],
            'recommended_reject' => $stats_result['recommended_reject'],
            'pending_decision' => $stats_result['pending_decision'],
            'total_interviews' => $stats_result['total_interviews']
        ];
    }

} catch (Exception $e) {
    error_log("Error in interview_results.php: " . $e->getMessage());
    $error = "An error occurred while fetching interview results.";
}

admin_header('Interview Results');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Interview Results</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="interview_list.php">Interviews</a></li>
                        <li class="breadcrumb-item active">Results</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Total Interviews</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['total_interviews']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Recommended for Hire</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['recommended_hire']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Not Recommended</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['recommended_reject']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Pending Decision</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['pending_decision']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Name or Email">
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Program</th>
                                    <th>Interview Date</th>
                                    <th>Interviewer</th>
                                    <th>Recommendation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($interviews)): ?>
                                    <?php foreach ($interviews as $interview): ?>
                                        <tr>
                                            <td>
                                                <div><?php echo htmlspecialchars($interview['applicant_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($interview['applicant_email']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($interview['program']); ?></td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($interview['schedule'])); ?></td>
                                            <td><?php echo htmlspecialchars($interview['interviewer_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $interview['recommendation'] === 'hire' ? 'success' : 
                                                        ($interview['recommendation'] === 'reject' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($interview['recommendation']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_interview.php?id=<?php echo $interview['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View Details">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <a href="applicant_results.php?id=<?php echo $interview['user_id']; ?>" 
                                                       class="btn btn-sm btn-outline-info"
                                                       title="View All Results">
                                                        <i class="bx bx-user"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No completed interviews found</td>
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
