<?php
require_once '../classes/Auth.php';
require_once '../classes/Exam.php';

$auth = new Auth();
$auth->requireRole('applicant');

$exam = new Exam();
$user = $auth->getCurrentUser();

// Get applicant ID
$query = "SELECT id FROM applicants WHERE user_id = ?";
$stmt = $exam->getConnection()->prepare($query);
$stmt->execute([$user['id']]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

$action = $_POST['action'] ?? '';

switch($action) {
    case 'submit_exam':
        $exam_id = $_POST['exam_id'];
        $answers = $_POST['answers'] ?? [];
        
        // Save all answers
        foreach($answers as $question_id => $answer) {
            $exam->submitAnswer($applicant['id'], $exam_id, $question_id, $answer);
        }
        
        // Get exam type
        $current_exam = $exam->getExam($exam_id);
        
        if($current_exam['type'] === 'mcq') {
            // Grade MCQ exam automatically
            $result = $exam->gradeMultipleChoice($applicant['id'], $exam_id);
            
            // Update applicant progress
            $query = "UPDATE applicants SET progress_status = ? WHERE id = ?";
            $stmt = $exam->getConnection()->prepare($query);
            $stmt->execute([
                $result['status'] === 'pass' ? 'part1_completed' : 'failed',
                $applicant['id']
            ]);
        } else {
            // For coding exam, mark it as completed but needs manual review
            $query = "UPDATE applicants SET progress_status = 'part2_completed' WHERE id = ?";
            $stmt = $exam->getConnection()->prepare($query);
            $stmt->execute([$applicant['id']]);
            
            // Create a notification for admin review
            $query = "INSERT INTO notifications (user_id, title, message, type) 
                     SELECT id, 'Coding Exam Submitted', 'A new coding exam needs review', 'exam_review'
                     FROM users WHERE role IN ('admin', 'super_admin')";
            $stmt = $exam->getConnection()->prepare($query);
            $stmt->execute();
        }
        
        header('Location: exam.php?exam_id=' . $exam_id);
        break;
        
    case 'auto_save':
        $exam_id = $_POST['exam_id'];
        $answers = $_POST['answers'] ?? [];
        
        foreach($answers as $question_id => $answer) {
            $exam->submitAnswer($applicant['id'], $exam_id, $question_id, $answer);
        }
        
        echo json_encode(['success' => true]);
        break;
        
    default:
        header('Location: exam.php');
        break;
}
?>
