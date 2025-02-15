<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../includes/layout.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();;

// Get exam details if exam_id is provided
$exam_id = $_GET['exam_id'] ?? null;
$exam = null;
$questions = [];

if ($exam_id) {
    try {
        // Get exam details
        $stmt = $db->query("SELECT * FROM exams WHERE id = ?", [$exam_id]);
        $exam = $stmt->fetch();
        
        if ($exam) {
            // Get exam questions
            $stmt = $db->query("SELECT * FROM exam_questions WHERE exam_id = ? ORDER BY question_order", [$exam_id]);
            $questions = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Exam Error: " . $e->getMessage());
    }
}

get_header('Exams');
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php get_sidebar('applicant'); ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php if ($exam): ?>
                <!-- Exam Timer -->
                <div id="examTimer" class="exam-timer">
                    <div class="timer-content">
                        <i class="bx bx-time"></i>
                        <span id="timeLeft"></span>
                    </div>
                </div>

                <!-- Exam Content -->
                <div class="exam-container">
                    <h1 class="h2 mb-4"><?php echo htmlspecialchars($exam['title']); ?></h1>
                    
                    <form id="examForm" method="POST" action="submit_exam.php">
                        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                        <input type="hidden" name="time_remaining" id="timeRemaining">
                        
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Question <?php echo $index + 1; ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                    
                                    <?php
                                    // Get choices for this question
                                    $stmt = $db->query("SELECT * FROM question_choices WHERE question_id = ? ORDER BY RAND()", [$question['id']]);
                                    $choices = $stmt->fetchAll();
                                    
                                    foreach ($choices as $choice):
                                    ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" 
                                                   name="answer[<?php echo $question['id']; ?>]" 
                                                   id="choice_<?php echo $choice['id']; ?>" 
                                                   value="<?php echo $choice['id']; ?>" required>
                                            <label class="form-check-label" for="choice_<?php echo $choice['id']; ?>">
                                                <?php echo htmlspecialchars($choice['choice_text']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                            <button type="submit" class="btn btn-primary" id="submitExam">
                                Submit Exam
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Available Exams List -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Available Exams</h1>
                </div>

                <div class="row">
                    <?php
                    // Get available exams
                    $stmt = $db->query(
                        "SELECT e.* FROM exams e 
                         LEFT JOIN exam_results er ON er.exam_id = e.id AND er.user_id = ?
                         WHERE e.status = 'active' AND er.id IS NULL", 
                        [$user_id]
                    );
                    $available_exams = $stmt->fetchAll();

                    if (empty($available_exams)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No exams are currently available for you to take.
                            </div>
                        </div>
                    <?php else:
                        foreach ($available_exams as $exam):
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($exam['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">
                                            <i class="bx bx-time"></i> <?php echo $exam['duration_minutes']; ?> minutes
                                        </span>
                                        <a href="?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary">
                                            Start Exam
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<style>
.exam-timer {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    background: #fff;
    padding: 10px 20px;
    border-radius: 50px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.timer-content {
    display: flex;
    align-items: center;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.timer-content i {
    margin-right: 8px;
    color: #e74c3c;
}

.exam-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 20px;
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-check-input:checked {
    background-color: #3498db;
    border-color: #3498db;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($exam): ?>
    // Initialize timer
    let duration = <?php echo $exam['duration_minutes'] ?? 0; ?> * 60; // Convert minutes to seconds
    let timeLeft = duration;
    
    function updateTimer() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        // Update display
        document.getElementById('timeLeft').textContent = 
            `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Update hidden input
        document.getElementById('timeRemaining').value = timeLeft;
        
        if (timeLeft <= 0) {
            // Time's up
            clearInterval(timerInterval);
            alert('Time is up! Your exam will be submitted automatically.');
            document.getElementById('examForm').submit();
        } else {
            timeLeft--;
        }
    }

    // Start timer
    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);

    // Handle form submission
    document.getElementById('examForm').addEventListener('submit', function(e) {
        clearInterval(timerInterval);
    });

    // Prevent leaving the page
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = '';
    });
    <?php endif; ?>
});
</script>

<?php get_footer(); ?>
