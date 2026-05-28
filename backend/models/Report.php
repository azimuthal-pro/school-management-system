<?php

require_once __DIR__ . '/../config/database.php';

class Report {
    private $conn;

    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->connect();
    }

    public function attendanceSummary($filters = []) {
        $query = "SELECT status, COUNT(*) as count FROM attendance WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['class_id'])) {
            $query .= " AND class_id = ?";
            $params[] = $filters['class_id'];
            $types .= 'i';
        }
        if (!empty($filters['date'])) {
            $query .= " AND date = ?";
            $params[] = $filters['date'];
            $types .= 's';
        }

        $query .= " GROUP BY status";
        $stmt = $this->conn->prepare($query);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
        while ($row = $result->fetch_assoc()) {
            $summary[$row['status']] = (int) $row['count'];
            $summary['total'] += (int) $row['count'];
        }

        return $summary;
    }

    public function gradeSummary($filters = []) {
        $query = "SELECT AVG(score) as avg_score, MIN(score) as min_score, MAX(score) as max_score FROM grades WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['class_id'])) {
            $query .= " AND class_id = ?";
            $params[] = $filters['class_id'];
            $types .= 'i';
        }
        if (!empty($filters['subject_id'])) {
            $query .= " AND subject_id = ?";
            $params[] = $filters['subject_id'];
            $types .= 'i';
        }
        if (!empty($filters['term'])) {
            $query .= " AND term = ?";
            $params[] = $filters['term'];
            $types .= 's';
        }

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        return [
            'average' => isset($data['avg_score']) ? round((float) $data['avg_score'], 2) : null,
            'min' => isset($data['min_score']) ? (float) $data['min_score'] : null,
            'max' => isset($data['max_score']) ? (float) $data['max_score'] : null,
        ];
    }

    public function studentReport($studentId) {
        $attendance = $this->getStudentAttendanceSummary($studentId);
        $grades = $this->getStudentGrades($studentId);

        return [
            'attendance' => $attendance,
            'grades' => $grades,
        ];
    }

    public function getStudentAttendanceSummary($studentId) {
        $stmt = $this->conn->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE student_id = ? GROUP BY status");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
        while ($row = $result->fetch_assoc()) {
            $summary[$row['status']] = (int) $row['count'];
            $summary['total'] += (int) $row['count'];
        }

        return $summary;
    }

    public function getStudentGrades($studentId) {
        $stmt = $this->conn->prepare("SELECT g.*, s.name as subject_name, c.name as class_name FROM grades g 
                                      LEFT JOIN subjects s ON g.subject_id = s.id 
                                      LEFT JOIN classes c ON g.class_id = c.id 
                                      WHERE g.student_id = ? ORDER BY g.term ASC");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>