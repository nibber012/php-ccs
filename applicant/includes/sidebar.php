<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/utilities.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();;

try {
    // Get user data
    $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$user_id]);
    $user = $stmt->fetch();
    
    // Get applicant data
    $stmt = $db->query("SELECT status FROM applicants WHERE user_id = ?", [$user_id]);
    $applicant = $stmt->fetch();
    
    if (!$applicant) {
        $db->query("INSERT INTO applicants (user_id, status) VALUES (?, 'registered')", [$user_id]);
        $applicant = ['status' => 'registered'];
    }

    // Get exam stats
    $stmt = $db->query(
        "SELECT COUNT(*) as total_exams, AVG(score) as avg_score 
         FROM exam_results WHERE user_id = ?",
        [$user_id]
    );
    $exam_stats = $stmt->fetch();
} catch (Exception $e) {
    error_log("Sidebar Error: " . $e->getMessage());
    $user = ['first_name' => 'User', 'last_name' => ''];
    $applicant = ['status' => 'registered'];
    $exam_stats = ['total_exams' => 0, 'avg_score' => 0];
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar">
    <!-- Brand Logo -->
    <div class="brand-link">
        <span class="brand-text">CCS Screening</span>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel">
            <div class="info">
                <div class="user-name"><?php echo safe_string($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <div class="user-role">Applicant</div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="sidebar-menu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>applicant/dashboard.php" 
                       class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="bx bxs-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>applicant/profile.php" 
                       class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                        <i class="bx bxs-user"></i>
                        <span>My Profile</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>applicant/exams.php" 
                       class="nav-link <?php echo $current_page === 'exams.php' ? 'active' : ''; ?>">
                        <i class="bx bxs-book"></i>
                        <span>Exams</span>
                        <?php if ($exam_stats['total_exams'] > 0): ?>
                            <span class="badge bg-info rounded-pill ms-2">
                                <?php echo $exam_stats['total_exams']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>applicant/results.php" 
                       class="nav-link <?php echo $current_page === 'results.php' ? 'active' : ''; ?>">
                        <i class="bx bxs-bar-chart-alt-2"></i>
                        <span>My Results</span>
                        <?php if ($exam_stats['avg_score'] > 0): ?>
                            <span class="badge <?php echo getScoreBadgeClass($exam_stats['avg_score']); ?> rounded-pill ms-2">
                                <?php echo number_format($exam_stats['avg_score'], 1); ?>%
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>applicant/support.php" 
                       class="nav-link <?php echo $current_page === 'support.php' ? 'active' : ''; ?>">
                        <i class="bx bxs-help-circle"></i>
                        <span>Help & Support</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Application Status -->
        <div class="application-status">
            <div class="status-card">
                <h6>Application Status</h6>
                <?php
                $status = $applicant['status'];
                $status_badges = [
                    'registered' => 'status-badge-secondary',
                    'screening' => 'status-badge-primary',
                    'interview_scheduled' => 'status-badge-warning',
                    'interview_completed' => 'status-badge-info',
                    'accepted' => 'status-badge-success',
                    'rejected' => 'status-badge-danger'
                ];
                $badge_class = $status_badges[$status] ?? 'status-badge-secondary';
                ?>
                <span class="status-badge <?php echo $badge_class; ?>">
                    <?php echo safe_ucwords(str_replace('_', ' ', $status)); ?>
                </span>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn-logout">
                <i class="bx bx-log-out"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </div>
</aside>

<style>
.main-sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: #2c3e50;
    color: #ecf0f1;
    z-index: 1000;
    transition: all 0.3s ease;
}

.brand-link {
    padding: 20px;
    text-align: center;
    background: #34495e;
    border-bottom: 1px solid #46627f;
}

.brand-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: #fff;
}

.user-panel {
    padding: 20px;
    border-bottom: 1px solid #46627f;
}

.user-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.user-role {
    font-size: 0.875rem;
    color: #bdc3c7;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #ecf0f1;
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: #34495e;
    color: #fff;
}

.nav-link.active {
    background: #3498db;
    color: #fff;
}

.nav-link i {
    margin-right: 10px;
    font-size: 1.25rem;
}

.application-status {
    padding: 20px;
    margin-top: auto;
}

.status-card {
    background: #34495e;
    padding: 15px;
    border-radius: 8px;
}

.status-card h6 {
    margin-bottom: 10px;
    color: #bdc3c7;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.875rem;
}

.status-badge-secondary { background: #95a5a6; }
.status-badge-primary { background: #3498db; }
.status-badge-warning { background: #f1c40f; color: #2c3e50; }
.status-badge-info { background: #2980b9; }
.status-badge-success { background: #27ae60; }
.status-badge-danger { background: #c0392b; }

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid #46627f;
}

.btn-logout {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 10px;
    background: #c0392b;
    color: #fff;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    background: #e74c3c;
    color: #fff;
}

.btn-logout i {
    margin-right: 8px;
}

.badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
}

.badge-excellent { background-color: #27ae60 !important; }
.badge-good { background-color: #2980b9 !important; }
.badge-average { background-color: #f39c12 !important; }
.badge-poor { background-color: #c0392b !important; }
</style>
