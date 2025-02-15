<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'applicant/exams.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$exam_id = $_POST['exam_id'] ?? null;
$answers = $_POST['answer'] ?? [];
$time_remaining = $_POST['time_remaining'] ?? 0;

if (!$exam_id || empty($answers)) {
    $_SESSION['error'] = 'Invalid exam submission.';
    header('Location: ' . BASE_URL . 'applicant/exams.php');
    exit;
}

try {
    $db = Database::getInstance();;
    
    // Start transaction
    $db->beginTransaction();
    
    // Get exam details
    $stmt = $db->query("SELECT * FROM exams WHERE id = ?", [$exam_id]);
    $exam = $stmt->fetch();
    
    if (!$exam) {
        throw new Exception('Exam not found.');
    }
    
    // Calculate score
    $total_questions = 0;
    $correct_answers = 0;
    
    foreach ($answers as $question_id => $choice_id) {
        $stmt = $db->query(
            "SELECT is_correct FROM question_choices WHERE id = ? AND question_id = ?",
            [$choice_id, $question_id]
        );
        $choice = $stmt->fetch();
        
        if ($choice) {
            $total_questions++;
            if ($choice['is_correct']) {
                $correct_answers++;
            }
        }
    }
    
    // Calculate percentage score
    $score = ($total_questions > 0) ? ($correct_answers / $total_questions) * 100 : 0;
    
    // Calculate completion time in minutes
    $completion_time = ($exam['duration_minutes'] - ($time_remaining / 60));
    
    // Save exam result
    $db->query(
        "INSERT INTO exam_results (user_id, exam_id, score, completion_time, created_at) 
         VALUES (?, ?, ?, ?, NOW())",
        [$user_id, $exam_id, $score, $completion_time]
    );
    
    // Save detailed answers
    foreach ($answers as $question_id => $choice_id) {
        $db->query(
            "INSERT INTO exam_answers (user_id, exam_id, question_id, choice_id, created_at)
             VALUES (?, ?, ?, ?, NOW())",
            [$user_id, $exam_id, $question_id, $choice_id]
        );
    }
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success'] = 'Exam submitted successfully! Your score: ' . number_format($score, 2) . '%';
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Exam Submission Error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to submit exam. Please try again.';
}

header('Location: ' . BASE_URL . 'applicant/results.php');
exit;
