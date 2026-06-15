<?php
/**
 * Database connection using PDO
 */
class Database {
    private $host = "localhost";
    private $db_name = "enhanced_enrollment_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Enforce UTC timezone for PHP date/time functions
        date_default_timezone_set('UTC');

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Set session timezone to UTC for database queries
            $this->conn->exec("SET time_zone = '+00:00'");
            
            // Set error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Disable emulated prepared statements
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            // Default fetch mode to associative array
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // Return clean JSON response if DB connection fails
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Database connection error: " . $exception->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
?>
