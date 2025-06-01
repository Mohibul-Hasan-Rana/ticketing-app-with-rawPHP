<?php
class Ticket {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($data) {
        $query = "INSERT INTO tickets (title, description, user_id, department_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['user_id'],
            $data['department_id']
        ]);
        return $this->db->lastInsertId();
    }

    public function getAll($filters = []) {
        $query = "SELECT t.*, u.name as user_name, u.email as user_email, 
                         d.name as department_name,
                         a.name as assigned_agent_name
                  FROM tickets t
                  LEFT JOIN users u ON t.user_id = u.id
                  LEFT JOIN departments d ON t.department_id = d.id
                  LEFT JOIN users a ON t.assigned_agent_id = a.id";
        
        $where_conditions = [];
        $params = [];
        
        if (isset($filters['status'])) {
            $where_conditions[] = "t.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['user_id'])) {
            $where_conditions[] = "t.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (isset($filters['department_id'])) {
            $where_conditions[] = "t.department_id = ?";
            $params[] = $filters['department_id'];
        }
        
        if (isset($filters['assigned_agent_id'])) {
            $where_conditions[] = "t.assigned_agent_id = ?";
            $params[] = $filters['assigned_agent_id'];
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(' AND ', $where_conditions);
        }
        
        $query .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT t.*, u.name as user_name, u.email as user_email, 
                         d.name as department_name,
                         a.name as assigned_agent_name
                  FROM tickets t
                  LEFT JOIN users u ON t.user_id = u.id
                  LEFT JOIN departments d ON t.department_id = d.id
                  LEFT JOIN users a ON t.assigned_agent_id = a.id
                  WHERE t.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['title'])) {
            $fields[] = "title = ?";
            $values[] = $data['title'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $values[] = $data['description'];
        }
        
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }
        
        if (isset($data['assigned_agent_id'])) {
            $fields[] = "assigned_agent_id = ?";
            $values[] = $data['assigned_agent_id'];
        }
        
        if (isset($data['department_id'])) {
            $fields[] = "department_id = ?";
            $values[] = $data['department_id'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $query = "UPDATE tickets SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute($values);
    }

    public function delete($id) {
        $query = "DELETE FROM tickets WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }

    public function addNote($ticket_id, $user_id, $note) {
        $query = "INSERT INTO ticket_notes (ticket_id, user_id, note) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ticket_id, $user_id, $note]);
        return $this->db->lastInsertId();
    }

    public function getNotes($ticket_id) {
        $query = "SELECT tn.*, u.name as user_name 
                  FROM ticket_notes tn 
                  LEFT JOIN users u ON tn.user_id = u.id 
                  WHERE tn.ticket_id = ? 
                  ORDER BY tn.created_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ticket_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignAgent($ticket_id, $agent_id) {
        return $this->update($ticket_id, ['assigned_agent_id' => $agent_id]);
    }

    public function uploadAttachment($ticket_id, $file_data, $uploaded_by) {
        $query = "INSERT INTO ticket_attachments (ticket_id, filename, file_path, file_size, mime_type, uploaded_by) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            $ticket_id,
            $file_data['filename'],
            $file_data['file_path'],
            $file_data['file_size'],
            $file_data['mime_type'],
            $uploaded_by
        ]);
        return $this->db->lastInsertId();
    }

   
}
?>