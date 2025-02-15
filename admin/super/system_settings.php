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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle system settings update
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_exam_settings':
                    // Update exam settings logic here
                    $success = "Exam settings updated successfully.";
                    break;
                
                case 'update_interview_settings':
                    // Update interview settings logic here
                    $success = "Interview settings updated successfully.";
                    break;
                
                case 'update_email_settings':
                    // Update email settings logic here
                    $success = "Email settings updated successfully.";
                    break;
            }
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('System Settings');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">System Settings</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Exam Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Exam Settings</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="update_exam_settings">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Part 1 Passing Score</label>
                        <input type="number" class="form-control" name="part1_passing_score" min="0" max="100" value="75">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Part 2 Passing Score</label>
                        <input type="number" class="form-control" name="part2_passing_score" min="0" max="100" value="75">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Exam Settings</button>
            </form>
        </div>
    </div>

    <!-- Interview Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Interview Settings</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="update_interview_settings">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Interview Passing Score</label>
                        <input type="number" class="form-control" name="interview_passing_score" min="0" max="100" value="75">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Interview Duration (minutes)</label>
                        <input type="number" class="form-control" name="interview_duration" min="15" max="120" value="30">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Interview Settings</button>
            </form>
        </div>
    </div>

    <!-- Email Settings -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Email Settings</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="update_email_settings">
                <div class="mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" class="form-control" name="smtp_host" value="smtp.gmail.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" class="form-control" name="smtp_port" value="587">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Username</label>
                    <input type="email" class="form-control" name="smtp_username">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" class="form-control" name="smtp_password">
                </div>
                <div class="mb-3">
                    <label class="form-label">From Email</label>
                    <input type="email" class="form-control" name="from_email">
                </div>
                <div class="mb-3">
                    <label class="form-label">From Name</label>
                    <input type="text" class="form-control" name="from_name" value="CCS Screening System">
                </div>
                <button type="submit" class="btn btn-primary">Save Email Settings</button>
            </form>
        </div>
    </div>
</div>

<?php
admin_footer();
?>
