<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Email.php';

try {
    $database = Database::getInstance();;
    $conn = $database->getConnection();

    // Get interviews scheduled for tomorrow
    $query = "SELECT 
                i.*,
                CONCAT(a.first_name, ' ', a.last_name) as applicant_name,
                u.email,
                CONCAT(u2.first_name, ' ', u2.last_name) as interviewer_name
              FROM interview_schedules i
              JOIN applicants a ON i.applicant_id = a.id
              JOIN users u ON a.user_id = u.id
              JOIN users u2 ON i.interviewer_id = u2.id
              WHERE i.schedule_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
              AND i.status = 'scheduled'";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $interviews = $stmt->fetchAll();

    $email = new Email();
    $sent_count = 0;
    $error_count = 0;

    foreach ($interviews as $interview) {
        if ($email->sendInterviewReminder($interview['email'], $interview['applicant_name'], $interview)) {
            $sent_count++;
        } else {
            $error_count++;
            error_log("Failed to send interview reminder to {$interview['email']}: " . $email->getError());
        }
    }

    echo "Reminder emails sent: $sent_count\n";
    echo "Errors encountered: $error_count\n";

} catch (Exception $e) {
    error_log("Error in interview reminder cron: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
