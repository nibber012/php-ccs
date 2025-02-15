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

    // Get filter parameters
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';

    // Build query conditions
    $conditions = ["i.status = 'completed'"];
    $params = [];

    if ($start_date) {
        $conditions[] = "i.schedule_date >= ?";
        $params[] = $start_date;
    }

    if ($end_date) {
        $conditions[] = "i.schedule_date <= ?";
        $params[] = $end_date;
    }

    if ($status !== 'all') {
        $conditions[] = "a.progress_status = ?";
        $params[] = $status;
    }

    if ($search) {
        $conditions[] = "(CONCAT(a.first_name, ' ', a.last_name) LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    // Get interview results with scores
    $query = "SELECT 
                i.*,
                CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                u.email as applicant_email,
                a.contact_number as applicant_contact,
                CONCAT(u2.first_name, ' ', u2.last_name) as interviewer_name,
                u2.role as interviewer_role,
                (
                    SELECT SUM(score)
                    FROM interview_scores
                    WHERE interview_schedule_id = i.id
                ) as total_score,
                (
                    SELECT GROUP_CONCAT(
                    CONCAT(category, ':', score)
                    SEPARATOR '|'
                )
                FROM interview_scores
                WHERE interview_schedule_id = i.id
                ) as score_breakdown
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              JOIN users u ON a.user_id = u.id
              JOIN users u2 ON i.interviewer_id = u2.id
              $where_clause
              ORDER BY i.schedule_date DESC, i.start_time DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Calculate statistics
    $total_interviews = count($results);
    $passed_interviews = 0;
    $failed_interviews = 0;
    $avg_score = 0;
    $score_distribution = [
        '90-100' => 0,
        '80-89' => 0,
        '70-79' => 0,
        '60-69' => 0,
        'Below 60' => 0
    ];

    foreach ($results as $result) {
        $score_percentage = ($result['total_score'] / 100) * 100;
        $avg_score += $score_percentage;

        if ($score_percentage >= 70) {
            $passed_interviews++;
        } else {
            $failed_interviews++;
        }

        // Update score distribution
        if ($score_percentage >= 90) {
            $score_distribution['90-100']++;
        } elseif ($score_percentage >= 80) {
            $score_distribution['80-89']++;
        } elseif ($score_percentage >= 70) {
            $score_distribution['70-79']++;
        } elseif ($score_percentage >= 60) {
            $score_distribution['60-69']++;
        } else {
            $score_distribution['Below 60']++;
        }
    }

    $avg_score = $total_interviews > 0 ? $avg_score / $total_interviews : 0;

} catch (Exception $e) {
    $error = $e->getMessage();
}

admin_header('Interview Results Report');
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2">Interview Results Report</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="exportToExcel()">
                <i class="bi bi-file-excel"></i> Export to Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="passed" <?php echo $status === 'passed' ? 'selected' : ''; ?>>Passed</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Interviews</h5>
                    <h2 class="mb-0"><?php echo $total_interviews; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Pass Rate</h5>
                    <h2 class="mb-0">
                        <?php 
                            echo $total_interviews > 0 
                                ? round(($passed_interviews / $total_interviews) * 100, 1) 
                                : 0; 
                        ?>%
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Average Score</h5>
                    <h2 class="mb-0"><?php echo round($avg_score, 1); ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Fail Rate</h5>
                    <h2 class="mb-0">
                        <?php 
                            echo $total_interviews > 0 
                                ? round(($failed_interviews / $total_interviews) * 100, 1) 
                                : 0; 
                        ?>%
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Score Distribution Chart -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Score Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="scoreDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($results)): ?>
                <p class="text-center text-muted my-5">No interview results found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="resultsTable">
                        <thead>
                            <tr>
                                <th>Interview Date</th>
                                <th>Applicant</th>
                                <th>Interviewer</th>
                                <th>Score Breakdown</th>
                                <th>Total Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($result['schedule_date'])); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['applicant_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($result['applicant_email']); ?><br>
                                            <?php echo htmlspecialchars($result['applicant_contact']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($result['interviewer_name']); ?><br>
                                        <span class="badge bg-secondary">
                                            <?php echo strtoupper($result['interviewer_role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($result['score_breakdown']) {
                                            $scores = array_map(function($item) {
                                                list($category, $score) = explode(':', $item);
                                                return ucfirst(str_replace('_', ' ', $category)) . ': ' . $score;
                                            }, explode('|', $result['score_breakdown']));
                                            echo implode('<br>', $scores);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $result['total_score']; ?>/100</strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $score_percentage = ($result['total_score'] / 100) * 100;
                                        $passed = $score_percentage >= 70;
                                        ?>
                                        <span class="badge <?php echo $passed ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $passed ? 'PASSED' : 'FAILED'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Include SheetJS for Excel export -->
<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>

<script>
// Initialize Score Distribution Chart
const ctx = document.getElementById('scoreDistributionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($score_distribution)); ?>,
        datasets: [{
            label: 'Number of Applicants',
            data: <?php echo json_encode(array_values($score_distribution)); ?>,
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',  // 90-100
                'rgba(23, 162, 184, 0.8)', // 80-89
                'rgba(255, 193, 7, 0.8)',  // 70-79
                'rgba(253, 126, 20, 0.8)', // 60-69
                'rgba(220, 53, 69, 0.8)'   // Below 60
            ],
            borderColor: [
                'rgb(40, 167, 69)',
                'rgb(23, 162, 184)',
                'rgb(255, 193, 7)',
                'rgb(253, 126, 20)',
                'rgb(220, 53, 69)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Export to Excel function
function exportToExcel() {
    const table = document.getElementById('resultsTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: "Interview Results"});
    XLSX.writeFile(wb, `interview_results_${new Date().toISOString().slice(0,10)}.xlsx`);
}

// Print styles
@media print {
    .btn-toolbar,
    .card-header button,
    form {
        display: none !important;
    }
    
    .card {
        border: none !important;
    }
    
    .table {
        width: 100% !important;
        page-break-inside: auto !important;
    }
    
    tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }
}
</script>

<?php
admin_footer();
?>
