<?php

require_once __DIR__ . '/../config/database.php';

class Student {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->connect();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT s.*, u.name, u.email, c.name as class_name FROM students s 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      LEFT JOIN classes c ON s.class_id = c.id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT s.*, u.name, u.email, c.name as class_name FROM students s 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      LEFT JOIN classes c ON s.class_id = c.id 
                                      WHERE s.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $dateOfBirth = $data['date_of_birth'] ?? null;
        $address = $data['address'] ?? null;

        $stmt = $this->conn->prepare("INSERT INTO students (user_id, student_number, class_id, date_of_birth, address) 
                                      VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", 
            $data['user_id'], 
            $data['student_number'], 
            $data['class_id'], 
            $dateOfBirth, 
            $address
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

        if (isset($data['class_id'])) {
            $updates[] = "class_id = ?";
            $params[] = $data['class_id'];
            $types .= "i";
        }
        if (isset($data['student_number'])) {
            $updates[] = "student_number = ?";
            $params[] = $data['student_number'];
            $types .= "s";
        }
        if (isset($data['date_of_birth'])) {
            $updates[] = "date_of_birth = ?";
            $params[] = $data['date_of_birth'];
            $types .= "s";
        }
        if (isset($data['address'])) {
            $updates[] = "address = ?";
            $params[] = $data['address'];
            $types .= "s";
        }

        if (empty($updates)) {
            return $this->getById($id);
        }

        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE students SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return $this->getById($id);
        }

        return null;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }

    public function getByClassId($classId) {
        $stmt = $this->conn->prepare("SELECT s.*, u.name, u.email FROM students s 
                                      LEFT JOIN users u ON s.user_id = u.id 
                                      WHERE s.class_id = ?");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findByStudentNumber($studentNumber) {
        $stmt = $this->conn->prepare("SELECT * FROM students WHERE student_number = ?");
        $stmt->bind_param("s", $studentNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}
?>
