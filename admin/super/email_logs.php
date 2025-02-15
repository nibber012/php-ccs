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

    // Get filters
    $status = $_GET['status'] ?? '';
    $template = $_GET['template'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $search = $_GET['search'] ?? '';

    // Build where clause
    $where_conditions = [];
    $params = [];

    if ($status) {
        $where_conditions[] = "el.status = ?";
        $params[] = $status;
    }

    if ($template) {
        $where_conditions[] = "el.template_name = ?";
        $params[] = $template;
    }

    if ($date_from) {
        $where_conditions[] = "DATE(el.sent_at) >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = "DATE(el.sent_at) <= ?";
        $params[] = $date_to;
    }

    if ($search) {
        $where_conditions[] = "(el.recipient_email LIKE ? OR el.recipient_name LIKE ? OR el.subject LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count
    $query = "SELECT COUNT(*) as total FROM email_logs el $where_clause";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $total_count = $stmt->fetch()['total'];

    // Get statistics
    $query = "SELECT 
                COUNT(*) as total_emails,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                COUNT(DISTINCT recipient_email) as unique_recipients
              FROM email_logs el $where_clause";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $stats = $stmt->fetch();

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    $total_pages = ceil($total_count / $per_page);

    // Get logs
    $query = "SELECT 
                el.*,
                CASE 
                    WHEN el.related_type = 'interview_schedule' THEN i.schedule_date
                    WHEN el.related_type = 'interview_result' THEN i.schedule_date
                    WHEN el.related_type = 'interview_reminder' THEN i.schedule_date
                    WHEN el.related_type = 'interview_cancellation' THEN i.schedule_date
                END as related_date,
                CASE 
                    WHEN el.related_type LIKE 'interview%' THEN CONCAT(a.first_name, ' ', a.last_name)
                END as related_name
              FROM email_logs el
              LEFT JOIN interview_schedules i ON 
                (el.related_type LIKE 'interview%' AND el.related_id = i.id)
              LEFT JOIN applicants a ON 
                (el.related_type LIKE 'interview%' AND i.applicant_id = a.id)
              $where_clause
              ORDER BY el.sent_at DESC
              LIMIT $per_page OFFSET $offset";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('Email Logs');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Email Logs</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Emails</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_emails']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Sent Successfully</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['sent_count']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Failed</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['failed_count']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Unique Recipients</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['unique_recipients']); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Email, name, or subject">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="template" class="form-label">Template</label>
                    <select class="form-select" id="template" name="template">
                        <option value="">All</option>
                        <option value="interview_schedule" <?php echo $template === 'interview_schedule' ? 'selected' : ''; ?>>Interview Schedule</option>
                        <option value="interview_result" <?php echo $template === 'interview_result' ? 'selected' : ''; ?>>Interview Result</option>
                        <option value="interview_reminder" <?php echo $template === 'interview_reminder' ? 'selected' : ''; ?>>Interview Reminder</option>
                        <option value="interview_cancellation" <?php echo $template === 'interview_cancellation' ? 'selected' : ''; ?>>Interview Cancellation</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Recipient</th>
                            <th>Template</th>
                            <th>Related To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($log['sent_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['recipient_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['recipient_email']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucwords(str_replace('_', ' ', $log['template_name'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['related_name']): ?>
                                        <?php echo htmlspecialchars($log['related_name']); ?><br>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($log['related_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $log['status'] === 'sent' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                    <?php if ($log['error_message']): ?>
                                        <i class="bi bi-info-circle" 
                                           data-bs-toggle="tooltip" 
                                           title="<?php echo htmlspecialchars($log['error_message']); ?>"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&template=<?php echo urlencode($template); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
admin_footer();
?>
