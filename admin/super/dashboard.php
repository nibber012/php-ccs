<?php
require_once '../../config/config.php';
require_once '../../middleware/SessionManager.php';
require_once '../../classes/Auth.php';
require_once '../../config/Database.php';
require_once '../includes/layout.php'; 

// Start session first
SessionManager::start();

// Initialize Auth
$auth = new Auth();

// Check authentication and role
try {
    // This will redirect to login if not authenticated
    if (!$auth->requireRole('super_admin')) {
        exit(); // requireRole will handle the redirect
    }
    
    // Get user data after confirming authentication
    $user = $auth->getCurrentUser();
    if (!$user) {
        throw new Exception('User data not found');
    }
    
    // Continue with rest of dashboard code
    $error = '';
    $success = '';

    
    try {
        $conn = Database::getInstance()->getConnection();

        // Get statistics
        $stats = [
            'total_applicants' => 0,
            'passed_exams' => 0,
            'pending_interviews' => 0,
            'active_admins' => 0
        ];

        // Total applicants
        $query = "SELECT COUNT(*) FROM applicants";
        $stmt = $conn->query($query);
        $stats['total_applicants'] = $stmt->fetchColumn();

        // Passed exams (both parts)
        $query = "SELECT COUNT(DISTINCT a.id) 
                  FROM applicants a
                  JOIN exam_results er1 ON a.id = er1.applicant_id
                  JOIN exams e1 ON er1.exam_id = e1.id AND e1.part = '1'
                  JOIN exam_results er2 ON a.id = er2.applicant_id
                  JOIN exams e2 ON er2.exam_id = e2.id AND e2.part = '2'
                  WHERE er1.status = 'pass' AND er2.status = 'pass'";
        $stmt = $conn->query($query);
        $stats['passed_exams'] = $stmt->fetchColumn();

        // Pending interviews
        $query = "SELECT COUNT(*) FROM interview_schedules WHERE status = 'scheduled'";
        $stmt = $conn->query($query);
        $stats['pending_interviews'] = $stmt->fetchColumn();

        // Active admins
        $query = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'";
        $stmt = $conn->query($query);
        $stats['active_admins'] = $stmt->fetchColumn();

        // Get recent activities
        $query = "SELECT al.*, 
                  CONCAT(u.first_name, ' ', u.last_name) as user_name,
                  DATE_FORMAT(al.created_at, '%M %d, %Y %h:%i %p') as formatted_date
                  FROM activity_logs al
                  JOIN users u ON al.user_id = u.id
                  ORDER BY al.created_at DESC
                  LIMIT 5";
        $stmt = $conn->query($query);
        $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Handle export report
        if (isset($_POST['export_report'])) {
            // Generate CSV content
            $csv_content = "CCS Screening System - Summary Report\n";
            $csv_content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Statistics
            $csv_content .= "STATISTICS\n";
            $csv_content .= "Total Applicants," . $stats['total_applicants'] . "\n";
            $csv_content .= "Passed Exams," . $stats['passed_exams'] . "\n";
            $csv_content .= "Pending Interviews," . $stats['pending_interviews'] . "\n";
            $csv_content .= "Active Admins," . $stats['active_admins'] . "\n\n";

            // Applicant Status Breakdown
            $query = "SELECT progress_status, COUNT(*) as count 
                     FROM applicants 
                     GROUP BY progress_status";
            $stmt = $conn->query($query);
            $status_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $csv_content .= "APPLICANT STATUS BREAKDOWN\n";
            foreach ($status_breakdown as $status) {
                $csv_content .= ucwords(str_replace('_', ' ', $status['progress_status'])) . "," . $status['count'] . "\n";
            }
            $csv_content .= "\n";

            // Exam Statistics
            $query = "SELECT e.part, e.title, 
                            COUNT(er.id) as total_attempts,
                            SUM(CASE WHEN er.status = 'pass' THEN 1 ELSE 0 END) as passed,
                            AVG(er.score) as average_score
                     FROM exams e
                     LEFT JOIN exam_results er ON e.id = er.exam_id
                     GROUP BY e.id";
            $stmt = $conn->query($query);
            $exam_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $csv_content .= "EXAM STATISTICS\n";
            $csv_content .= "Part,Title,Total Attempts,Passed,Pass Rate,Average Score\n";
            foreach ($exam_stats as $exam) {
                $pass_rate = $exam['total_attempts'] > 0 ? ($exam['passed'] / $exam['total_attempts'] * 100) : 0;
                $csv_content .= $exam['part'] . ",";
                $csv_content .= $exam['title'] . ",";
                $csv_content .= $exam['total_attempts'] . ",";
                $csv_content .= $exam['passed'] . ",";
                $csv_content .= number_format($pass_rate, 2) . "%,";
                $csv_content .= number_format($exam['average_score'], 2) . "\n";
            }

            // Set headers for download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="ccs_screening_report_' . date('Y-m-d') . '.csv"');
            echo $csv_content;
            exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try logging in again.';
    $auth->logout();
    header('Location: /php-ccs/login.php');
    exit();
}

// Start the page
admin_header('Super Admin Dashboard');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Super Admin Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <form method="post" class="me-2">
                <button type="submit" name="export_report" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download"></i> Export Report
                </button>
            </form>
            <a href="create_admin.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Create Admin Account
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Applicants</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_applicants']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Passed Exams</h5>
                    <h2 class="card-text"><?php echo number_format($stats['passed_exams']); ?></h2>
                    <?php if ($stats['total_applicants'] > 0): ?>
                        <small><?php echo number_format(($stats['passed_exams'] / $stats['total_applicants']) * 100, 1); ?>% success rate</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title">Pending Interviews</h5>
                    <h2 class="card-text"><?php echo number_format($stats['pending_interviews']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Active Admins</h5>
                    <h2 class="card-text"><?php echo number_format($stats['active_admins']); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Recent Activity</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_activities)): ?>
                <p class="text-center text-muted my-3">No recent activities found.</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                <small class="text-muted"><?php echo $activity['formatted_date']; ?></small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($activity['details']); ?></p>
                            <small class="text-muted">
                                By: <?php echo htmlspecialchars($activity['user_name']); ?>
                                <?php if ($activity['ip_address']): ?>
                                    from <?php echo htmlspecialchars($activity['ip_address']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
admin_footer();
?>
