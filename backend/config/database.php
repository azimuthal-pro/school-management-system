<?php

class Database {
    private static $instance = null;
    private $host;
    private $db;
    private $user;
    private $password;
    private $conn;

    private function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db = getenv('DB_NAME') ?: 'sms';
        $this->user = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function connect() {
        if ($this->conn === null) {
            $this->conn = new mysqli($this->host, $this->user, $this->password, $this->db);

            if ($this->conn->connect_error) {
                error_log("Database connection failed: " . $this->conn->connect_error);
                die("Connection Failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset("utf8mb4");
        }
        return $this->conn;
    }

    public function getConnection() {
        return $this->conn;
    }
}
?>
