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

    // Handle applicant approval/rejection
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['applicant_id'])) {
            $applicant_id = $_POST['applicant_id'];
            $action = $_POST['action'];

            // Start transaction
            $conn->beginTransaction();

            try {
                // Update user status
                $status = ($action === 'approve') ? 'active' : 'rejected';
                $query = "UPDATE users u 
                         JOIN applicants a ON u.id = a.user_id 
                         SET u.status = ? 
                         WHERE a.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$status, $applicant_id]);

                // Update applicant progress status if approved
                if ($action === 'approve') {
                    $query = "UPDATE applicants 
                             SET progress_status = 'registered' 
                             WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$applicant_id]);
                }

                // Log the action
                $auth->logActivity(
                    $user['id'],
                    'applicant_' . $action,
                    "Applicant (ID: $applicant_id) has been " . ($action === 'approve' ? 'approved' : 'rejected')
                );

                $conn->commit();
                $success = 'Applicant has been ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully';
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        }
    }

    // Get all applicants with filters
    $status_filter = $_GET['status'] ?? 'all';
    $progress_filter = $_GET['progress'] ?? 'all';
    
    $query = "SELECT 
                a.id,
                a.first_name,
                a.last_name,
                u.email,
                a.contact_number,
                a.preferred_course,
                u.status,
                a.progress_status,
                u.created_at as registration_date
              FROM applicants a
              JOIN users u ON a.user_id = u.id
              WHERE u.role = 'applicant'";

    if ($status_filter !== 'all') {
        $query .= " AND u.status = :status";
    }
    if ($progress_filter !== 'all') {
        $query .= " AND a.progress_status = :progress";
    }
    
    $query .= " ORDER BY u.created_at DESC";

    $stmt = $conn->prepare($query);
    
    if ($status_filter !== 'all') {
        $stmt->bindParam(':status', $status_filter);
    }
    if ($progress_filter !== 'all') {
        $stmt->bindParam(':progress', $progress_filter);
    }
    
    $stmt->execute();
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Manage Applicants';
admin_header($page_title);
?>

<div class="wrapper">
    <!-- Sidebar -->
    <?php include_once '../includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div class="page-content-wrapper">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><?php echo $page_title; ?></h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <!-- Filter Buttons -->
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bx bx-filter'></i> Status Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item <?php echo $status_filter === 'all' ? 'active' : ''; ?>" href="?status=all&progress=<?php echo $progress_filter; ?>">All Status</a></li>
                                    <li><a class="dropdown-item <?php echo $status_filter === 'pending' ? 'active' : ''; ?>" href="?status=pending&progress=<?php echo $progress_filter; ?>">Pending</a></li>
                                    <li><a class="dropdown-item <?php echo $status_filter === 'active' ? 'active' : ''; ?>" href="?status=active&progress=<?php echo $progress_filter; ?>">Active</a></li>
                                    <li><a class="dropdown-item <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected&progress=<?php echo $progress_filter; ?>">Rejected</a></li>
                                </ul>
                            </div>
                            <div class="btn-group me-2">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bx bx-loader'></i> Progress Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'all' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=all">All Progress</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'registered' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=registered">Registered</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'exam_scheduled' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=exam_scheduled">Exam Scheduled</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'exam_completed' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=exam_completed">Exam Completed</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'interview_scheduled' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=interview_scheduled">Interview Scheduled</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'interview_completed' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=interview_completed">Interview Completed</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'accepted' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=accepted">Accepted</a></li>
                                    <li><a class="dropdown-item <?php echo $progress_filter === 'rejected' ? 'active' : ''; ?>" href="?status=<?php echo $status_filter; ?>&progress=rejected">Rejected</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($error)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Contact</th>
                                            <th>Course</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($applicants)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No applicants found</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($applicants as $applicant): ?>
                                            <tr>
                                                <td><?php echo $applicant['id']; ?></td>
                                                <td><?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['contact_number']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['preferred_course']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = match($applicant['status']) {
                                                        'active' => 'success',
                                                        'pending' => 'warning',
                                                        'rejected' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($applicant['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $progress_class = match($applicant['progress_status']) {
                                                        'registered' => 'info',
                                                        'exam_scheduled' => 'primary',
                                                        'exam_completed' => 'success',
                                                        'interview_scheduled' => 'warning',
                                                        'interview_completed' => 'success',
                                                        'accepted' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $progress_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $applicant['progress_status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($applicant['registration_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view_applicant.php?id=<?php echo $applicant['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="View Details">
                                                            <i class='bx bx-show'></i>
                                                        </a>
                                                        <?php if ($applicant['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to approve this applicant?');">
                                                            <input type="hidden" name="applicant_id" value="<?php echo $applicant['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                                <i class='bx bx-check'></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this applicant?');">
                                                            <input type="hidden" name="applicant_id" value="<?php echo $applicant['id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                                <i class='bx bx-x'></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
