<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/ExportManager.php';
require_once './includes/admin_layout.php';

$auth = new Auth();
$auth->requireRole(['admin', 'super_admin']);

$user = $auth->getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filters = [
        'date_from' => $_POST['date_from'] ?? null,
        'date_to' => $_POST['date_to'] ?? null,
        'status' => $_POST['status'] ?? null,
        'applicant_id' => $_POST['applicant_id'] ?? null
    ];

    $exportManager = new ExportManager();
    $result = $exportManager->exportScreeningResults($filters);

    if ($result['success']) {
        $success = "Export successful! File: " . $result['filename'];
        // Trigger download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $result['filename'] . '"');
        header('Cache-Control: max-age=0');
        readfile($result['filepath']);
        unlink($result['filepath']); // Delete the temporary file
        exit;
    } else {
        $error = "Export failed: " . $result['error'];
    }
}

$page_title = "Export Screening Results";
admin_header($page_title);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><?php echo $page_title; ?></h1>
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

    <div class="card">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Applicant Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All</option>
                            <option value="Registered">Registered</option>
                            <option value="Approved">Approved</option>
                            <option value="Pending">Pending</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="applicant_id" class="form-label">Applicant ID</label>
                        <input type="number" class="form-control" id="applicant_id" name="applicant_id" placeholder="Optional">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-export"></i> Export Results
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Export History Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Export History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Admin</th>
                            <th>Filename</th>
                            <th>Type</th>
                            <th>Filters Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT eh.*, CONCAT(a.first_name, ' ', a.last_name) as admin_name 
                                 FROM export_history eh 
                                 JOIN admins a ON eh.admin_id = a.id 
                                 ORDER BY eh.created_at DESC 
                                 LIMIT 10";
                        $history = $db->query($query)->fetchAll();
                        foreach ($history as $record):
                            $filters = json_decode($record['filters'], true);
                            $filterText = [];
                            if (!empty($filters['date_from'])) $filterText[] = "From: " . $filters['date_from'];
                            if (!empty($filters['date_to'])) $filterText[] = "To: " . $filters['date_to'];
                            if (!empty($filters['status'])) $filterText[] = "Status: " . $filters['status'];
                            if (!empty($filters['applicant_id'])) $filterText[] = "ID: " . $filters['applicant_id'];
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($record['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($record['admin_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['filename']); ?></td>
                            <td><?php echo htmlspecialchars($record['export_type']); ?></td>
                            <td><?php echo htmlspecialchars(implode(', ', $filterText)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Date range validation
document.getElementById('date_to').addEventListener('change', function() {
    var dateFrom = document.getElementById('date_from').value;
    var dateTo = this.value;
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        alert('Date To must be after Date From');
        this.value = '';
    }
});
</script>

<?php admin_footer(); ?>
