<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'ticketing_system';
    private $username = 'root';
    private $password = 'root';
    private $db;

    public function getConnection() {
        $this->db = null;
        try {
            $this->db = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->db;
    }


    public function createTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'agent', 'user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
                user_id INT NOT NULL,
                department_id INT NOT NULL,
                assigned_agent_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_agent_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS ticket_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ticket_id INT NOT NULL,
                user_id INT NOT NULL,
                note TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS auth_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                endpoint VARCHAR(255) NOT NULL,
                count INT DEFAULT 1,
                window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_endpoint (ip_address, endpoint)
            )",
            
            // "CREATE TABLE IF NOT EXISTS ticket_attachments (
            //     id INT AUTO_INCREMENT PRIMARY KEY,
            //     ticket_id INT NOT NULL,
            //     filename VARCHAR(255) NOT NULL,
            //     file_path VARCHAR(500) NOT NULL,
            //     file_size INT NOT NULL,
            //     mime_type VARCHAR(100) NOT NULL,
            //     uploaded_by INT NOT NULL,
            //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            //     FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            //     FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            // )"
        ];

        foreach ($queries as $query) {
            $this->db->prepare($query)->execute();
        }
    }
}
?>