<?php
require_once 'models/User.php';
require_once 'middleware/Auth.php';

class UserController {

    private $db;
    private $user_model;
    private $auth;

    public function __construct($db) {
        $this->db = $db;
        $this->user_model = new User($db);
        $this->auth = new Auth($db);
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['name'], $data['email'], $data['password'])) {
            Response::error('Name, email, and password are required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format');
        }

        if ($this->user_model->findByEmail($data['email'])) {
            Response::error('Email already exists');
        }

        try {
            $user_id = $this->user_model->create($data);
            $user = $this->user_model->findById($user_id);
            
            Response::success($user, 'User registered successfully', 201);
        } catch (Exception $e) {
            Response::error('Registration failed: ' . $e->getMessage());
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['email'], $data['password'])) {
            Response::error('Email and password are required');
        }

        $user = $this->user_model->findByEmail($data['email']);
        
        if (!$user || !$this->user_model->verifyPassword($data['password'], $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }

        $token = $this->auth->generateToken($user['id']);
        unset($user['password_hash']);
        
        Response::success([
            'user' => $user,
            'token' => $token
        ], 'Login successful');
    }

    public function logout() {
        $headers = getallheaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                $token = $matches[1];
            }
        }        

        if ($token) {
            $this->auth->revokeToken($token);
        }

        Response::success(null, 'Logged out successfully');
    }

    public function index() {
        
        $user = $this->auth->requireRole(['admin']);
        $users = $this->user_model->getAll();
        Response::success($users);
    }

    public function show($id) {
        $current_user = $this->auth->authenticate();
        
        // Users can only view their own profile unless they're admin
        if ($current_user['role'] !== 'admin' && $current_user['id'] != $id) {
            Response::error('Unauthorized', 403);
        }

        $user = $this->user_model->findById($id);
        if (!$user) {
            Response::error('User not found', 404);
        }

        Response::success($user);
    }

    public function create() {
        $current_user = $this->auth->requireRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['name'], $data['email'], $data['password'])) {
            Response::error('Name, email, and password are required');
        }

        if ($this->user_model->findByEmail($data['email'])) {
            Response::error('Email already exists');
        }

        try {
            $user_id = $this->user_model->create($data);
            $user = $this->user_model->findById($user_id);
            Response::success($user, 'User created successfully', 201);
        } catch (Exception $e) {
            Response::error('Failed to create user: ' . $e->getMessage());
        }
    }

    public function update($id) {
        $current_user = $this->auth->authenticate();
        
        // Users can only update their own profile unless they're admin
        if ($current_user['role'] !== 'admin' && $current_user['id'] != $id) {
            Response::error('Unauthorized', 403);
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            Response::error('No data provided');
        }

        // Non-admin users cannot change their role
        if ($current_user['role'] !== 'admin' && isset($data['role'])) {
            unset($data['role']);
        }

        try {
            $success = $this->user_model->update($id, $data);
            if ($success) {
                $user = $this->user_model->findById($id);
                Response::success($user, 'User updated successfully');
            } else {
                Response::error('Failed to update user');
            }
        } catch (Exception $e) {
            Response::error('Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id) {
        $current_user = $this->auth->requireRole(['admin']);
        
        // Cannot delete self
        if ($current_user['id'] == $id) {
            Response::error('Cannot delete your own account');
        }

        try {
            $success = $this->user_model->delete($id);
            if ($success) {
                Response::success(null, 'User deleted successfully');
            } else {
                Response::error('Failed to delete user');
            }
        } catch (Exception $e) {
            Response::error('Delete failed: ' . $e->getMessage());
        }
    }
}
?>