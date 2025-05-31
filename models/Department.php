<?php
class Department {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($data) {
        $query = "INSERT INTO departments (name) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$data['name']]);
        return $this->db->lastInsertId();
    }

    public function getAll() {
        $query = "SELECT * FROM departments ORDER BY name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM departments WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE departments SET name = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$data['name'], $id]);
    }

    public function delete($id) {
        $query = "DELETE FROM departments WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
}
?>