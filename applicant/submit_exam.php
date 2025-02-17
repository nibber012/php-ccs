<?php
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../config/database.php';

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

// Removee after debugging

error_log("DEBUG: Exam Submission - User ID: $user_id, Exam ID: $exam_id");
error_log("DEBUG: Time Remaining - $time_remaining");
error_log("DEBUG: Answers - " . print_r($answers, true));


if (!$exam_id || empty($answers)) {
    $_SESSION['error'] = 'Invalid exam submission.';
    header('Location: ' . BASE_URL . 'applicant/exams.php');
    exit;
}

try {
    $db = Database::getInstance()->getConnection(); // Get the PDO connection

    // Start transaction
    $db->beginTransaction();
    
    // Get exam details
// Check if exam has already been started by this user
$stmt = $db->prepare("SELECT * FROM exam_results WHERE applicant_id = ? AND exam_id = ?");
$stmt->execute([$user_id, $exam_id]);
$existing_result = $stmt->fetch();

if ($existing_result) {
    error_log("ERROR: User {$user_id} has already submitted Exam ID: {$exam_id}.");
    $_SESSION['error'] = "You have already submitted this exam!";
    header('Location: ' . BASE_URL . 'applicant/results.php');
    exit;
}

// Log Exam Details
$stmt = $db->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    error_log("ERROR: Exam ID {$exam_id} not found.");
    $_SESSION['error'] = "The exam you are trying to submit does not exist!";
    header('Location: ' . BASE_URL . 'applicant/exams.php');
    exit;
}

error_log("DEBUG: Exam Found - ID: {$exam['id']}, Title: {$exam['title']}, Passing Score: {$exam['passing_score']}");


    //Remove when done debugging
    if (!$exam) {
        error_log("ERROR: Exam ID $exam_id not found in database.");
    } else {
        error_log("DEBUG: Found Exam - ID: {$exam['id']}, Title: {$exam['title']}, Passing Score: {$exam['passing_score']}");
    }
    

    if (!$exam) {
        throw new Exception('Exam not found.');
    }

    // Initialize score tracking
    $total_questions = 0;
    $correct_answers = 0;
    $total_score = 0;

    
    foreach ($answers as $question_id => $user_answer) {
        // Fetch question details
        $stmt = $db->prepare("SELECT options, correct_answer, points FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
    
        if ($question) {
            // Decode options JSON into an array
            $options = json_decode($question['options'], true);
            
            // Ensure options exist and are an array
            if (is_array($options)) {
                // Find the index of the user's selected answer in the options array
                $user_answer_index = array_search($user_answer, $options);
                $user_answer_index_str = ($user_answer_index !== false) ? (string) $user_answer_index : "N/A";
    
                // Compare user-selected index with stored correct answer
                $is_correct = ($user_answer_index_str === trim($question['correct_answer'])) ? 1 : 0;
    
                if ($is_correct) {
                    $total_score += (int) $question['points']; // ✅ Increment score correctly
                    $correct_answers++;
                }
    
                $total_questions++;
    
                // 🔍 Debugging Log
                error_log("DEBUG: Question ID: {$question_id} | Options: " . json_encode($options) . 
                    " | Correct Answer (Stored: {$question['correct_answer']}, Index Value: " . 
                    ($options[(int) $question['correct_answer']] ?? "UNKNOWN") . ") | User Answer: {$user_answer} | " .
                    "User Index: {$user_answer_index_str} | Result: " . ($is_correct ? '✅ CORRECT' : '❌ INCORRECT') . 
                    " | Points Earned: " . ($is_correct ? $question['points'] : 0));
            } else {
                error_log("ERROR: Question ID {$question_id} has invalid options format.");
            }
        } else {
            error_log("ERROR: Question ID {$question_id} not found.");
        }
    }
    
    
    
    // Calculate percentage score
    // ✅ Calculate total earned points instead of percentage
$score = $total_score; // Total points accumulated
$passing_score = (int) $exam['passing_score']; // Ensure integer comparison

// Debug Log
error_log("DEBUG: Final Score Calculation - Total Score: {$score}, Required Passing Score: {$passing_score}");



    
    // Calculate completion time in minutes
// Fetch started_at from the database
$stmt = $db->prepare(
    "SELECT started_at FROM exam_results WHERE applicant_id = ? AND exam_id = ?"
);
$stmt->execute([$user_id, $exam_id]);
$result = $stmt->fetch();

if ($result && $result['started_at']) {
    $started_at = new DateTime($result['started_at']);
    $completed_at = new DateTime(); // Current time
    $completion_time = $started_at->diff($completed_at)->i; // Completion time in minutes
} else {
    $completion_time = null; // Fallback in case started_at is not available
}

    
    // Save exam result
// 🔍 Get the correct applicant_id from applicants table
$stmt = $db->prepare("SELECT id, user_id FROM applicants WHERE user_id = ?");
$stmt->execute([$user_id]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC); // Ensure fetching as an associative array

// Log the fetched applicant data
if ($applicant) {
    error_log("DEBUG: Successfully fetched applicant: " . print_r($applicant, true));
} else {
    error_log("ERROR: Fetch failed - No matching applicant found for User ID: {$user_id}.");
}





$applicant_id = $applicant['user_id']; // ✅ Now it correctly gets 14
error_log("DEBUG: Correct Applicant ID Retrieved - {$applicant_id}");
error_log("DEBUG: Retrieved Applicant ID: {$applicant_id} for User ID: {$user_id}");
error_log("DEBUG: Exam Details - ID: {$exam_id}, Passing Score: {$exam['passing_score']}");


// ✅ Now, insert into exam_results with applicant_id
error_log("DEBUG: Preparing to insert into exam_results:");
error_log("DEBUG: Exam Results Insert Details:");
error_log("Applicant ID: {$applicant_id}");
error_log("Exam ID: {$exam_id}");
error_log("Total Score: {$score}");
error_log("Passing Score: {$passing_score}");
error_log("Status: " . ($score >= $passing_score ? '✅ PASS' : '❌ FAIL'));

error_log("Applicant ID: {$applicant_id}");
error_log("Exam ID: {$exam_id}");
error_log("Score: {$score}");
error_log("Passing Score: {$exam['passing_score']}");
error_log("Status: " . ($score >= $exam['passing_score'] ? 'pass' : 'fail'));

$stmt = $db->prepare(
    "INSERT INTO exam_results (applicant_id, exam_id, score, passing_score, status, created_at) 
     VALUES (?, ?, ?, ?, ?, NOW())"
);

if ($stmt->execute([$applicant_id, $exam_id, $score, $exam['passing_score'], $score >= $exam['passing_score'] ? 'pass' : 'fail'])) {
    error_log("SUCCESS: Exam result inserted for Applicant ID: {$applicant_id}, Exam ID: {$exam_id}");
} else {
    error_log("ERROR: Failed to insert exam result for Applicant ID: {$applicant_id}, Exam ID: {$exam_id}");
}

    // Save detailed answers to applicant_answers table
    foreach ($answers as $question_id => $user_answer) {
        // Check if the user's answer matches the correct answer
        $stmt = $db->prepare(
            "SELECT correct_answer FROM questions WHERE id = ?"
        );
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
    
        $is_correct = ($question && $user_answer === $question['correct_answer']) ? 1 : 0;
        $score = $is_correct ? $question['points'] : 0;
    
        // Insert answer into applicant_answers table
        $stmt = $db->prepare(
            "INSERT INTO applicant_answers (applicant_id, exam_id, question_id, answer, is_correct, score, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        error_log("DEBUG: Preparing to insert answer:");
error_log("Applicant ID: {$applicant_id}");
error_log("Exam ID: {$exam_id}");
error_log("Question ID: {$question_id}");
error_log("User Answer: {$user_answer}");
error_log("Correct: " . ($is_correct ? '✅ Yes' : '❌ No'));
error_log("Score: {$score}");



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
