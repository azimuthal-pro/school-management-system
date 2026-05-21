<?php

require_once __DIR__ . '/../config/database.php';

class Teacher {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT t.*, u.name, u.email FROM teachers t 
                                      LEFT JOIN users u ON t.user_id = u.id");
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT t.*, u.name, u.email FROM teachers t 
                                      LEFT JOIN users u ON t.user_id = u.id 
                                      WHERE t.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO teachers (user_id, employee_number, phone) 
                                      VALUES (?, ?, ?)");
        $stmt->bind_param("iss", 
            $data['user_id'], 
            $data['employee_number'], 
            $data['phone'] ?? null
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

        if (isset($data['phone'])) {
            $updates[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= "s";
        }

        if (empty($updates)) {
            return $this->getById($id);
        }

        $params[] = $id;
        $types .= "i";

        $sql = "UPDATE teachers SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return $this->getById($id);
        }

        return null;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
}
?>
