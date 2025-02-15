<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Handle admin status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['admin_id'])) {
        $admin_id = $_POST['admin_id'];
        $action = $_POST['action'];

        try {
            $database = Database::getInstance();
            $conn = $database->getConnection();

            switch ($action) {
                case 'activate':
                    $status = 'active';
                    break;
                case 'deactivate':
                    $status = 'inactive';
                    break;
                default:
                    throw new Exception('Invalid action');
            }

            $query = "UPDATE users SET status = ? WHERE id = ? AND role = 'admin'";
            $stmt = $conn->prepare($query);
            $stmt->execute([$status, $admin_id]);

            if ($stmt->rowCount() > 0) {
                $auth->logActivity(
                    $user['id'],
                    'admin_' . $action,
                    "Admin account (ID: $admin_id) has been $action" . "d"
                );
                $success = 'Admin status updated successfully';
            } else {
                $error = 'Failed to update admin status';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Fetch all admin accounts
try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    $query = "SELECT u.id, u.email, u.status, u.created_at, 
              a.first_name, a.last_name, a.department,
              (SELECT COUNT(*) FROM activity_logs WHERE user_id = u.id) as activity_count
              FROM users u
              JOIN admins a ON u.id = a.user_id
              WHERE u.role = 'admin'
              ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
    $admins = [];
}

$page_title = 'Admin Accounts';
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
                            <a href="create_admin.php" class="btn btn-primary">
                                <i class='bx bx-user-plus'></i> Create Admin Account
                            </a>
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
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Activities</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($admins)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No admin accounts found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['department']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $admin['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($admin['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $admin['activity_count']; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="view_admin.php?id=<?php echo $admin['id']; ?>" 
                                                               class="btn btn-sm btn-info">
                                                                <i class='bx bx-show'></i>
                                                            </a>
                                                            <?php if ($admin['status'] === 'active'): ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <button type="submit" name="action" value="deactivate" 
                                                                            class="btn btn-sm btn-danger"
                                                                            onclick="return confirm('Are you sure you want to deactivate this admin account?')">
                                                                        <i class='bx bx-power-off'></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                                    <button type="submit" name="action" value="activate" 
                                                                            class="btn btn-sm btn-success"
                                                                            onclick="return confirm('Are you sure you want to activate this admin account?')">
                                                                        <i class='bx bx-power-off'></i>
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
