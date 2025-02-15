<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/admin_layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();

// Get current user
$user = $auth->getCurrentUser();

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? trim($_GET['order']) : 'DESC';

// Initialize params
$params = [];

// Build the query with proper joins
$query = "SELECT u.id, u.role, u.status, u.created_at, 
       u.email,  -- 📌 Email comes from users
       a.first_name, a.last_name,  -- 📌 Name comes from applicants
       e.score AS exam_score,
       e.status AS exam_status,
       i.latest_schedule AS interview_schedule,
       i.status AS interview_status
FROM users u
JOIN applicants a ON u.id = a.user_id  -- ✅ Fetch names from applicants
LEFT JOIN (
    SELECT applicant_id, MAX(score) AS score, status
    FROM exam_results
    GROUP BY applicant_id
) e ON a.id = e.applicant_id
LEFT JOIN (
    SELECT applicant_id, MAX(schedule_date) AS latest_schedule, status
    FROM interview_schedules
    GROUP BY applicant_id
) i ON a.id = i.applicant_id
";

$whereClauses = ["u.role = 'applicant'"]; // ✅ Always include base WHERE condition

if (!empty($search)) {
    $whereClauses[] = "(a.first_name LIKE ? OR a.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $whereClauses[] = "u.status = ?";
    $params[] = $status;
}

// ✅ Build WHERE clause safely
$query .= " WHERE " . implode(" AND ", $whereClauses);

// Add sorting with proper field references
$sortField = match($sort) {
    'last_name' => 'u.last_name',
    'status' => 'u.status',
    'exam_score' => 'e.score',
    'interview_date' => 'i.latest_schedule', // ✅ Fixed field name
    default => 'u.created_at'
};

$query .= " ORDER BY " . ($sortField ?? 'u.created_at') . " " . ($order ?? 'DESC');

try {
    $stmt = $db->prepare($query);
$stmt->execute($params);
$applicants = $stmt->fetchAll();
$stmt->closeCursor(); // ✅ Close result set

} catch (Exception $e) {
    error_log("Error fetching applicants: " . $e->getMessage());
    $error = "An error occurred while fetching applicants.";
}

admin_header('Manage Applicants');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Applicants</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="bx bx-printer"></i> Print List
                </button>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Name or Email">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                                <option value="last_name" <?php echo $sort === 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                                <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
                                <option value="exam_score" <?php echo $sort === 'exam_score' ? 'selected' : ''; ?>>Exam Score</option>
                                <option value="interview_date" <?php echo $sort === 'interview_date' ? 'selected' : ''; ?>>Interview Date</option>
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
        </div>
    </div>

    <!-- Applicants Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Exam Score</th>
                            <th>Interview Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($applicants)): ?>
                            <?php foreach ($applicants as $applicant): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $applicant['status'] === 'approved' ? 'success' : 
                                                ($applicant['status'] === 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($applicant['status']); ?>
                                        </span>
                                    </td>
                                    <td>
    <?php echo !empty($applicant['exam_score']) ? 
        $applicant['exam_score'] . '%' : '<span class="text-muted">No exam result yet</span>'; ?>
</td>

                                    <td>
    <?php echo !empty($applicant['interview_schedule']) ? 
        date('M d, Y', strtotime($applicant['interview_schedule'])) : '<span class="text-muted">No interview set yet</span>'; ?>
</td>

                                    <td>
                                        <div class="btn-group">
                                            <a href="view_applicant.php?id=<?php echo $applicant['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="applicant_results.php?id=<?php echo $applicant['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="View Results">
                                                <i class="bx bx-bar-chart-alt-2"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No applicants found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
    .sidebar-wrapper, .btn-toolbar, form, .mobile-toggle {
        display: none !important;
    }
    
    .page-content-wrapper {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
</style>

<?php
admin_footer();
?>
