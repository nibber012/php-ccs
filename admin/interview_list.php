<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Build the query
$query = "SELECT i.*, 
                 CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                 u.email as applicant_email,
                 u.contact_number,
                 u.program
          FROM interviews i
          JOIN users u ON i.user_id = u.id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $query .= " AND i.status = ?";
    $params[] = $status;
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
} catch (Exception $e) {
    error_log("Error fetching interviews: " . $e->getMessage());
    $error = "An error occurred while fetching interviews.";
}

admin_header('Interview List');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Interview List</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="schedule_interview.php" class="btn btn-sm btn-primary">
                        <i class="bx bx-calendar-plus"></i> Schedule Interview
                    </a>
                </div>
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
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Name or Email">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-search"></i> Search
                            </button>
                            <a href="interview_list.php" class="btn btn-secondary">
                                <i class="bx bx-reset"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Interviews Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Program</th>
                                    <th>Schedule</th>
                                    <th>Contact</th>
                                    <th>Status</th>
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
                                            <td>
                                                <?php echo date('M d, Y h:i A', strtotime($interview['schedule'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($interview['contact_number']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $interview['status'] === 'completed' ? 'success' : 
                                                        ($interview['status'] === 'scheduled' ? 'info' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($interview['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_interview.php?id=<?php echo $interview['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="View Details">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    <a href="update_interview.php?id=<?php echo $interview['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info"
                                                       title="Update Status">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No interviews found</td>
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
