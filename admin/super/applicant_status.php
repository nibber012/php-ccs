<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';
$applicants = [];

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$course_filter = $_GET['course'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // Build where conditions
    $where_conditions = [];
    $params = [];

    if ($status_filter !== 'all') {
        $where_conditions[] = "a.progress_status = :status";
        $params[':status'] = $status_filter;
    }

    if ($course_filter !== 'all') {
        $where_conditions[] = "a.preferred_course = :course";
        $params[':course'] = $course_filter;
    }

    if ($search) {
        $where_conditions[] = "(a.first_name LIKE :search1 OR a.last_name LIKE :search2 OR u.email LIKE :search3)";
        $search_param = "%$search%";
        $params[':search1'] = $search_param;
        $params[':search2'] = $search_param;
        $params[':search3'] = $search_param;
    }

    // Get total count for pagination
    $count_query = "SELECT COUNT(DISTINCT a.id) as total FROM applicants a
                    JOIN users u ON a.user_id = u.id
                    WHERE 1=1";
    if (!empty($where_conditions)) {
        $count_query .= " AND " . implode(" AND ", $where_conditions);
    }

    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $per_page);

    // Get applicants with pagination
    $main_query = "SELECT 
                a.*,
                u.email,
                u.status as user_status,
                COALESCE(e1.score, 0) as part1_score,
                COALESCE(e2.score, 0) as part2_score,
                COALESCE(i.total_score, 0) as interview_score,
                i.interview_status,
                e1.exam_date as part1_date,
                e2.exam_date as part2_date,
                i.schedule_date as interview_date
              FROM applicants a
              JOIN users u ON a.user_id = u.id
              LEFT JOIN (
                SELECT er.user_id, er.score, er.created_at as exam_date
                FROM exam_results er
                JOIN exams e ON er.exam_id = e.id
                WHERE e.part = '1'
              ) e1 ON a.user_id = e1.user_id
              LEFT JOIN (
                SELECT er.user_id, er.score, er.created_at as exam_date
                FROM exam_results er
                JOIN exams e ON er.exam_id = e.id
                WHERE e.part = '2'
              ) e2 ON a.user_id = e2.user_id
              LEFT JOIN (
                SELECT applicant_id, total_score, interview_status, schedule_date
                FROM interview_schedules
                WHERE status = 'completed'
              ) i ON a.id = i.applicant_id
              WHERE 1=1";

    if (!empty($where_conditions)) {
        $main_query .= " AND " . implode(" AND ", $where_conditions);
    }

    $main_query .= " ORDER BY a.id DESC LIMIT " . $per_page . " OFFSET " . $offset;

    $stmt = $conn->prepare($main_query);
    $stmt->execute($params);
    $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get available courses for filter
    $courses_query = "SELECT DISTINCT preferred_course FROM applicants ORDER BY preferred_course";
    $stmt = $conn->prepare($courses_query);
    $stmt->execute();
    $available_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (Exception $e) {
    $error = $e->getMessage();
}

$page_title = 'Applicant Status Overview';
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
                            <a href="manage_applicants.php" class="btn btn-sm btn-outline-primary me-2">
                                <i class='bx bx-user-plus'></i> Manage Applicants
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class='bx bx-printer'></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Progress Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="registered" <?php echo $status_filter === 'registered' ? 'selected' : ''; ?>>Registered</option>
                                        <option value="exam_scheduled" <?php echo $status_filter === 'exam_scheduled' ? 'selected' : ''; ?>>Exam Scheduled</option>
                                        <option value="exam_completed" <?php echo $status_filter === 'exam_completed' ? 'selected' : ''; ?>>Exam Completed</option>
                                        <option value="interview_scheduled" <?php echo $status_filter === 'interview_scheduled' ? 'selected' : ''; ?>>Interview Scheduled</option>
                                        <option value="interview_completed" <?php echo $status_filter === 'interview_completed' ? 'selected' : ''; ?>>Interview Completed</option>
                                        <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="course" class="form-label">Course</label>
                                    <select class="form-select" id="course" name="course">
                                        <option value="all" <?php echo $course_filter === 'all' ? 'selected' : ''; ?>>All Courses</option>
                                        <?php foreach ($available_courses as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course); ?>" 
                                                <?php echo $course_filter === $course ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by name or email">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class='bx bx-filter-alt'></i> Apply Filters
                                    </button>
                                </div>
                            </form>
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

            <!-- Main Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Course</th>
                                            <th>Progress Status</th>
                                            <th>Exam Part 1</th>
                                            <th>Exam Part 2</th>
                                            <th>Interview</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($applicants)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No applicants found</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($applicants as $applicant): ?>
                                            <tr>
                                                <td><?php echo $applicant['id']; ?></td>
                                                <td>
                                                    <div>
                                                        <?php echo htmlspecialchars($applicant['first_name'] . ' ' . $applicant['last_name']); ?>
                                                        <?php if ($applicant['user_status'] === 'pending'): ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($applicant['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($applicant['preferred_course']); ?></td>
                                                <td>
                                                    <?php
                                                    $progress_class = match($applicant['progress_status']) {
                                                        'registered' => 'info',
                                                        'exam_scheduled' => 'primary',
                                                        'exam_completed' => 'success',
                                                        'interview_scheduled' => 'warning',
                                                        'interview_completed' => 'success',
                                                        'accepted' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?php echo $progress_class; ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $applicant['progress_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($applicant['part1_score'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $applicant['part1_score']; ?>%</span>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y', strtotime($applicant['part1_date'])); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not taken</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($applicant['part2_score'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $applicant['part2_score']; ?>%</span>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y', strtotime($applicant['part2_date'])); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not taken</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($applicant['interview_score'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $applicant['interview_score']; ?>%</span>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y', strtotime($applicant['interview_date'])); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not scheduled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view_applicant.php?id=<?php echo $applicant['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="View Details">
                                                            <i class='bx bx-show'></i>
                                                        </a>
                                                        <a href="schedule_exam.php?id=<?php echo $applicant['id']; ?>" 
                                                           class="btn btn-sm btn-primary" title="Schedule Exam">
                                                            <i class='bx bx-calendar-plus'></i>
                                                        </a>
                                                        <a href="schedule_interview.php?id=<?php echo $applicant['id']; ?>" 
                                                           class="btn btn-sm btn-warning" title="Schedule Interview">
                                                            <i class='bx bx-calendar-check'></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&course=<?php echo urlencode($course_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <i class='bx bx-chevron-left'></i> Previous
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&course=<?php echo urlencode($course_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&course=<?php echo urlencode($course_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            Next <i class='bx bx-chevron-right'></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
@media print {
    .btn-toolbar,
    .sidebar,
    .pagination,
    .filters {
        display: none !important;
    }
    .page-content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php admin_footer(); ?>
