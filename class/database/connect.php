<?php

class Connect {
    // Use your own database credentials
    private $servername = " ";
    private $username = " ";
    private $password = " ";
    private $dbname = " ";
    private $conn = null;

    public function __construct() {
        $this->createConnection();
    }

    private function createConnection() {
        if ($this->conn == null) {
            try {
                $newConn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
                
                // Check connection
                if ($newConn->connect_error) {
                    throw new Exception("Connection failed: " . $newConn->connect_error);
                }
                
                $this->conn = $newConn;
            } catch (Exception $e) {
                die("Database connection error: " . $e->getMessage());
            }
        }
        return $this->conn;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn != null) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}
