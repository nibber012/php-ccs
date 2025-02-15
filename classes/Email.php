<?php
class Email {
    private $error;
    private $db;

    public function __construct() {
        // Initialize PHP's mail function settings
        ini_set("SMTP", "localhost");
        ini_set("smtp_port", "25");

        // Initialize database connection
        require_once __DIR__ . '/../config/database.php';
        $database = Database::getInstance();;
        $this->db = $database->getConnection();
    }

    private function logEmail($recipient_email, $recipient_name, $subject, $template_name, $status, $error_message, $related_type, $related_id) {
        try {
            $query = "INSERT INTO email_logs 
                      (recipient_email, recipient_name, subject, template_name, status, error_message, related_type, related_id)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $recipient_email,
                $recipient_name,
                $subject,
                $template_name,
                $status,
                $error_message,
                $related_type,
                $related_id
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to log email: " . $e->getMessage());
            return false;
        }
    }

    private function sendMail($to, $subject, $body, $headers) {
        $sent = mail($to, $subject, $body, $headers);
        return $sent;
    }

    public function sendInterviewSchedule($to_email, $to_name, $interview_data) {
        try {
            $subject = 'Interview Schedule - CCS Screening';
            
            $headers = "From: CCS Screening System <noreply@example.com>\r\n";
            $headers .= "Reply-To: noreply@example.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Interview Schedule Confirmation</h2>
                    <p>Dear {$to_name},</p>
                    <p>Your interview has been scheduled for the CCS Screening process.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Interview Details:</h3>
                        <p><strong>Date:</strong> " . date('F d, Y', strtotime($interview_data['schedule_date'])) . "</p>
                        <p><strong>Time:</strong> " . 
                            date('h:i A', strtotime($interview_data['start_time'])) . " - " . 
                            date('h:i A', strtotime($interview_data['end_time'])) . 
                        "</p>
                        <p><strong>Interviewer:</strong> {$interview_data['interviewer_name']}</p>
                        <p><strong>Meeting Link:</strong> <a href='{$interview_data['meeting_link']}'>{$interview_data['meeting_link']}</a></p>
                    </div>

                    <div style='background-color: #e9ecef; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Important Notes:</h3>
                        <ul style='padding-left: 20px;'>
                            <li>Please click the meeting link above at your scheduled time</li>
                            <li>Test your camera and microphone before joining</li>
                            <li>Ensure you have a stable internet connection</li>
                            <li>Join 5 minutes before the scheduled time</li>
                            <li>Have your resume and documents ready</li>
                        </ul>
                    </div>

                    <p>Best regards,<br>CCS Screening Team</p>
                </div>
            ";

            $sent = $this->sendMail($to_email, $subject, $body, $headers);
            $this->logEmail(
                $to_email,
                $to_name,
                $subject,
                'interview_schedule',
                $sent ? 'sent' : 'failed',
                $sent ? null : $this->error,
                'interview_schedule',
                $interview_data['id']
            );

            return $sent;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function sendInterviewResult($to_email, $to_name, $interview_data) {
        try {
            $subject = 'Interview Results - CCS Screening';
            
            $headers = "From: CCS Screening System <noreply@example.com>\r\n";
            $headers .= "Reply-To: noreply@example.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            // Calculate total score and result
            $total_score = 0;
            $scores_html = "";
            foreach ($interview_data['scores'] as $category => $score) {
                $total_score += $score['score'];
                $scores_html .= "
                    <tr>
                        <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>" . 
                            ucwords(str_replace('_', ' ', $category)) . 
                        "</td>
                        <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$score['score']}</td>
                        <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'>{$score['remarks']}</td>
                    </tr>";
            }
            
            $passed = $total_score >= 70;
            $result_color = $passed ? '#28a745' : '#dc3545';
            $result_text = $passed ? 'Congratulations! You have passed the interview.' : 'We regret to inform you that you did not meet the required score for the interview.';

            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Interview Results</h2>
                    <p>Dear {$to_name},</p>
                    <p>Thank you for attending the interview for the CCS Screening process.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Interview Details:</h3>
                        <p><strong>Date:</strong> " . date('F d, Y', strtotime($interview_data['schedule_date'])) . "</p>
                        <p><strong>Interviewer:</strong> {$interview_data['interviewer_name']}</p>
                    </div>

                    <div style='margin: 20px 0;'>
                        <h3>Interview Scores:</h3>
                        <table style='width: 100%; border-collapse: collapse; margin-bottom: 1rem;'>
                            <thead>
                                <tr style='background-color: #f8f9fa;'>
                                    <th style='padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;'>Category</th>
                                    <th style='padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;'>Score</th>
                                    <th style='padding: 8px; border-bottom: 2px solid #dee2e6; text-align: left;'>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                {$scores_html}
                                <tr style='background-color: #f8f9fa;'>
                                    <td style='padding: 8px; border-bottom: 1px solid #dee2e6;'><strong>Total Score</strong></td>
                                    <td colspan='2' style='padding: 8px; border-bottom: 1px solid #dee2e6;'>
                                        <strong>{$total_score}/100</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style='background-color: " . ($passed ? '#d4edda' : '#f8d7da') . "; color: " . ($passed ? '#155724' : '#721c24') . "; padding: 20px; margin: 20px 0; border-radius: 4px;'>
                        <h3 style='margin-top: 0; color: {$result_color};'>Result</h3>
                        <p style='margin-bottom: 0;'>{$result_text}</p>
                    </div>

                    " . ($passed ? "
                    <div style='background-color: #e9ecef; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Next Steps</h3>
                        <p>We will contact you shortly with information about the next steps in the process.</p>
                    </div>
                    " : "
                    <p>We appreciate your interest in joining our program and wish you the best in your future endeavors.</p>
                    ") . "

                    <p>Best regards,<br>CCS Screening Team</p>
                </div>
            ";

            $sent = $this->sendMail($to_email, $subject, $body, $headers);
            $this->logEmail(
                $to_email,
                $to_name,
                $subject,
                'interview_result',
                $sent ? 'sent' : 'failed',
                $sent ? null : $this->error,
                'interview_result',
                $interview_data['id']
            );

            return $sent;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function sendInterviewReminder($to_email, $to_name, $interview_data) {
        try {
            $subject = 'Interview Reminder - CCS Screening';
            
            $headers = "From: CCS Screening System <noreply@example.com>\r\n";
            $headers .= "Reply-To: noreply@example.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Interview Reminder</h2>
                    <p>Dear {$to_name},</p>
                    <p>This is a reminder about your upcoming interview.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Interview Details:</h3>
                        <p><strong>Date:</strong> " . date('F d, Y', strtotime($interview_data['schedule_date'])) . "</p>
                        <p><strong>Time:</strong> " . 
                            date('h:i A', strtotime($interview_data['start_time'])) . " - " . 
                            date('h:i A', strtotime($interview_data['end_time'])) . 
                        "</p>
                        <p><strong>Interviewer:</strong> {$interview_data['interviewer_name']}</p>
                        <p><strong>Meeting Link:</strong> <a href='{$interview_data['meeting_link']}'>{$interview_data['meeting_link']}</a></p>
                    </div>

                    <div style='background-color: #e9ecef; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Reminder:</h3>
                        <ul style='padding-left: 20px;'>
                            <li>Click the meeting link above at your scheduled time</li>
                            <li>Join 5 minutes early to test your audio/video</li>
                            <li>Ensure you're in a quiet environment</li>
                        </ul>
                    </div>

                    <p>Best regards,<br>CCS Screening Team</p>
                </div>
            ";

            $sent = $this->sendMail($to_email, $subject, $body, $headers);
            $this->logEmail(
                $to_email,
                $to_name,
                $subject,
                'interview_reminder',
                $sent ? 'sent' : 'failed',
                $sent ? null : $this->error,
                'interview_reminder',
                $interview_data['id']
            );

            return $sent;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function sendInterviewCancellation($to_email, $to_name, $interview_data) {
        try {
            $subject = 'Interview Cancellation - CCS Screening';
            
            $headers = "From: CCS Screening System <noreply@example.com>\r\n";
            $headers .= "Reply-To: noreply@example.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2>Interview Cancellation Notice</h2>
                    <p>Dear {$to_name},</p>
                    <p>We regret to inform you that your scheduled interview has been cancelled.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; margin: 20px 0;'>
                        <h3 style='margin-top: 0;'>Cancelled Interview Details:</h3>
                        <p><strong>Date:</strong> " . date('F d, Y', strtotime($interview_data['schedule_date'])) . "</p>
                        <p><strong>Time:</strong> " . 
                            date('h:i A', strtotime($interview_data['start_time'])) . " - " . 
                            date('h:i A', strtotime($interview_data['end_time'])) . 
                        "</p>
                        <p><strong>Meeting Link:</strong> <a href='{$interview_data['meeting_link']}'>{$interview_data['meeting_link']}</a></p>
                    </div>

                    " . ($interview_data['cancel_reason'] ? "
                    <div style='margin: 20px 0;'>
                        <h3>Reason for Cancellation:</h3>
                        <p>" . nl2br(htmlspecialchars($interview_data['cancel_reason'])) . "</p>
                    </div>
                    " : "") . "

                    <p>We will contact you shortly to reschedule your interview.</p>
                    <p>Best regards,<br>CCS Screening Team</p>
                </div>
            ";

            $sent = $this->sendMail($to_email, $subject, $body, $headers);
            $this->logEmail(
                $to_email,
                $to_name,
                $subject,
                'interview_cancellation',
                $sent ? 'sent' : 'failed',
                $sent ? null : $this->error,
                'interview_cancellation',
                $interview_data['id']
            );

            return $sent;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function getError() {
        return $this->error;
    }
}
