<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();
} catch(PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Start the page
admin_header('Page Title'); // Replace with actual page title
?>

<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    </div>

    <!-- Page Content -->
    <div class="page-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Page Title</h1>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <!-- Page content goes here -->

                </main>
            </div>
        </div>
    </div>
</div>

<?php
admin_footer();
?>
