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

    // Get admin ID from URL
    $admin_id = $_GET['id'] ?? null;
    if (!$admin_id) {
        throw new Exception('Admin ID is required.');
    }

    // Get admin details
    $query = "SELECT u.id, u.email, u.status, u.created_at,
              a.first_name, a.last_name, a.department
              FROM users u
              JOIN admins a ON u.id = a.user_id
              WHERE u.id = ? AND u.role = 'admin'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        throw new Exception('Admin not found.');
    }

    // Get admin activities with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    // Get total count
    $count_query = "SELECT COUNT(*) FROM activity_logs WHERE user_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->execute([$admin_id]);
    $total_count = $stmt->fetchColumn();
    $total_pages = ceil($total_count / $per_page);

    // Get activities
    $query = "SELECT al.*, DATE_FORMAT(al.created_at, '%M %d, %Y %h:%i %p') as formatted_date
              FROM activity_logs al
              WHERE al.user_id = ?
              ORDER BY al.created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$admin_id, $per_page, $offset]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('View Admin Activity');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Admin Activity Log</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="list_admins.php" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Admin List
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($admin)): ?>
        <!-- Admin Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <div class="display-3 text-primary mb-3">
                            <i class="bi bi-person-circle"></i>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <h4><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h4>
                        <p class="text-muted mb-2">Administrator</p>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                                <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($admin['department']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($admin['status']); ?>
                                    </span>
                                </p>
                                <p class="mb-1"><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($admin['created_at'])); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>Total Activities:</strong> <?php echo $total_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Activity Timeline</h5>
            </div>
            <div class="card-body">
                <?php if (empty($activities)): ?>
                    <p class="text-center text-muted my-3">No activities found.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activities as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($activity['details']); ?></p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo $activity['formatted_date']; ?>
                                        <?php if ($activity['ip_address']): ?>
                                            <span class="ms-2">
                                                <i class="bi bi-globe me-1"></i>
                                                <?php echo htmlspecialchars($activity['ip_address']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $admin_id; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $admin_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?id=<?php echo $admin_id; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #0d6efd;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #0d6efd;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 15px;
    bottom: -30px;
    width: 2px;
    background: #dee2e6;
}

.timeline-item:last-child::before {
    display: none;
}
</style>

<?php
admin_footer();
?>
