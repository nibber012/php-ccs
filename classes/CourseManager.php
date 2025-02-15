<?php

class CourseManager {
    private $database;

    public function __construct() {
        $this->database = Database::getInstance();
    }

    public function addCourse($code, $name, $description = '') {
        $query = "INSERT INTO courses (code, name, description) VALUES (?, ?, ?)";
        $stmt = $this->database->prepare($query);
        return $stmt->execute([$code, $name, $description]);
    }

    public function updateCourse($id, $code, $name, $description = '') {
        $query = "UPDATE courses SET code = ?, name = ?, description = ? WHERE id = ?";
        $stmt = $this->database->prepare($query);
        return $stmt->execute([$code, $name, $description, $id]);
    }

    public function toggleCourseStatus($id) {
        $query = "UPDATE courses SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?";
        $stmt = $this->database->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getCourses($activeOnly = true) {
        $query = "SELECT * FROM courses";
        if ($activeOnly) {
            $query .= " WHERE status = 'active'";
        }
        $query .= " ORDER BY code ASC";
        
        $stmt = $this->database->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCourse($id) {
        $query = "SELECT * FROM courses WHERE id = ?";
        $stmt = $this->database->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
