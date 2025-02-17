<?php
require_once __DIR__ . '/../config/database.php';

class Exam {
    private $conn;

    public function __construct() {
        $database = Database::getInstance();;
        $this->conn = $database->getConnection();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function createExam($title, $description, $type, $duration_minutes, $passing_score, $created_by) {
        $query = "INSERT INTO exams (title, description, type, duration_minutes, passing_score, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $description, $type, $duration_minutes, $passing_score, $created_by]);
    }

    public function addQuestion($exam_id, $question_text, $question_type, $points, $options = null, $correct_answer = null, $coding_template = null, $test_cases = null) {
        $query = "INSERT INTO questions (exam_id, question_text, question_type, points, options, correct_answer, coding_template, test_cases) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $exam_id,
            $question_text,
            $question_type,
            $points,
            $options ? json_encode($options) : null,
            $correct_answer,
            $coding_template,
            $test_cases ? json_encode($test_cases) : null
        ]);
    }

    public function getExam($exam_id) {
        $query = "SELECT * FROM exams WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$exam_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getExamQuestions($exam_id) {
        $query = "SELECT * FROM questions WHERE exam_id = ? ORDER BY id";
        error_log("DEBUG: Query - $query, Exam ID - $exam_id");
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$exam_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function submitAnswer($applicant_id, $exam_id, $question_id, $answer) {
        // First, check if an answer already exists
        $query = "SELECT id FROM applicant_answers 
                  WHERE applicant_id = ? AND exam_id = ? AND question_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$applicant_id, $exam_id, $question_id]);
        
        if($stmt->rowCount() > 0) {
            // Update existing answer
            $query = "UPDATE applicant_answers 
                     SET answer = ?, submitted_at = CURRENT_TIMESTAMP 
                     WHERE applicant_id = ? AND exam_id = ? AND question_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$answer, $applicant_id, $exam_id, $question_id]);
        } else {
            // Insert new answer
            $query = "INSERT INTO applicant_answers (applicant_id, exam_id, question_id, answer) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$applicant_id, $exam_id, $question_id, $answer]);
        }
    }

    public function gradeMultipleChoice($applicant_id, $exam_id) {
        // Get all MCQ questions and answers for this exam
        $query = "SELECT q.id, q.correct_answer, aa.answer, q.points 
                  FROM questions q 
                  LEFT JOIN applicant_answers aa ON q.id = aa.question_id 
                  AND aa.applicant_id = ? 
                  WHERE q.exam_id = ? AND q.question_type = 'multiple_choice'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$applicant_id, $exam_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_score = 0;
        foreach($questions as $question) {
            $is_correct = $question['answer'] === $question['correct_answer'];
            $score = $is_correct ? $question['points'] : 0;
            $total_score += $score;

            // Update the answer record with the score and correctness
            $query = "UPDATE applicant_answers 
                     SET is_correct = ?, score = ? 
                     WHERE applicant_id = ? AND exam_id = ? AND question_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$is_correct, $score, $applicant_id, $exam_id, $question['id']]);
        }

        // Get exam details for passing score
        $exam = $this->getExam($exam_id);
        $status = $total_score >= $exam['passing_score'] ? 'pass' : 'fail';

        // Record the final result
        $query = "INSERT INTO exam_results (applicant_id, exam_id, score, passing_score, status) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$applicant_id, $exam_id, $total_score, $exam['passing_score'], $status]);

        return [
            'total_score' => $total_score,
            'passing_score' => $exam['passing_score'],
            'status' => $status
        ];
    }

    public function getApplicantProgress($applicant_id, $exam_id) {
        $query = "SELECT 
                    COUNT(DISTINCT aa.question_id) as answered_questions,
                    (SELECT COUNT(*) FROM questions WHERE exam_id = ?) as total_questions,
                    e.duration_minutes,
                    TIMESTAMPDIFF(
                        MINUTE, 
                        MIN(aa.submitted_at), 
                        CURRENT_TIMESTAMP
                    ) as elapsed_minutes
                  FROM applicant_answers aa
                  JOIN exams e ON e.id = aa.exam_id
                  WHERE aa.applicant_id = ? AND aa.exam_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$exam_id, $applicant_id, $exam_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isExamCompleted($applicant_id, $exam_id) {
        $query = "SELECT id FROM exam_results 
                  WHERE applicant_id = ? AND exam_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$applicant_id, $exam_id]);
        return $stmt->rowCount() > 0;
    }

    public function getExamResult($applicant_id, $exam_id) {
        $query = "SELECT * FROM exam_results 
                  WHERE applicant_id = ? AND exam_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$applicant_id, $exam_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
