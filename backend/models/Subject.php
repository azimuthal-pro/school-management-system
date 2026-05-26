<?php

require_once __DIR__ . '/../config/database.php';

class Subject {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT s.*, u.name as teacher_name FROM subjects s 
                                      LEFT JOIN teachers t ON s.teacher_id = t.id 
                                      LEFT JOIN users u ON t.user_id = u.id 
                                      ORDER BY s.name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT s.*, u.name as teacher_name FROM subjects s 
                                      LEFT JOIN teachers t ON s.teacher_id = t.id 
                                      LEFT JOIN users u ON t.user_id = u.id 
                                      WHERE s.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO subjects (name, code, teacher_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", 
            $data['name'], 
            $data['code'], 
            $data['teacher_id'] ?? null
        );

        if ($stmt->execute()) {
            return $this->getById($this->conn->insert_id);
        }

        return null;
    }

    public function update($id, $data) {
        $updates = [];
        $params = [];
        $types = "";

        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
            $types .= "s";
        }
        if (isset($data['code'])) {
            $updates[] = "code = ?";
            $params[] = $data['code'];
            $types .= "s";
        }
        if (isset($data['teacher_id'])) {
            $updates[] = "teacher_id = ?";
            $params[] = $data['teacher_id'];
            $types .= "i";
        }

        if (empty($updates)) {
            return $this->getById($id);
        }

        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE subjects SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return $this->getById($id);
        }

        return null;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }

    public function getByTeacher($teacherId) {
        $stmt = $this->conn->prepare("SELECT * FROM subjects WHERE teacher_id = ? ORDER BY name ASC");
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findByCode($code) {
        $stmt = $this->conn->prepare("SELECT * FROM subjects WHERE code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}
?>
