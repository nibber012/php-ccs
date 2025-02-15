<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = Database::getInstance();;
        $conn = $database->getConnection();

        $report_type = $_POST['report_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $format = $_POST['format'] ?? 'csv';

        // Validate inputs
        if (!$report_type) {
            throw new Exception('Please select a report type.');
        }
        if (!$start_date || !$end_date) {
            throw new Exception('Please select both start and end dates.');
        }
        if ($start_date > $end_date) {
            throw new Exception('Start date cannot be later than end date.');
        }

        // Build query based on report type
        switch ($report_type) {
            case 'applicants':
                $query = "SELECT 
                            a.id,
                            a.first_name,
                            a.last_name,
                            u.email,
                            a.contact_number,
                            a.preferred_course,
                            a.progress_status as status,
                            a.created_at
                         FROM applicants a
                         JOIN users u ON a.user_id = u.id
                         WHERE DATE(a.created_at) BETWEEN ? AND ?
                         ORDER BY a.created_at DESC";
                $filename = "applicants_report";
                $headers = ['ID', 'First Name', 'Last Name', 'Email', 'Contact Number', 'Preferred Course', 'Status', 'Application Date'];
                break;

            case 'exams':
                $query = "SELECT 
                            er.id,
                            CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                            er.score,
                            e.title as exam_title,
                            e.part as exam_part,
                            er.status,
                            er.created_at as exam_date
                         FROM exam_results er
                         JOIN users u ON er.user_id = u.id
                         JOIN exams e ON er.exam_id = e.id
                         WHERE DATE(er.created_at) BETWEEN ? AND ?
                         ORDER BY er.created_at DESC";
                $filename = "exam_results_report";
                $headers = ['ID', 'Applicant Name', 'Score', 'Exam Title', 'Part', 'Result', 'Date Taken'];
                break;

            case 'interviews':
                $query = "SELECT 
                            i.id,
                            CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                            CONCAT(u.first_name, ' ', u.last_name) as interviewer_name,
                            i.schedule_date,
                            i.start_time,
                            i.end_time,
                            i.status,
                            i.interview_status,
                            i.total_score
                         FROM interview_schedules i
                         JOIN applicants a ON i.applicant_id = a.id
                         JOIN users u ON i.interviewer_id = u.id
                         WHERE DATE(i.schedule_date) BETWEEN ? AND ?
                         ORDER BY i.schedule_date DESC";
                $filename = "interview_results_report";
                $headers = ['ID', 'Applicant Name', 'Interviewer', 'Date', 'Start Time', 'End Time', 'Status', 'Result', 'Score'];
                break;

            default:
                throw new Exception('Invalid report type selected.');
        }

        // Execute query
        $stmt = $conn->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            throw new Exception('No data found for the selected criteria.');
        }

        // Generate filename with date range
        $filename = sprintf("%s_%s_to_%s.%s", 
            $filename,
            date('Y-m-d', strtotime($start_date)),
            date('Y-m-d', strtotime($end_date)),
            $format
        );

        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add headers
        fputcsv($output, $headers);

        // Add data rows
        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        // Close the output stream
        fclose($output);
        exit;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

admin_header('Export Reports');
?>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2 mb-0">Export Reports</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../super/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item">Reports</li>
                    <li class="breadcrumb-item active" aria-current="page">Export Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bx bx-error-circle me-1"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <i class="bx bx-check-circle me-1"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-export me-2"></i>Generate Report
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Report Type -->
                        <div class="mb-4">
                            <label class="form-label">Report Type</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check custom-card">
                                        <input class="form-check-input" type="radio" name="report_type" 
                                               id="applicants" value="applicants" required>
                                        <label class="form-check-label card w-100" for="applicants">
                                            <div class="card-body text-center">
                                                <i class="bx bx-user-pin fs-1 mb-2 text-primary"></i>
                                                <h6 class="mb-1">Applicants</h6>
                                                <small class="text-muted">Export applicant records</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check custom-card">
                                        <input class="form-check-input" type="radio" name="report_type" 
                                               id="exams" value="exams" required>
                                        <label class="form-check-label card w-100" for="exams">
                                            <div class="card-body text-center">
                                                <i class="bx bx-edit fs-1 mb-2 text-success"></i>
                                                <h6 class="mb-1">Exam Results</h6>
                                                <small class="text-muted">Export exam scores</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check custom-card">
                                        <input class="form-check-input" type="radio" name="report_type" 
                                               id="interviews" value="interviews" required>
                                        <label class="form-check-label card w-100" for="interviews">
                                            <div class="card-body text-center">
                                                <i class="bx bx-user-voice fs-1 mb-2 text-info"></i>
                                                <h6 class="mb-1">Interviews</h6>
                                                <small class="text-muted">Export interview results</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="mb-4">
                            <label class="form-label">Date Range</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                        <div class="invalid-feedback">Please select a start date.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                        <div class="invalid-feedback">Please select an end date.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-download me-1"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.custom-card {
    padding: 0;
    margin: 0;
}
.custom-card input[type="radio"] {
    display: none;
}
.custom-card label.card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.custom-card input[type="radio"]:checked + label.card {
    border-color: #4e73df;
    background-color: #f8f9fc;
}
.custom-card .card-body {
    padding: 1.5rem;
}
.custom-card i {
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set default dates to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    document.getElementById('start_date').value = firstDay.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];

    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });

    // Date validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    function validateDates() {
        if (startDate.value && endDate.value) {
            if (startDate.value > endDate.value) {
                endDate.setCustomValidity('End date must be after start date');
            } else {
                endDate.setCustomValidity('');
            }
        }
    }

    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);
});
</script>

<?php
admin_footer();
?>
