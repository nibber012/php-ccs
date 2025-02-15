<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // Get application statistics
    $stats_query = "SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN u.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
        SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active_applications,
        SUM(CASE WHEN u.status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications,
        SUM(CASE WHEN a.progress_status = 'registered' THEN 1 ELSE 0 END) as registered,
        SUM(CASE WHEN a.progress_status = 'exam_scheduled' THEN 1 ELSE 0 END) as exam_scheduled,
        SUM(CASE WHEN a.progress_status = 'exam_completed' THEN 1 ELSE 0 END) as exam_completed,
        SUM(CASE WHEN a.progress_status = 'interview_scheduled' THEN 1 ELSE 0 END) as interview_scheduled,
        SUM(CASE WHEN a.progress_status = 'interview_completed' THEN 1 ELSE 0 END) as interview_completed,
        SUM(CASE WHEN a.progress_status = 'accepted' THEN 1 ELSE 0 END) as accepted
    FROM applicants a
    JOIN users u ON a.user_id = u.id
    WHERE u.role = 'applicant'";

    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // Get recent activities
    $activities_query = "SELECT 
        ua.activity_type,
        ua.description,
        ua.created_at,
        u.email as actor_email,
        CONCAT(a.first_name, ' ', a.last_name) as actor_name
    FROM user_activities ua
    JOIN users u ON ua.user_id = u.id
    LEFT JOIN admins a ON u.id = a.user_id
    WHERE ua.activity_type LIKE 'applicant_%'
    ORDER BY ua.created_at DESC
    LIMIT 10";

    $activities_stmt = $conn->prepare($activities_query);
    $activities_stmt->execute();
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get applications by course
    $courses_query = "SELECT 
        preferred_course,
        COUNT(*) as count
    FROM applicants
    GROUP BY preferred_course
    ORDER BY count DESC";

    $courses_stmt = $conn->prepare($courses_query);
    $courses_stmt->execute();
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Application Status Dashboard';
admin_header($page_title);
?>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include_once '../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div class="page-content-wrapper">
        <div class="container-fluid px-4">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mt-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Applicants</li>
                    <li class="breadcrumb-item active" aria-current="page">Application Status</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="bi bi-clipboard-data me-2"></i>Application Status Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-primary shadow h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-uppercase mb-1 text-primary fw-bold small">
                                        Total Applications
                                    </div>
                                    <div class="h3 mb-0 fw-bold"><?php echo number_format($stats['total_applications']); ?></div>
                                    <div class="text-muted small mt-2">
                                        All time applications
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-file-earmark-text display-6 text-primary opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-warning shadow h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-uppercase mb-1 text-warning fw-bold small">
                                        Pending Review
                                    </div>
                                    <div class="h3 mb-0 fw-bold"><?php echo number_format($stats['pending_applications']); ?></div>
                                    <div class="text-muted small mt-2">
                                        Awaiting review
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-hourglass-split display-6 text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-success shadow h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-uppercase mb-1 text-success fw-bold small">
                                        Active Applications
                                    </div>
                                    <div class="h3 mb-0 fw-bold"><?php echo number_format($stats['active_applications']); ?></div>
                                    <div class="text-muted small mt-2">
                                        In progress
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-check display-6 text-success opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start-info shadow h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="text-uppercase mb-1 text-info fw-bold small">
                                        Accepted Applicants
                                    </div>
                                    <div class="h3 mb-0 fw-bold"><?php echo number_format($stats['accepted']); ?></div>
                                    <div class="text-muted small mt-2">
                                        Successfully accepted
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-trophy display-6 text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Status and Course Distribution -->
            <div class="row">
                <!-- Application Progress -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary bg-gradient text-white">
                            <h6 class="m-0 fw-bold">
                                <i class="bi bi-graph-up me-2"></i>Application Progress
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="progress-timeline">
                                <?php
                                $progress_stages = [
                                    'registered' => ['Registered', $stats['registered'], 'bi-person-plus'],
                                    'exam_scheduled' => ['Exam Scheduled', $stats['exam_scheduled'], 'bi-calendar-event'],
                                    'exam_completed' => ['Exam Completed', $stats['exam_completed'], 'bi-journal-check'],
                                    'interview_scheduled' => ['Interview Scheduled', $stats['interview_scheduled'], 'bi-camera-video'],
                                    'interview_completed' => ['Interview Completed', $stats['interview_completed'], 'bi-chat-right-text'],
                                    'accepted' => ['Accepted', $stats['accepted'], 'bi-check-circle']
                                ];

                                foreach ($progress_stages as $stage => $info):
                                    $percentage = $stats['total_applications'] > 0 
                                        ? round(($info[1] / $stats['total_applications']) * 100) 
                                        : 0;
                                ?>
                                <div class="progress-item mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <i class="bi <?php echo $info[2]; ?> me-2"></i>
                                            <strong><?php echo $info[0]; ?></strong>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo $info[1]; ?> applicants
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%"
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <div class="text-end mt-1">
                                        <span class="badge bg-primary"><?php echo $percentage; ?>%</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applications by Course -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-success bg-gradient text-white">
                            <h6 class="m-0 fw-bold">
                                <i class="bi bi-mortarboard-fill me-2"></i>Applications by Course
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="course-distribution">
                                <?php if (empty($courses)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-bar-chart display-1"></i>
                                        <p class="mt-3">No course data available</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($courses as $course): ?>
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0">
                                                <i class="bi bi-book me-2"></i>
                                                <?php echo htmlspecialchars($course['preferred_course']); ?>
                                            </h6>
                                            <span class="badge bg-success">
                                                <?php echo $course['count']; ?> applicants
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <?php 
                                            $course_percentage = $stats['total_applications'] > 0 
                                                ? round(($course['count'] / $stats['total_applications']) * 100) 
                                                : 0;
                                            ?>
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $course_percentage; ?>%" 
                                                 aria-valuenow="<?php echo $course_percentage; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="text-end mt-1">
                                            <small class="text-muted"><?php echo $course_percentage; ?>% of total</small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-warning bg-gradient text-dark">
                            <h6 class="m-0 fw-bold">
                                <i class="bi bi-activity me-2"></i>Recent Application Activities
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activities)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history display-1"></i>
                                    <p class="mt-3">No recent activities</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><i class="bi bi-tag me-2"></i>Activity</th>
                                                <th><i class="bi bi-card-text me-2"></i>Description</th>
                                                <th><i class="bi bi-person me-2"></i>Performed By</th>
                                                <th><i class="bi bi-calendar me-2"></i>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $activity_class = match($activity['activity_type']) {
                                                        'applicant_approved' => ['success', 'bi-check-circle-fill'],
                                                        'applicant_rejected' => ['danger', 'bi-x-circle-fill'],
                                                        'applicant_registered' => ['info', 'bi-person-plus-fill'],
                                                        default => ['secondary', 'bi-arrow-right-circle-fill']
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $activity_class[0]; ?> p-2">
                                                        <i class="bi <?php echo $activity_class[1]; ?> me-1"></i>
                                                        <?php echo ucwords(str_replace('_', ' ', $activity['activity_type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                                <td>
                                                    <?php if (!empty($activity['actor_name'])): ?>
                                                        <div>
                                                            <i class="bi bi-person-circle me-1"></i>
                                                            <?php echo htmlspecialchars($activity['actor_name']); ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-envelope me-1"></i>
                                                            <?php echo htmlspecialchars($activity['actor_email']); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <i class="bi bi-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($activity['actor_email']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <i class="bi bi-calendar2-event me-1"></i>
                                                        <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock me-1"></i>
                                                        <?php echo date('h:i A', strtotime($activity['created_at'])); ?>
                                                    </small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
.border-start-primary {
    border-left: 4px solid #4e73df !important;
}
.border-start-success {
    border-left: 4px solid #1cc88a !important;
}
.border-start-warning {
    border-left: 4px solid #f6c23e !important;
}
.border-start-info {
    border-left: 4px solid #36b9cc !important;
}
.progress {
    background-color: rgba(0,0,0,0.05);
    border-radius: 1rem;
}
.progress-bar {
    border-radius: 1rem;
}
.card {
    border: none;
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-2px);
}
.card-header {
    border-bottom: none;
}
.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}
.badge {
    font-weight: 500;
}
.text-muted {
    opacity: 0.75;
}
</style>

<?php admin_footer(); ?>
