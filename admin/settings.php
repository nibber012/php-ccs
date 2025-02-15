<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';

$auth = new Auth();
$db = Database::getInstance();

// Get current user
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add settings update logic here
        // For now, we'll just show a success message
        $success = 'Settings updated successfully';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
require_once 'includes/layout.php';
admin_header('Settings');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Settings</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Settings</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notification Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" checked>
                                <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                            </div>
                            <small class="text-muted">Receive email notifications for important updates</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="applicationAlerts" name="application_alerts" checked>
                                <label class="form-check-label" for="applicationAlerts">Application Alerts</label>
                            </div>
                            <small class="text-muted">Receive alerts for new applications</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="interviewReminders" name="interview_reminders" checked>
                                <label class="form-check-label" for="interviewReminders">Interview Reminders</label>
                            </div>
                            <small class="text-muted">Receive reminders for upcoming interviews</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Display Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Manila">Asia/Manila (UTC+8)</option>
                                <!-- Add more timezone options as needed -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="dateFormat" class="form-label">Date Format</label>
                            <select class="form-select" id="dateFormat" name="date_format">
                                <option value="Y-m-d">YYYY-MM-DD</option>
                                <option value="m/d/Y">MM/DD/YYYY</option>
                                <option value="d/m/Y">DD/MM/YYYY</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">PHP Version</label>
                        <p class="mb-0"><?php echo PHP_VERSION; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Server Software</label>
                        <p class="mb-0"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Database Version</label>
                        <p class="mb-0"><?php 
                            $version = $db->query("SELECT VERSION() as version")->fetch(PDO::FETCH_ASSOC);
                            echo $version['version'];
                        ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
