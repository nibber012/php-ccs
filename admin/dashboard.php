<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/admin_layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance()->getConnection();

// Get current user
$user = $auth->getCurrentUser();
// Get today's interviews
$today_interviews = 0;
$today_query = "SELECT COUNT(*) as count FROM interview_schedules WHERE DATE(schedule_date) = CURDATE()";
$today_result = $db->query($today_query)->fetch();
if ($today_result) {
    $today_interviews = $today_result['count'];
}

$stmt = $db->query("SELECT * FROM users LIMIT 1");
$test = $stmt->fetch();
error_log("Dashboard loaded successfully for user ID: " . ($_SESSION['user_id'] ?? 'UNKNOWN'));



try {
    // Get total applicants
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'applicant'");
    $total_applicants = $stmt->fetch()['count'];

    // Get pending applications
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'applicant' AND status = 'pending'");
    $pending_applications = $stmt->fetch()['count'];

    // Get recent exam results
    $stmt = $db->query(
        "SELECT er.*,
            CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
            e.title as exam_title,
            u.email as applicant_email
         FROM exam_results er
         JOIN users u ON er.applicant_id = u.id  -- ✅ Changed user_id to applicant_id
         JOIN exams e ON er.exam_id = e.id
         ORDER BY er.created_at DESC
         LIMIT 5"
    );
    
    $recent_results = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error in admin dashboard: " . $e->getMessage());
    $error = "An error occurred while fetching dashboard data.";
}

admin_header('Admin Dashboard');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class='bx bx-printer'></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Applicants</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_applicants; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Applications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_applications; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-time fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Today's Interviews</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $today_interviews; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bx bx-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Exam Results -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Exam Results</h6>
                    <a href="exam_results.php" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Exam</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_results)): ?>
                                    <?php foreach ($recent_results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['applicant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                            <td><?php echo $result['score']; ?>%</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $result['status'] === 'pass' ? 'success' : 'danger'; 
                                                ?>">
                                                    <?php echo ucfirst($result['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($result['created_at'])); ?></td>
                                            <td>
                                                <a href="view_result.php?id=<?php echo $result['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent exam results</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }

.card-body {
    padding: 1.25rem;
}

.text-xs {
    font-size: .7rem;
}

.text-gray-800 {
    color: #5a5c69!important;
}

@media print {
    .btn-toolbar, .mobile-toggle {
        display: none !important;
    }
}
</style>

<?php
admin_footer();
?>
