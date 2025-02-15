<?php
require_once '../classes/Auth.php';
require_once '../classes/Exam.php';
require_once '../classes/Notification.php';

$auth = new Auth();
$auth->requireRole(['admin', 'super_admin']);

$exam = new Exam();
$notification = new Notification();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'create':
        $result = $exam->createExam(
            $_POST['title'],
            $_POST['description'],
            $_POST['type'],
            $_POST['duration_minutes'],
            $_POST['passing_score'],
            $_SESSION['user_id']
        );
        header('Location: exams.php' . ($result ? '?success=1' : '?error=1'));
        break;

    case 'edit':
        $query = "UPDATE exams SET 
                    title = ?, 
                    description = ?, 
                    type = ?, 
                    duration_minutes = ?, 
                    passing_score = ? 
                  WHERE id = ?";
        $stmt = $exam->getConnection()->prepare($query);
        $result = $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['type'],
            $_POST['duration_minutes'],
            $_POST['passing_score'],
            $_POST['id']
        ]);
        header('Location: exams.php' . ($result ? '?success=1' : '?error=1'));
        break;

    case 'add_question':
        $options = isset($_POST['options']) ? $_POST['options'] : null;
        $correct_answer = null;
        
        if($_POST['question_type'] === 'multiple_choice') {
            $correct_answer = $options[$_POST['correct_answer']];
        }
        
        $result = $exam->addQuestion(
            $_POST['exam_id'],
            $_POST['question_text'],
            $_POST['question_type'],
            $_POST['points'],
            $options,
            $correct_answer,
            $_POST['coding_template'] ?? null,
            $_POST['test_cases'] ?? null
        );
        
        header('Location: exams.php?action=questions&id=' . $_POST['exam_id'] . ($result ? '&success=1' : '&error=1'));
        break;

    case 'delete_question':
        $query = "DELETE FROM questions WHERE id = ?";
        $stmt = $exam->getConnection()->prepare($query);
        $result = $stmt->execute([$_GET['id']]);
        header('Location: exams.php?action=questions&id=' . $_GET['exam_id'] . ($result ? '&success=1' : '&error=1'));
        break;

    case 'publish':
        $query = "UPDATE exams SET status = 'published' WHERE id = ?";
        $stmt = $exam->getConnection()->prepare($query);
        $result = $stmt->execute([$_GET['id']]);
        
        if ($result) {
            // Send notifications to eligible applicants
            $notification_result = $notification->sendExamNotifications($_GET['id']);
            if (!$notification_result['success']) {
                error_log("Error sending exam notifications: " . $notification_result['error']);
            }
        }
        
        header('Location: exams.php' . ($result ? '?success=1' : '?error=1'));
        break;

    default:
        header('Location: exams.php?error=1');
        break;
}
?>
