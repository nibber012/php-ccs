<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';
require_once './includes/layout.php';

// Initialize Auth and Database
$auth = new Auth();
$auth->requireRole('admin');
$db = Database::getInstance();;

// Get interview ID from URL
$interview_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$interview_id) {
    header('Location: interview_list.php');
    exit;
}

try {
    // Fetch interview details
    $stmt = $db->query(
        "SELECT i.*, 
                CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                u.email as applicant_email,
                u.contact_number,
                u.program,
                CONCAT(a.first_name, ' ', a.last_name) as interviewer_name,
                a.email as interviewer_email
         FROM interviews i
         JOIN users u ON i.user_id = u.id
         LEFT JOIN users a ON i.created_by = a.id
         WHERE i.id = ?",
        [$interview_id]
    );
    $interview = $stmt->fetch();

    if (!$interview) {
        throw new Exception('Interview not found.');
    }

    // Fetch interview history
    $stmt = $db->query(
        "SELECT ih.*,
                CONCAT(u.first_name, ' ', u.last_name) as updated_by,
                u.email as updated_by_email
         FROM interview_history ih
         JOIN users u ON ih.created_by = u.id
         WHERE ih.interview_id = ?
         ORDER BY ih.created_at DESC",
        [$interview_id]
    );
    $history = $stmt->fetchAll();

    // Fetch exam results for context
    $stmt = $db->query(
        "SELECT er.*, e.exam_title
         FROM exam_results er
         JOIN exams e ON er.exam_id = e.id
         WHERE er.user_id = ?
         ORDER BY er.created_at DESC",
        [$interview['user_id']]
    );
    $exam_results = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error in view_interview.php: " . $e->getMessage());
    $error = $e->getMessage();
}

admin_header('View Interview');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once './includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Interview Details</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="interview_list.php">Interviews</a></li>
                        <li class="breadcrumb-item active">View Interview</li>
                    </ol>
                </nav>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <!-- Interview Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Interview Information</h5>
                                <?php if ($interview['status'] !== 'completed'): ?>
                                    <a href="update_interview.php?id=<?php echo $interview_id; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="bx bx-edit"></i> Update Status
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>Schedule:</strong><br>
                                        <?php echo date('F d, Y h:i A', strtotime($interview['schedule'])); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Status:</strong><br>
                                        <span class="badge bg-<?php 
                                            echo $interview['status'] === 'completed' ? 'success' : 
                                                ($interview['status'] === 'scheduled' ? 'info' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($interview['status']); ?>
                                        </span>
                                    </p>
                                    <?php if ($interview['status'] === 'completed'): ?>
                                        <p class="mb-1">
                                            <strong>Recommendation:</strong><br>
                                            <span class="badge bg-<?php 
                                                echo $interview['recommendation'] === 'hire' ? 'success' : 
                                                    ($interview['recommendation'] === 'reject' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($interview['recommendation']); ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>Interviewer:</strong><br>
                                        <?php echo htmlspecialchars($interview['interviewer_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($interview['interviewer_email']); ?></small>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($interview['notes'])): ?>
                                <hr>
                                <div class="mt-3">
                                    <h6>Interview Notes</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($interview['notes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Interview History -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Interview History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($history)): ?>
                                <p class="text-muted">No history available.</p>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($history as $record): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <h6 class="timeline-title">
                                                    Status changed to: <?php echo ucfirst($record['status']); ?>
                                                </h6>
                                                <p class="timeline-text"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                                                <p class="timeline-date text-muted">
                                                    <small>
                                                        By <?php echo htmlspecialchars($record['updated_by']); ?> on
                                                        <?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?>
                                                    </small>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Applicant Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Applicant Information</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($interview['applicant_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Email:</strong><br>
                                <?php echo htmlspecialchars($interview['applicant_email']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Contact:</strong><br>
                                <?php echo htmlspecialchars($interview['contact_number']); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Program:</strong><br>
                                <?php echo htmlspecialchars($interview['program']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Exam Results -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Exam Results</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($exam_results)): ?>
                                <p class="text-muted">No exam results found.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($exam_results as $result): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($result['exam_title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($result['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1">Score: <?php echo $result['score']; ?>%</p>
                                            <small class="text-<?php echo $result['status'] === 'passed' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($result['status']); ?>
                                            </small>
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
    margin-bottom: 1.5rem;
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
}

/* Timeline styles */
.timeline {
    position: relative;
    padding: 1rem 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 2px;
    height: 100%;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-left: 2.5rem;
    padding-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 2px solid #fff;
}

.timeline-content {
    padding: 0.5rem 0;
}

.timeline-title {
    margin-bottom: 0.5rem;
}

.timeline-date {
    margin-top: 0.5rem;
}

.list-group-item {
    border: 1px solid rgba(0,0,0,.125);
}

@media print {
    .sidebar-wrapper {
        display: none !important;
    }
    
    .col-md-9 {
        width: 100% !important;
        margin: 0 !important;
    }
}
</style>

<?php admin_footer(); ?>
