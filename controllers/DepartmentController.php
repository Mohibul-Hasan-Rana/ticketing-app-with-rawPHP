<?php
require_once 'models/Department.php';

class DepartmentController {
    
    private $db;
    private $department_model;
    private $auth;

    public function __construct($db) {
        $this->db = $db;
        $this->department_model = new Department($db);
        $this->auth = new Auth($db);
    }

    public function index() {
        // All authenticated users can view departments
        $this->auth->authenticate();
        $departments = $this->department_model->getAll();
        Response::success($departments);
    }

    public function show($id) {
        $this->auth->authenticate();
        $department = $this->department_model->findById($id);
        
        if (!$department) {
            Response::error('Department not found', 404);
        }

        Response::success($department);
    }

    public function create() {
        $this->auth->requireRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['name'])) {
            Response::error('Department name is required');
        }

        if (empty(trim($data['name']))) {
            Response::error('Department name cannot be empty');
        }

        try {
            $department_id = $this->department_model->create($data);
            $department = $this->department_model->findById($department_id);
            Response::success($department, 'Department created successfully', 201);
        } catch (Exception $e) {
            Response::error('Failed to create department: ' . $e->getMessage());
        }
    }

    public function update($id) {
        $this->auth->requireRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['name'])) {
            Response::error('Department name is required');
        }

        if (empty(trim($data['name']))) {
            Response::error('Department name cannot be empty');
        }

        if (!$this->department_model->findById($id)) {
            Response::error('Department not found', 404);
        }

        try {
            $success = $this->department_model->update($id, $data);
            if ($success) {
                $department = $this->department_model->findById($id);
                Response::success($department, 'Department updated successfully');
            } else {
                Response::error('Failed to update department');
            }
        } catch (Exception $e) {
            Response::error('Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id) {
        $this->auth->requireRole(['admin']);
        
        if (!$this->department_model->findById($id)) {
            Response::error('Department not found', 404);
        }

        try {
            $success = $this->department_model->delete($id);
            if ($success) {
                Response::success(null, 'Department deleted successfully');
            } else {
                Response::error('Failed to delete department');
            }
        } catch (Exception $e) {
            Response::error('Delete failed: ' . $e->getMessage());
        }
    }
}
?>