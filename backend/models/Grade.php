<?php

require_once __DIR__ . '/../config/database.php';

class Grade {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->connect();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT g.*, u.name as student_name, s.name as subject_name, c.name as class_name, t.name as teacher_name 
                                      FROM grades g 
                                      LEFT JOIN students st ON g.student_id = st.id 
                                      LEFT JOIN users u ON st.user_id = u.id 
                                      LEFT JOIN subjects s ON g.subject_id = s.id 
                                      LEFT JOIN classes c ON g.class_id = c.id 
                                      LEFT JOIN teachers t ON g.teacher_id = t.id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByStudent($studentId) {
        $stmt = $this->conn->prepare("SELECT g.*, s.name as subject_name, c.name as class_name 
                                      FROM grades g 
                                      LEFT JOIN subjects s ON g.subject_id = s.id 
                                      LEFT JOIN classes c ON g.class_id = c.id 
                                      WHERE g.student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getByClass($classId) {
        $stmt = $this->conn->prepare("SELECT g.*, u.name as student_name, s.name as subject_name 
                                      FROM grades g 
                                      LEFT JOIN students st ON g.student_id = st.id 
                                      LEFT JOIN users u ON st.user_id = u.id 
                                      LEFT JOIN subjects s ON g.subject_id = s.id 
                                      WHERE g.class_id = ?");
        $stmt->bind_param("i", $classId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getBySubject($subjectId) {
        $stmt = $this->conn->prepare("SELECT g.*, u.name as student_name, c.name as class_name 
                                      FROM grades g 
                                      LEFT JOIN students st ON g.student_id = st.id 
                                      LEFT JOIN users u ON st.user_id = u.id 
                                      LEFT JOIN classes c ON g.class_id = c.id 
                                      WHERE g.subject_id = ?");
        $stmt->bind_param("i", $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO grades (student_id, subject_id, class_id, teacher_id, term, score, grade) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisisds", 
            $data['student_id'], 
            $data['subject_id'], 
            $data['class_id'] ?? null, 
            $data['teacher_id'], 
            $data['term'] ?? null, 
            $data['score'], 
            $this->calculateGrade($data['score'])
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

        if (isset($data['score'])) {
            $updates[] = "score = ?";
            $params[] = $data['score'];
            $types .= "d";
        }
        if (isset($data['term'])) {
            $updates[] = "term = ?";
            $params[] = $data['term'];
            $types .= "s";
        }

        if (empty($updates)) {
            return $this->getById($id);
        }

        // Recalculate grade if score changed
        if (isset($data['score'])) {
            $updates[] = "grade = ?";
            $params[] = $this->calculateGrade($data['score']);
            $types .= "s";
        }

        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE grades SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return $this->getById($id);
        }

        return null;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM grades WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT g.*, u.name as student_name, s.name as subject_name 
                                      FROM grades g 
                                      LEFT JOIN students st ON g.student_id = st.id 
                                      LEFT JOIN users u ON st.user_id = u.id 
                                      LEFT JOIN subjects s ON g.subject_id = s.id 
                                      WHERE g.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    private function calculateGrade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
?>
