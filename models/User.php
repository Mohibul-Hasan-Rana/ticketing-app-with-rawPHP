<?php
class User {
    
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($data) {
        $query = "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = $data['role'] ?? 'user';
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $password_hash,
            $role
        ]);

        return $this->db->lastInsertId();
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT id, name, email, role, created_at FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $values[] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $values[] = $data['email'];
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $values[] = $data['role'];
        }
        
        if (isset($data['password'])) {
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
    }

    public function delete($id) {
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>