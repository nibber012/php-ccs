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
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Get overall status counts
    $query = "SELECT 
                progress_status,
                COUNT(*) as count,
                preferred_course
              FROM applicants
              GROUP BY progress_status, preferred_course
              ORDER BY preferred_course, progress_status";
    $stmt = $conn->query($query);
    $status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process data for display
    $status_overview = [
        'BSCS' => [
            'registered' => 0,
            'part1_pending' => 0,
            'part1_completed' => 0,
            'part2_pending' => 0,
            'part2_completed' => 0,
            'interview_pending' => 0,
            'interview_completed' => 0,
            'passed' => 0,
            'failed' => 0,
            'total' => 0
        ],
        'BSIT' => [
            'registered' => 0,
            'part1_pending' => 0,
            'part1_completed' => 0,
            'part2_pending' => 0,
            'part2_completed' => 0,
            'interview_pending' => 0,
            'interview_completed' => 0,
            'passed' => 0,
            'failed' => 0,
            'total' => 0
        ]
    ];

    foreach ($status_data as $data) {
        if (isset($status_overview[$data['preferred_course']])) {
            $status_overview[$data['preferred_course']][$data['progress_status']] = $data['count'];
            $status_overview[$data['preferred_course']]['total'] += $data['count'];
        }
    }

    // Get recent status changes
    $query = "SELECT 
                a.id,
                a.first_name,
                a.last_name,
                a.preferred_course,
                a.progress_status,
                a.updated_at
              FROM applicants a
              ORDER BY a.updated_at DESC
              LIMIT 10";
    $stmt = $conn->query($query);
    $recent_changes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('Applicant Status Overview');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Applicant Status Overview</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Status Overview Cards -->
    <div class="row">
        <?php foreach ($status_overview as $course => $statuses): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($course); ?> Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statuses as $status => $count): ?>
                                        <?php if ($status !== 'total'): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($status) {
                                                            'registered' => 'secondary',
                                                            'part1_pending', 'part2_pending', 'interview_pending' => 'warning',
                                                            'part1_completed', 'part2_completed', 'interview_completed' => 'info',
                                                            'passed' => 'success',
                                                            'failed' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $count; ?></td>
                                                <td>
                                                    <?php 
                                                    if ($statuses['total'] > 0) {
                                                        echo round(($count / $statuses['total']) * 100, 1) . '%';
                                                    } else {
                                                        echo '0%';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td><strong>Total Applicants</strong></td>
                                        <td><strong><?php echo $statuses['total']; ?></strong></td>
                                        <td><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Status Changes -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Recent Status Changes</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Course</th>
                            <th>Current Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_changes as $change): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($change['first_name'] . ' ' . $change['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($change['preferred_course']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($change['progress_status']) {
                                            'registered' => 'secondary',
                                            'part1_pending', 'part2_pending', 'interview_pending' => 'warning',
                                            'part1_completed', 'part2_completed', 'interview_completed' => 'info',
                                            'passed' => 'success',
                                            'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $change['progress_status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($change['updated_at'])); ?></td>
                                <td>
                                    <a href="view_applicant.php?id=<?php echo $change['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_changes)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No recent status changes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
admin_footer();
?>
