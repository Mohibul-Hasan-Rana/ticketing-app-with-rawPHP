<?php
require_once 'models/Ticket.php';

class TicketController {
    private $db;
    private $ticket_model;
    private $auth;

    public function __construct($db) {
        $this->db = $db;
        $this->ticket_model = new Ticket($db);
        $this->auth = new Auth($db);
    }

    public function index() {
        $current_user = $this->auth->authenticate();
        $filters = [];
        
        // Regular users can only see their own tickets
        if ($current_user['role'] === 'user') {
            $filters['user_id'] = $current_user['id'];
        }
        
        // Apply additional filters from query parameters
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['department_id'])) {
            $filters['department_id'] = $_GET['department_id'];
        }
        
        if (isset($_GET['assigned_agent_id'])) {
            $filters['assigned_agent_id'] = $_GET['assigned_agent_id'];
        }

        $tickets = $this->ticket_model->getAll($filters);
        Response::success($tickets);
    }

    public function show($id) {
        $current_user = $this->auth->authenticate();
        $ticket = $this->ticket_model->findById($id);
        
        if (!$ticket) {
            Response::error('Ticket not found', 404);
        }

        // Regular users can only view their own tickets
        if ($current_user['role'] === 'user' && $ticket['user_id'] != $current_user['id']) {
            Response::error('Unauthorized', 403);
        }

        // Get notes and attachments
        $notes = $this->ticket_model->getNotes($id);
        
        
        $ticket['notes'] = $notes;
       

        Response::success($ticket);
    }

    public function create() {
        $current_user = $this->auth->authenticate();
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['title'], $data['description'], $data['department_id'])) {
            Response::error('Title, description, and department_id are required');
        }

        if (empty(trim($data['title'])) || empty(trim($data['description']))) {
            Response::error('Title and description cannot be empty');
        }

        $data['user_id'] = $current_user['id'];

        try {
            $ticket_id = $this->ticket_model->create($data);
            $ticket = $this->ticket_model->findById($ticket_id);
            Response::success($ticket, 'Ticket created successfully', 201);
        } catch (Exception $e) {
            Response::error('Failed to create ticket: ' . $e->getMessage());
        }
    }

    public function update($id) {
        $current_user = $this->auth->authenticate();
        $ticket = $this->ticket_model->findById($id);
        
        if (!$ticket) {
            Response::error('Ticket not found', 404);
        }

        // Regular users can only update their own tickets and only certain fields
        if ($current_user['role'] === 'user' && $ticket['user_id'] != $current_user['id']) {
            Response::error('Unauthorized', 403);
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            Response::error('No data provided');
        }

        // Regular users can only update title and description
        if ($current_user['role'] === 'user') {
            $allowed_fields = ['title', 'description'];
            $data = array_intersect_key($data, array_flip($allowed_fields));
        }

        try {
            $success = $this->ticket_model->update($id, $data);
            if ($success) {
                $ticket = $this->ticket_model->findById($id);
                Response::success($ticket, 'Ticket updated successfully');
            } else {
                Response::error('Failed to update ticket');
            }
        } catch (Exception $e) {
            Response::error('Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id) {
        $current_user = $this->auth->authenticate();
        $ticket = $this->ticket_model->findById($id);
        
        if (!$ticket) {
            Response::error('Ticket not found', 404);
        }

        // Only admins or ticket owners can delete tickets
        if ($current_user['role'] !== 'admin' && $ticket['user_id'] != $current_user['id']) {
            Response::error('Unauthorized', 403);
        }

        try {
            $success = $this->ticket_model->delete($id);
            if ($success) {
                Response::success(null, 'Ticket deleted successfully');
            } else {
                Response::error('Failed to delete ticket');
            }
        } catch (Exception $e) {
            Response::error('Delete failed: ' . $e->getMessage());
        }
    }

    public function handleAction($id, $action) {
        switch ($action) {
            case 'notes':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $this->getNotes($id);
                } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->addNote($id);
                }
                break;
            case 'assign':
                $this->assignAgent($id);
                break;
            default:
                Response::error('Action not found', 404);
        }
    }

    private function getNotes($ticket_id) {
        $current_user = $this->auth->authenticate();
        $ticket = $this->ticket_model->findById($ticket_id);
        
        if (!$ticket) {
            Response::error('Ticket not found', 404);
        }

        // Regular users can only view notes for their own tickets
        if ($current_user['role'] === 'user' && $ticket['user_id'] != $current_user['id']) {
            Response::error('Unauthorized', 403);
        }

        $notes = $this->ticket_model->getNotes($ticket_id);
        Response::success($notes);
    }

    private function addNote($ticket_id) {
        $current_user = $this->auth->authenticate();
        $ticket = $this->ticket_model->findById($ticket_id);
        
        if (!$ticket) {
            Response::error('Ticket not found', 404);
        }

        // Regular users can only add notes to their own tickets
        if ($current_user['role'] === 'user' && $ticket['user_id'] != $current_user['id']) {
            Response::error('Unauthorized', 403);
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['note'])) {
            Response::error('Note content is required');
        }

        if (empty(trim($data['note']))) {
            Response::error('Note cannot be empty');
        }

        try {
            $note_id = $this->ticket_model->addNote($ticket_id, $current_user['id'], $data['note']);
            Response::success(['note_id' => $note_id], 'Note added successfully', 201);
        } catch (Exception $e) {
            Response::error('Failed to add note: ' . $e->getMessage());
        }
    }

    private function assignAgent($ticket_id) {
        $current_user = $this->auth->requireRole(['admin', 'agent']);
        $ticket = $this->ticket_model->findById($ticket_id);
        
        if (!$ticket) {
            Response::error('Ticket not found', 404);
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        // If no agent_id provided, assign to self (for agents)
        $agent_id = $data['agent_id'] ?? $current_user['id'];
        
        // Admins can assign to anyone, agents can only assign to themselves
        if ($current_user['role'] === 'agent' && $agent_id != $current_user['id']) {
            Response::error('Agents can only assign tickets to themselves', 403);
        }

        try {
            $success = $this->ticket_model->assignAgent($ticket_id, $agent_id);
            if ($success) {
                $ticket = $this->ticket_model->findById($ticket_id);
                Response::success($ticket, 'Agent assigned successfully');
            } else {
                Response::error('Failed to assign agent');
            }
        } catch (Exception $e) {
            Response::error('Assignment failed: ' . $e->getMessage());
        }
    }
    
}
?>