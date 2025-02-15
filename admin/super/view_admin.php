<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';
require_once '../../config/database.php';

// Initialize Auth and Database
$auth = new Auth();
$db = Database::getInstance();;

// Check if user is logged in and is a super admin
$user = $auth->getCurrentUser();
if (!$user || $user['role'] !== 'super_admin') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Get admin ID from URL
$admin_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // First get the super admin details
    $query = "SELECT sa.*, u.email, u.role, u.status 
              FROM super_admins sa
              INNER JOIN users u ON u.id = sa.user_id 
              WHERE sa.id = ?";
    $stmt = $db->query($query, [$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        // Try regular admin table
        $query = "SELECT a.*, u.email, u.role, u.status 
                 FROM admins a
                 INNER JOIN users u ON u.id = a.user_id 
                 WHERE a.id = ?";
        $stmt = $db->query($query, [$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get activity statistics
    if ($admin) {
        // Get processed applications count
        $query = "SELECT COUNT(*) as processed_applications 
                 FROM applicants 
                 WHERE processed_by = ?";
        $stmt = $db->query($query, [$admin['user_id']]);
        $applications = $stmt->fetch(PDO::FETCH_ASSOC);
        $admin['processed_applications'] = $applications['processed_applications'] ?? 0;

        // Get conducted interviews count
        $query = "SELECT COUNT(*) as conducted_interviews 
                 FROM interview_schedules 
                 WHERE interviewer_id = ?";
        $stmt = $db->query($query, [$admin['user_id']]);
        $interviews = $stmt->fetch(PDO::FETCH_ASSOC);
        $admin['conducted_interviews'] = $interviews['conducted_interviews'] ?? 0;

        // Get recent activity logs
        $query = "SELECT * FROM activity_logs 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 5";
        $stmt = $db->query($query, [$admin['user_id']]);
        $activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "Error fetching admin details: " . $e->getMessage();
}

// If admin not found, redirect to list
if (!$admin) {
    header('Location: ' . BASE_URL . 'admin/super/list_admins.php');
    exit;
}

// Include header
require_once '../includes/layout.php';
admin_header('View Admin Details');
?>

<div class="container-fluid">
    <?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Admin Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="list_admins.php">Admin Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Admin</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="edit_admin.php?id=<?php echo $admin_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Admin
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Admin Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Full Name</h6>
                        </div>
                        <div class="col-sm-9">
                            <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Email</h6>
                        </div>
                        <div class="col-sm-9">
                            <?php echo htmlspecialchars($admin['email']); ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Role</h6>
                        </div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php echo $admin['role'] === 'super_admin' ? 'primary' : 'info'; ?>">
                                <?php echo ucfirst($admin['role']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">
                            <h6 class="mb-0">Status</h6>
                        </div>
                        <div class="col-sm-9">
                            <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($admin['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Activity Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activity Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stat-icon bg-primary text-white rounded-circle p-3 me-3">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Processed Applications</h6>
                            <h4 class="mb-0"><?php echo number_format($admin['processed_applications']); ?></h4>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success text-white rounded-circle p-3 me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Conducted Interviews</h6>
                            <h4 class="mb-0"><?php echo number_format($admin['conducted_interviews']); ?></h4>
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
                    <?php if (empty($activity_logs)): ?>
                    <p class="text-muted mb-0">No recent activity found.</p>
                    <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activity_logs as $log): ?>
                        <div class="timeline-item">
                            <div class="timeline-date text-muted">
                                <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                            </div>
                            <div class="timeline-content">
                                <p class="mb-0"><?php echo htmlspecialchars($log['details']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -1.5rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item:after {
    content: '';
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: #007bff;
}

.timeline-date {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}
</style>

<?php
// Include footer
admin_footer();
?>
