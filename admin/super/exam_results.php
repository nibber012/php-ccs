<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';
$resultsByPart = [];

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Get filter parameters
    $exam_part = $_GET['part'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Build the WHERE clause
    $where_conditions = [];
    $params = [];

    if ($exam_part !== 'all') {
        $where_conditions[] = "e.part = ?";
        $params[] = $exam_part;
    }

    if ($status !== 'all') {
        if ($status === 'passed') {
            $where_conditions[] = "(SELECT COUNT(*) FROM applicant_answers aa WHERE aa.exam_id = e.id AND aa.applicant_id = a.id AND aa.is_correct = 1) / (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) * 100 >= e.passing_score";
        } else {
            $where_conditions[] = "(SELECT COUNT(*) FROM applicant_answers aa WHERE aa.exam_id = e.id AND aa.applicant_id = a.id AND aa.is_correct = 1) / (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) * 100 < e.passing_score";
        }
    }

    if ($search) {
        $where_conditions[] = "(CONCAT(a.first_name, ' ', a.last_name) LIKE ? OR e.title LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($date_from)) {
        $where_conditions[] = "er.created_at >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "er.created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
    }

    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Get summary statistics
    $stats_query = "SELECT 
        COUNT(*) as total_attempts,
        SUM(CASE WHEN (
            SELECT COUNT(*) FROM applicant_answers aa 
            WHERE aa.exam_id = e.id AND aa.applicant_id = a.id AND aa.is_correct = 1
        ) / (
            SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id
        ) * 100 >= e.passing_score THEN 1 ELSE 0 END) as total_passed,
        AVG((
            SELECT COUNT(*) FROM applicant_answers aa 
            WHERE aa.exam_id = e.id AND aa.applicant_id = a.id AND aa.is_correct = 1
        ) / (
            SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id
        ) * 100) as average_score
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN applicants a ON er.applicant_id = a.id
    $where_clause";

    $stmt = $conn->prepare($stats_query);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all exam results grouped by part
    $query = "SELECT 
                er.*,
                e.title as exam_title,
                e.type as exam_type,
                e.part as exam_part,
                e.passing_score,
                CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                u.email as applicant_email,
                (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) as total_questions,
                (SELECT COUNT(*) FROM applicant_answers aa 
                 WHERE aa.exam_id = e.id 
                 AND aa.applicant_id = a.id 
                 AND aa.is_correct = 1) as correct_answers
              FROM exam_results er
              JOIN exams e ON er.exam_id = e.id
              JOIN applicants a ON er.applicant_id = a.id
              JOIN users u ON er.user_id = u.id
              $where_clause
              ORDER BY e.part ASC, er.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group results by exam part
    foreach ($results as $result) {
        $resultsByPart[$result['exam_part']][] = $result;
    }

} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
    $results = [];
    $stats = [
        'total_attempts' => 0,
        'total_passed' => 0,
        'average_score' => 0
    ];
}

$page_title = 'Exam Results';
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
                        <div>
                            <h1 class="h2"><?php echo $page_title; ?></h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="list_exams.php">Exams</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Results</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="exportToExcel()">
                                <i class='bx bx-file'></i> Export to Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class='bx bx-printer'></i> Print View
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class='bx bx-test-tube bx-lg text-primary'></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Total Attempts</h6>
                                    <h3 class="mb-0"><?php echo number_format($stats['total_attempts']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class='bx bx-check-circle bx-lg text-success'></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Passed Exams</h6>
                                    <h3 class="mb-0">
                                        <?php 
                                        echo number_format($stats['total_passed']);
                                        if ($stats['total_attempts'] > 0) {
                                            echo ' <small class="text-muted">(' . 
                                                 number_format(($stats['total_passed'] / $stats['total_attempts']) * 100, 1) . 
                                                 '%)</small>';
                                        }
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class='bx bx-bar-chart-alt-2 bx-lg text-info'></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="card-title mb-1">Average Score</h6>
                                    <h3 class="mb-0">
                                        <?php echo number_format($stats['average_score'], 1); ?>%
                                    </h3>
                                </div>
                            </div>
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
                            <div class="input-group">
                                <span class="input-group-text"><i class='bx bx-search'></i></span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name or exam...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label for="part" class="form-label">Exam Part</label>
                            <select class="form-select" id="part" name="part">
                                <option value="all" <?php echo $exam_part === 'all' ? 'selected' : ''; ?>>All Parts</option>
                                <option value="1" <?php echo $exam_part === '1' ? 'selected' : ''; ?>>Part 1</option>
                                <option value="2" <?php echo $exam_part === '2' ? 'selected' : ''; ?>>Part 2</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="passed" <?php echo $status === 'passed' ? 'selected' : ''; ?>>Passed</option>
                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class='bx bx-filter-alt'></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Results by Part -->
            <?php if (empty($resultsByPart)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-5">
                            <i class='bx bx-info-circle bx-lg text-muted'></i>
                            <p class="text-muted mt-2">No exam results found matching your criteria.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($resultsByPart as $part => $partResults): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h3 class="h5 mb-0">Part <?php echo $part; ?> Results</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="resultsTable<?php echo $part; ?>">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Exam</th>
                                            <th class="text-center">Score</th>
                                            <th class="text-center">Correct/Total</th>
                                            <th class="text-center">Status</th>
                                            <th>Completion Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($partResults as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['applicant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                            <td class="text-center">
                                                <?php 
                                                    $score = ($result['correct_answers'] / $result['total_questions']) * 100;
                                                    echo number_format($score, 1) . '%';
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $result['correct_answers'] . '/' . $result['total_questions']; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                    $passed = $score >= $result['passing_score'];
                                                    echo '<span class="badge ' . ($passed ? 'bg-success' : 'bg-danger') . '">';
                                                    echo $passed ? 'Passed' : 'Failed';
                                                    echo '</span>';
                                                ?>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($result['created_at'])); ?></td>
                                            <td class="text-end">
                                                <a href="view_result.php?id=<?php echo $result['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class='bx bx-show'></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
<script>
function exportToExcel() {
    // Create workbook
    const wb = XLSX.utils.book_new();
    
    <?php foreach ($resultsByPart as $part => $partResults): ?>
    // Create worksheet for each part
    const ws<?php echo $part; ?> = XLSX.utils.table_to_sheet(document.getElementById('resultsTable<?php echo $part; ?>'));
    XLSX.utils.book_append_sheet(wb, ws<?php echo $part; ?>, 'Part <?php echo $part; ?> Results');
    <?php endforeach; ?>
    
    // Save the file
    XLSX.writeFile(wb, 'exam_results_<?php echo date('Y-m-d'); ?>.xlsx');
}

// Auto-submit form when filters change
document.querySelectorAll('select[name="part"], select[name="status"]').forEach(select => {
    select.addEventListener('change', () => {
        select.closest('form').submit();
    });
});

// Date range validation
document.getElementById('date_to').addEventListener('change', function() {
    const dateFrom = document.getElementById('date_from');
    if (dateFrom.value && this.value < dateFrom.value) {
        alert('Date To cannot be earlier than Date From');
        this.value = dateFrom.value;
    }
});

document.getElementById('date_from').addEventListener('change', function() {
    const dateTo = document.getElementById('date_to');
    if (dateTo.value && this.value > dateTo.value) {
        dateTo.value = this.value;
    }
});
</script>

<?php admin_footer(); ?>
