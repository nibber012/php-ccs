<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Send a notification to an applicant
     * @param int $applicant_id The ID of the applicant
     * @param string $type The type of notification (e.g., 'exam_published')
     * @param string $message The notification message
     * @param array $data Additional data related to the notification
     * @return bool Whether the notification was sent successfully
     */
    public function sendNotification($applicant_id, $type, $message, $data = []) {
        try {
            $query = "INSERT INTO notifications (applicant_id, type, message, data, created_at, read_at) 
                     VALUES (?, ?, ?, ?, NOW(), NULL)";
            $stmt = $this->db->getConnection()->prepare($query);
            return $stmt->execute([$applicant_id, $type, $message, json_encode($data)]);
        } catch (Exception $e) {
            error_log("Error sending notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send exam notifications to all eligible applicants
     * @param int $exam_id The ID of the published exam
     * @return array Result of the notification process
     */
    public function sendExamNotifications($exam_id) {
        try {
            // Get exam details
            $query = "SELECT title, type, part FROM exams WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$exam_id]);
            $exam = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$exam) {
                throw new Exception("Exam not found");
            }

            // Get eligible applicants (those who haven't taken this exam yet)
            $query = "SELECT a.id, a.email 
                     FROM applicants a 
                     LEFT JOIN exam_results er ON er.applicant_id = a.id AND er.exam_id = ?
                     WHERE er.id IS NULL AND a.status = 'active'";
            $stmt = $this->db->getConnection()->prepare($query);
            $stmt->execute([$exam_id]);
            $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sent_count = 0;
            foreach ($applicants as $applicant) {
                $message = "A new exam '{$exam['title']}' (Part {$exam['part']}) has been published and is now available for you to take.";
                $data = [
                    'exam_id' => $exam_id,
                    'exam_title' => $exam['title'],
                    'exam_type' => $exam['type'],
                    'exam_part' => $exam['part']
                ];

                if ($this->sendNotification($applicant['id'], 'exam_published', $message, $data)) {
                    $sent_count++;
                }
            }

            return [
                'success' => true,
                'notifications_sent' => $sent_count,
                'total_applicants' => count($applicants)
            ];
        } catch (Exception $e) {
            error_log("Error sending exam notifications: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mark notifications as read for an applicant
     * @param int $applicant_id The ID of the applicant
     * @param array $notification_ids Optional array of specific notification IDs to mark as read
     * @return bool Whether the operation was successful
     */
    public function markAsRead($applicant_id, $notification_ids = []) {
        try {
            if (empty($notification_ids)) {
                $query = "UPDATE notifications SET read_at = NOW() WHERE applicant_id = ? AND read_at IS NULL";
                $stmt = $this->db->getConnection()->prepare($query);
                return $stmt->execute([$applicant_id]);
            } else {
                $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
                $query = "UPDATE notifications SET read_at = NOW() 
                         WHERE applicant_id = ? AND id IN ($placeholders) AND read_at IS NULL";
                $params = array_merge([$applicant_id], $notification_ids);
                $stmt = $this->db->getConnection()->prepare($query);
                return $stmt->execute($params);
            }
        } catch (Exception $e) {
            error_log("Error marking notifications as read: " . $e->getMessage());
            return false;
        }
    }
}
