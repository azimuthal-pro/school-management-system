<?php

require_once __DIR__ . '/../config/database.php';

class Attendance {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->connect();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT a.*, s.student_number, u.name as student_name, c.name as class_name, t.name as teacher_name 
                                      FROM attendance a 
                                      LEFT JOIN students s ON a.student_id = s.id 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      LEFT JOIN classes c ON a.class_id = c.id 
                                      LEFT JOIN teachers t ON a.teacher_id = t.id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByClass($classId) {
        $stmt = $this->conn->prepare("SELECT a.*, s.student_number, u.name as student_name, t.name as teacher_name 
                                      FROM attendance a 
                                      LEFT JOIN students s ON a.student_id = s.id 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      LEFT JOIN teachers t ON a.teacher_id = t.id 
                                      WHERE a.class_id = ?");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByStudent($studentId) {
        $stmt = $this->conn->prepare("SELECT a.*, c.name as class_name, t.name as teacher_name 
                                      FROM attendance a 
                                      LEFT JOIN classes c ON a.class_id = c.id 
                                      LEFT JOIN teachers t ON a.teacher_id = t.id 
                                      WHERE a.student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByDate($date) {
        $stmt = $this->conn->prepare("SELECT a.*, s.student_number, u.name as student_name, c.name as class_name 
                                      FROM attendance a 
                                      LEFT JOIN students s ON a.student_id = s.id 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      LEFT JOIN classes c ON a.class_id = c.id 
                                      WHERE a.date = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO attendance (student_id, class_id, teacher_id, date, status) 
                                      VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", 
            $data['student_id'], 
            $data['class_id'], 
            $data['teacher_id'], 
            $data['date'], 
            $data['status']
        );

        if ($stmt->execute()) {
            return $this->getByStudent($data['student_id']);
        }

        return null;
    }

    public function update($id, $data) {
        $updates = [];
        $params = [];
        $types = "";

        if (isset($data['status'])) {
            $updates[] = "status = ?";
            $params[] = $data['status'];
            $types .= "s";
        }

        if (empty($updates)) {
            return $this->getById($id);
        }

        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE attendance SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return $this->getById($id);
        }

        return null;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT a.*, s.student_number, u.name as student_name, c.name as class_name 
                                      FROM attendance a 
                                      LEFT JOIN students s ON a.student_id = s.id 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      LEFT JOIN classes c ON a.class_id = c.id 
                                      WHERE a.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
}
?>