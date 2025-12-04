<?php
class Database{
 private $host = 'localhost';
 private $db   = 'course';
 private $user = 'admin';
 private $pass = 'password123';
 private $conn;
public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8";
            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            echo "Database connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>