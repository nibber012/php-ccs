<?php
require_once '../classes/Auth.php';
require_once '../classes/Exam.php';
require_once '../includes/layout.php';

$auth = new Auth();
$auth->requireRole('applicant');

$exam = new Exam();
$user = $auth->getCurrentUser();

// Get applicant details
$query = "SELECT * FROM applicants WHERE user_id = ?";
$stmt = $exam->getConnection()->prepare($query);
$stmt->execute([$user['id']]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

// Get available exams based on progress
$available_exams = [];
$query = "SELECT e.* FROM exams e 
          WHERE e.status = 'published' 
          AND NOT EXISTS (
              SELECT 1 FROM exam_results er 
              WHERE er.exam_id = e.id 
              AND er.applicant_id = ?
          )";
$stmt = $exam->getConnection()->prepare($query);
$stmt->execute([$applicant['id']]);
$available_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

get_header('Take Exam');
get_sidebar('applicant');
?>

<div class="content">
    <div class="container-fluid">
        <?php if(isset($_GET['exam_id'])): ?>
            <?php
            $current_exam = $exam->getExam($_GET['exam_id']);
            $questions = $exam->getExamQuestions($_GET['exam_id']);
            $progress = $exam->getApplicantProgress($applicant['id'], $_GET['exam_id']);
            
            // Check if exam is completed
            if($exam->isExamCompleted($applicant['id'], $_GET['exam_id'])) {
                $result = $exam->getExamResult($applicant['id'], $_GET['exam_id']);
            ?>
                <div class="card">
                    <div class="card-body text-center">
                        <h3>Exam Completed</h3>
                        <p class="lead">Your score: <?php echo $result['score']; ?> / <?php echo $result['passing_score']; ?></p>
                        <p class="h4 <?php echo $result['status'] === 'pass' ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $result['status'] === 'pass' ? 'Passed' : 'Failed'; ?>
                        </p>
                        <a href="exam.php" class="btn btn-primary mt-3">Back to Exams</a>
                    </div>
                </div>
            <?php } else { ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($current_exam['title']); ?></h5>
                            <div class="timer" id="examTimer" data-duration="<?php echo $current_exam['duration_minutes']; ?>">
                                Time remaining: <span id="timeDisplay"></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="examForm" method="POST" action="exam_process.php">
                            <input type="hidden" name="action" value="submit_exam">
                            <input type="hidden" name="exam_id" value="<?php echo $_GET['exam_id']; ?>">
                            
                            <?php foreach($questions as $index => $question): ?>
                                <div class="question-container mb-4">
                                    <h5>Question <?php echo $index + 1; ?></h5>
                                    <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                    
                                    <?php if($question['question_type'] === 'multiple_choice'): ?>
                                        <?php $options = json_decode($question['options'], true); ?>
                                        <div class="options-container">
                                            <?php foreach($options as $option): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="answers[<?php echo $question['id']; ?>]" 
                                                           value="<?php echo htmlspecialchars($option); ?>"
                                                           id="q<?php echo $question['id']; ?>_<?php echo md5($option); ?>">
                                                    <label class="form-check-label" for="q<?php echo $question['id']; ?>_<?php echo md5($option); ?>">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="coding-container">
                                            <textarea class="form-control code-editor" 
                                                      name="answers[<?php echo $question['id']; ?>]" 
                                                      rows="10"
                                                      data-template="<?php echo htmlspecialchars($question['coding_template']); ?>"
                                            ><?php echo $question['coding_template']; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Submit Exam</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    // Timer functionality
                    function startTimer(duration) {
                        let timer = duration * 60;
                        const display = document.getElementById('timeDisplay');
                        
                        const countdown = setInterval(function() {
                            const minutes = parseInt(timer / 60, 10);
                            const seconds = parseInt(timer % 60, 10);
                            
                            display.textContent = minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                            
                            if(--timer < 0) {
                                clearInterval(countdown);
                                document.getElementById('examForm').submit();
                            }
                        }, 1000);
                    }

                    // Start the timer
                    const duration = document.getElementById('examTimer').dataset.duration;
                    startTimer(duration);

                    // Prevent tab switching
                    document.addEventListener('visibilitychange', function() {
                        if(document.hidden) {
                            alert('Warning: Switching tabs is not allowed during the exam!');
                        }
                    });

                    // Prevent copy-paste
                    document.addEventListener('copy', function(e) {
                        e.preventDefault();
                        alert('Copying is not allowed during the exam!');
                    });
                    
                    document.addEventListener('paste', function(e) {
                        e.preventDefault();
                        alert('Pasting is not allowed during the exam!');
                    });

                    // Auto-save answers periodically
                    const autoSave = setInterval(function() {
                        const formData = new FormData(document.getElementById('examForm'));
                        formData.append('action', 'auto_save');
                        
                        fetch('exam_process.php', {
                            method: 'POST',
                            body: formData
                        });
                    }, 30000); // Auto-save every 30 seconds
                </script>
            <?php } ?>
        <?php else: ?>
            <h1 class="h2 mb-4">Available Exams</h1>
            
            <?php if(empty($available_exams)): ?>
                <div class="alert alert-info">
                    No exams are available at this time. Please check back later.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($available_exams as $available_exam): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($available_exam['title']); ?></h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($available_exam['description'])); ?></p>
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-clock"></i> Duration: <?php echo $available_exam['duration_minutes']; ?> minutes</li>
                                        <li><i class="bi bi-award"></i> Passing Score: <?php echo $available_exam['passing_score']; ?></li>
                                        <li><i class="bi bi-file-text"></i> Type: <?php echo ucfirst($available_exam['type']); ?></li>
                                    </ul>
                                </div>
                                <div class="card-footer">
                                    <a href="?exam_id=<?php echo $available_exam['id']; ?>" class="btn btn-primary">Start Exam</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
