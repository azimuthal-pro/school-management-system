<?php

require_once __DIR__ . '/../config/database.php';

class Grade {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->connect();
    }

    public function getAll($filters = []) {
        $sql = "SELECT g.*, u.name as student_name, s.name as subject_name, c.name as class_name, tu.name as teacher_name 
                FROM grades g 
                LEFT JOIN students st ON g.student_id = st.id 
                LEFT JOIN users u ON st.user_id = u.id 
                LEFT JOIN subjects s ON g.subject_id = s.id 
                LEFT JOIN classes c ON g.class_id = c.id 
                LEFT JOIN teachers t ON g.teacher_id = t.id
                LEFT JOIN users tu ON t.user_id = tu.id
                WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['student_id'])) {
            $sql .= " AND g.student_id = ?";
            $params[] = $filters['student_id'];
            $types .= "i";
        }
        if (!empty($filters['class_id'])) {
            $sql .= " AND g.class_id = ?";
            $params[] = $filters['class_id'];
            $types .= "i";
        }
        if (!empty($filters['subject_id'])) {
            $sql .= " AND g.subject_id = ?";
            $params[] = $filters['subject_id'];
            $types .= "i";
        }

        $sql .= " ORDER BY g.created_at DESC, g.id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
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
        $classId = $data['class_id'] ?? null;
        $teacherId = $data['teacher_id'] ?? null;
        $term = $data['term'] ?? null;
        $grade = $this->calculateGrade($data['score']);

        $stmt = $this->conn->prepare("INSERT INTO grades (student_id, subject_id, class_id, teacher_id, term, score, grade) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiisds", 
            $data['student_id'], 
            $data['subject_id'], 
            $classId, 
            $teacherId, 
            $term, 
            $data['score'], 
            $grade
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
        if (isset($data['student_id'])) {
            $updates[] = "student_id = ?";
            $params[] = $data['student_id'];
            $types .= "i";
        }
        if (isset($data['subject_id'])) {
            $updates[] = "subject_id = ?";
            $params[] = $data['subject_id'];
            $types .= "i";
        }
        if (isset($data['class_id'])) {
            $updates[] = "class_id = ?";
            $params[] = $data['class_id'];
            $types .= "i";
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

    public function getTeacherIdByUserId($userId) {
        if (!$userId) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $teacher = $result->fetch_assoc();

        return $teacher['id'] ?? null;
    }
}
?>
