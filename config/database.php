<?php
date_default_timezone_set('America/Mexico_City');

class Database {
    private $host     = 'localhost';
    private $db_name  = 'wiser_financiera';
    private $username = 'root';
    private $password = '';
    private $charset  = 'utf8mb4';
    public  $conn;

    public function getConnection(): PDO {
        $this->conn = null;
        try {
            $socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';
            $dsn    = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset};unix_socket={$socket}";
            $opts   = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $opts);

            try {
                $tz     = new DateTimeZone('America/Mexico_City');
                $offset = (new DateTime('now', $tz))->format('P');
                $this->conn->exec("SET time_zone = '{$offset}'");
            } catch (Exception $e) {
                error_log('No se pudo establecer timezone MySQL: ' . $e->getMessage());
            }
        } catch (PDOException $e) {
            error_log('DB Connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
        return $this->conn;
    }
}
