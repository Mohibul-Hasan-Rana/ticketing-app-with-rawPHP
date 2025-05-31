<?php

    class Auth {
        
        private $db;

        public function __construct($db) {
            $this->db = $db;
        }

        public function authenticate() {
            $headers = getallheaders();
            $token = null;

            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
                if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                    $token = $matches[1];
                }
            }

            if (!$token) {
                Response::error('Authentication required', 401);
            }

            $query = "SELECT u.*, t.expires_at FROM users u 
                    JOIN auth_tokens t ON u.id = t.user_id 
                    WHERE t.token = ? AND t.expires_at > NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Response::error('Invalid or expired token', 401);
            }

            return $user;
        }

        public function requireRole($roles) {
            $user = $this->authenticate();
            if (!in_array($user['role'], (array)$roles)) {
                Response::error('Insufficient permissions', 403);
            }
            return $user;
        }

        public function generateToken($user_id) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $query = "INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id, $token, $expires_at]);

            return $token;
        }

        public function revokeToken($token) {
            $query = "DELETE FROM auth_tokens WHERE token = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$token]);
        }

        public function cleanExpiredTokens() {
            $query = "DELETE FROM auth_tokens WHERE expires_at <= NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
        }
    }
?>
