<?php
require_once '../../classes/Auth.php';
require_once '../../config/database.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('super_admin');

$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Initialize statistics arrays with default values
$overall_stats = [
    'total_applicants' => 0,
    'accepted_applicants' => 0,
    'rejected_applicants' => 0,
    'pending_applicants' => 0
];

$exam_stats = [
    'total_exams' => 0,
    'average_score' => 0,
    'passed_count' => 0,
    'failed_count' => 0
];

$interview_stats = [
    'total_interviews' => 0,
    'passed_interviews' => 0,
    'failed_interviews' => 0,
    'average_interview_score' => 0
];

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Get date range filters
    $start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
    $end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

    // Overall Statistics
    $query = "SELECT
                (SELECT COUNT(*) FROM applicants) as total_applicants,
                (SELECT COUNT(*) FROM applicants WHERE progress_status = 'passed') as accepted_applicants,
                (SELECT COUNT(*) FROM applicants WHERE progress_status = 'failed') as rejected_applicants,
                (SELECT COUNT(*) FROM applicants WHERE progress_status NOT IN ('passed', 'failed')) as pending_applicants";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $overall_stats = $result;
    }

    // Exam Statistics
    $query = "SELECT 
                COUNT(*) as total_exams,
                COALESCE(AVG(score), 0) as average_score,
                COUNT(CASE WHEN status = 'pass' THEN 1 END) as passed_count,
                COUNT(CASE WHEN status = 'fail' THEN 1 END) as failed_count
              FROM exam_results
              WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $exam_stats = $result;
    }

    // Interview Statistics
    $query = "SELECT 
                COUNT(*) as total_interviews,
                COUNT(CASE WHEN interview_status = 'passed' THEN 1 END) as passed_interviews,
                COUNT(CASE WHEN interview_status = 'failed' THEN 1 END) as failed_interviews,
                COALESCE(AVG(total_score), 0) as average_interview_score
              FROM interview_schedules
              WHERE schedule_date BETWEEN ? AND ?
              AND status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $interview_stats = $result;
    }

    // Monthly Application Trends
    $query = "SELECT 
                DATE_FORMAT(u.created_at, '%Y-%m') as month,
                COUNT(*) as application_count
              FROM applicants a
              JOIN users u ON a.user_id = u.id
              WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
              ORDER BY month ASC";
    $stmt = $conn->query($query);
    $monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Course Distribution
    $query = "SELECT 
                preferred_course,
                COUNT(*) as count
              FROM applicants 
              GROUP BY preferred_course";
    $stmt = $conn->query($query);
    $course_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily Application Trends
    $query = "SELECT 
                DATE(u.created_at) as date,
                COUNT(*) as count
              FROM applicants a
              JOIN users u ON a.user_id = u.id
              WHERE u.created_at BETWEEN ? AND ?
              GROUP BY DATE(u.created_at)
              ORDER BY date ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $daily_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error = "Error fetching analytics data: " . $e->getMessage();
}

// Helper function to safely format numbers
function formatNumber($value, $decimals = 0) {
    return number_format(floatval($value), $decimals);
}

admin_header('Analytics Dashboard');
?>

<div class="analytics-container">
    <!-- Date Filter -->
    <div class="date-filter">
        <h4 class="mb-0">Analytics Dashboard</h4>
        <form class="d-flex gap-3" method="GET">
            <div class="form-group">
                <label for="start_date">From:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">To:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="statistics-grid">
        <div class="stat-card">
            <h3><?php echo formatNumber($overall_stats['total_applicants']); ?></h3>
            <p>Total Applicants</p>
        </div>
        <div class="stat-card">
            <h3><?php echo formatNumber($overall_stats['pending_applicants']); ?></h3>
            <p>Pending Applicants</p>
        </div>
        <div class="stat-card">
            <h3><?php echo formatNumber($overall_stats['accepted_applicants']); ?></h3>
            <p>Accepted Applicants</p>
        </div>
        <div class="stat-card">
            <h3><?php echo formatNumber($overall_stats['rejected_applicants']); ?></h3>
            <p>Rejected Applicants</p>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
        <!-- Course Distribution Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>Course Distribution</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-container">
                    <canvas id="courseDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Daily Application Trends -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>Daily Application Trends</h5>
            </div>
            <div class="chart-card-body">
                <div class="chart-container">
                    <canvas id="dailyTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Exam Statistics -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>Exam Statistics</h5>
            </div>
            <div class="chart-card-body">
                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <div class="border-start border-success border-4 rounded p-3">
                            <h4 class="mb-1"><?php echo formatNumber($exam_stats['passed_count']); ?></h4>
                            <p class="text-muted mb-0">Passed Exams</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border-start border-danger border-4 rounded p-3">
                            <h4 class="mb-1"><?php echo formatNumber($exam_stats['failed_count']); ?></h4>
                            <p class="text-muted mb-0">Failed Exams</p>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="examStatsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Interview Statistics -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h5>Interview Statistics</h5>
            </div>
            <div class="chart-card-body">
                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <div class="border-start border-success border-4 rounded p-3">
                            <h4 class="mb-1"><?php echo formatNumber($interview_stats['passed_interviews']); ?></h4>
                            <p class="text-muted mb-0">Passed Interviews</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border-start border-danger border-4 rounded p-3">
                            <h4 class="mb-1"><?php echo formatNumber($interview_stats['failed_interviews']); ?></h4>
                            <p class="text-muted mb-0">Failed Interviews</p>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="interviewStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.analytics-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Statistics Cards */
.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0;
    font-size: 2rem;
    color: #4e73df;
}

.stat-card p {
    margin: 10px 0 0;
    color: #858796;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.chart-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chart-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.chart-card-header h5 {
    margin: 0;
    color: #2b3a4a;
}

.chart-card-body {
    padding: 20px;
    height: 400px;
    position: relative;
}

.chart-container {
    position: relative;
    height: 100%;
    width: 100%;
}

/* Date Filter */
.date-filter {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.date-filter .form-group {
    margin: 0;
}

.date-filter label {
    margin-right: 10px;
    color: #2b3a4a;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }

    .chart-card-body {
        height: 300px;
    }

    .date-filter {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for charts
const courseLabels = <?php echo json_encode(array_column($course_distribution, 'preferred_course')); ?>;
const courseData = <?php echo json_encode(array_column($course_distribution, 'count')); ?>;
const dateLabels = <?php echo json_encode(array_column($daily_trends, 'date')); ?>;
const applicationData = <?php echo json_encode(array_column($daily_trends, 'count')); ?>;

// Course Distribution Chart
new Chart(document.getElementById('courseDistributionChart'), {
    type: 'pie',
    data: {
        labels: courseLabels,
        datasets: [{
            data: courseData,
            backgroundColor: [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
            ],
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    boxWidth: 12
                }
            }
        }
    }
});

// Daily Application Trends Chart
new Chart(document.getElementById('dailyTrendsChart'), {
    type: 'line',
    data: {
        labels: dateLabels,
        datasets: [{
            label: 'Applications',
            data: applicationData,
            fill: false,
            borderColor: '#4e73df',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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

// Exam Statistics Chart
new Chart(document.getElementById('examStatsChart'), {
    type: 'bar',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{
            data: [
                <?php echo $exam_stats['passed_count']; ?>,
                <?php echo $exam_stats['failed_count']; ?>
            ],
            backgroundColor: ['#1cc88a', '#e74a3b']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Interview Statistics Chart
new Chart(document.getElementById('interviewStatsChart'), {
    type: 'bar',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{
            data: [
                <?php echo $interview_stats['passed_interviews']; ?>,
                <?php echo $interview_stats['failed_interviews']; ?>
            ],
            backgroundColor: ['#1cc88a', '#e74a3b']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php
admin_footer();
?>
