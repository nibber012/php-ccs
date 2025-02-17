<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../includes/layout.php';
require_once '../includes/utilities.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();;

// Initialize default values
$recent_exams = [];
$exam_stats = [
    'total_exams' => 0,
    'average_score' => 0,
    'highest_score' => 0
];
$available_exams = [];
$status = 'registered';
$user_data = [];

try {
    // Get applicant's exam results
    $stmt = $db->query(
        "SELECT er.*, e.title as exam_title, e.duration_minutes,
                (SELECT SUM(q.points) FROM questions q WHERE q.exam_id = er.exam_id) AS total_points,
                DATE_FORMAT(er.created_at, '%M %d, %Y') as completion_date
         FROM exam_results er
         JOIN exams e ON er.exam_id = e.id
         WHERE er.applicant_id = ?
         ORDER BY er.created_at DESC
         LIMIT 5",
        [$user_id]
    );
        
    $recent_exams = $stmt->fetchAll();

    // Get total exams taken
    $stmt = $db->query(
        "SELECT COUNT(*) as total_exams,
                COALESCE(AVG(score), 0) as average_score,
                COALESCE(MAX(score), 0) as highest_score
         FROM exam_results
         WHERE applicant_id = ?",
        [$user_id]
    );    
    $exam_stats = $stmt->fetch();

    // Get upcoming/available exams
    $stmt = $db->query(
        "SELECT e.* 
         FROM exams e
         LEFT JOIN exam_results er ON er.exam_id = e.id AND er.applicant_id = ?
         WHERE e.status = 'published' AND er.id IS NULL
         LIMIT 3",
        [$user_id]
    );    
    $available_exams = $stmt->fetchAll();

    // Get user data
    $stmt = $db->query(
        "SELECT u.* 
         FROM users u
         WHERE u.id = ?",
        [$user_id]
    );
    
    $user_data = $stmt->fetch();
    
    // If no applicant record exists, create one
    if (empty($user_data['status'])) {
        $db->query(
            "INSERT INTO applicants (user_id, status, created_at) 
             VALUES (?, 'registered', NOW())",
            [$user_id]
        );
        $user_data['status'] = 'registered';
    }

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

// Helper functions for safe string handling
function safe_string($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function safe_ucwords($str) {
    return ucwords(str_replace('_', ' ', $str ?? ''));
}

get_header('Dashboard');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php get_sidebar('applicant'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Welcome, <?php echo safe_string($user_data['first_name'] ?? ''); ?></h1>
            </div>

            <!-- Status Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Application Status</h5>
                            <?php
                            $status = $user_data['status'] ?? 'registered';
                            $status_badges = [
                                'registered' => 'bg-secondary',
                                'screening' => 'bg-primary',
                                'interview_scheduled' => 'bg-warning',
                                'interview_completed' => 'bg-info',
                                'accepted' => 'bg-success',
                                'rejected' => 'bg-danger'
                            ];
                            $badge_class = $status_badges[$status] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $badge_class; ?> p-2">
                                <?php echo safe_ucwords($status); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Total Exams Taken</h6>
                            <h2 class="card-title mb-0"><?php echo (int)($exam_stats['total_exams'] ?? 0); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Average Score</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($exam_stats['average_score'] ?? 0, 1); ?>%</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Highest Score</h6>
                            <h2 class="card-title mb-0"><?php echo number_format($exam_stats['highest_score'] ?? 0, 1); ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Exams -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Recent Exam Results</h5>
                                <a href="results.php" class="btn btn-sm btn-primary">View All Results</a>
                            </div>
                            <?php if (empty($recent_exams)): ?>
                                <p class="text-muted">You haven't taken any exams yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Exam</th>
                                                <th>Score</th>
                                                <th>Completion Time</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($recent_exams as $exam): ?>
    <?php
    $total_points = $exam['total_points'] ?? 1; // Avoid division by zero
    $percentage_score = ($exam['score'] / $total_points) * 100;
    ?>
    <tr>
        <td><?php echo safe_string($exam['exam_title']); ?></td>
        <td>
            <span class="badge <?php echo getScoreBadgeClass($percentage_score); ?>">
                <?php echo number_format($percentage_score, 1); ?>%
            </span>
        </td>
        <td><?php echo number_format($exam['completion_time'] ?? 0, 1); ?> min</td>
        <td><?php echo safe_string($exam['completion_date']); ?></td>
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

            <!-- Available Exams -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Available Exams</h5>
                                <a href="exams.php" class="btn btn-sm btn-primary">View All Exams</a>
                            </div>
                            <?php if (empty($available_exams)): ?>
                                <p class="text-muted">No exams are currently available.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($available_exams as $exam): ?>
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo safe_string($exam['title']); ?></h6>
                                                    <p class="card-text small"><?php echo safe_string($exam['description']); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="bx bx-time"></i> <?php echo (int)($exam['duration_minutes'] ?? 0); ?> minutes
                                                        </small>
                                                        <a href="exams.php?exam_id=<?php echo (int)($exam['id'] ?? 0); ?>" class="btn btn-sm btn-primary">
                                                            Start Exam
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.badge {
    padding: 0.5rem 1rem;
}

.badge-excellent { background-color: #27ae60; }
.badge-good { background-color: #2980b9; }
.badge-average { background-color: #f39c12; }
.badge-poor { background-color: #c0392b; }
</style>

<?php get_footer(); ?>
