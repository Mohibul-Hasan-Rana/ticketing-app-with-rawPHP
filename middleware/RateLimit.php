<?php
class RateLimit {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function check($ip, $endpoint, $limit, $window_seconds) {
        $this->cleanup($window_seconds);
        
        $window_start = date('Y-m-d H:i:s', time() - $window_seconds);
        
        $query = "SELECT COUNT(*) as count FROM rate_limits 
                  WHERE ip_address = ? AND endpoint = ? AND window_start >= ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip, $endpoint, $window_start]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] >= $limit) {
            return false;
        }
        
        // Record this request
        $query = "INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip, $endpoint]);
        
        return true;
    }

    private function cleanup($window_seconds) {
        $cutoff = date('Y-m-d H:i:s', time() - $window_seconds);
        $query = "DELETE FROM rate_limits WHERE window_start < ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$cutoff]);
    }
}
?>