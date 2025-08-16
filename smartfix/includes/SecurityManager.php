<?php
class SecurityManager {
    private $pdo;
    private $max_attempts = 5;
    private $lockout_time = 900; // 15 minutes
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createSecurityTables();
    }
    
    /**
     * Create security-related tables if they don't exist
     */
    private function createSecurityTables() {
        try {
            // Rate limiting table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                endpoint VARCHAR(100) NOT NULL,
                attempts INT DEFAULT 1,
                first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_blocked BOOLEAN DEFAULT FALSE,
                blocked_until TIMESTAMP NULL,
                INDEX idx_ip_endpoint (ip_address, endpoint),
                INDEX idx_blocked_until (blocked_until)
            )");
            
            // Audit log table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                action VARCHAR(100) NOT NULL,
                table_name VARCHAR(50) NULL,
                record_id INT NULL,
                old_values JSON NULL,
                new_values JSON NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_action (user_id, action),
                INDEX idx_created_at (created_at)
            )");
            
            // CSRF tokens table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS csrf_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(64) NOT NULL UNIQUE,
                user_id INT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_expires (expires_at)
            )");
            
        } catch (PDOException $e) {
            error_log("SecurityManager: Failed to create tables - " . $e->getMessage());
        }
    }
    
    /**
     * Check rate limiting for IP and endpoint
     */
    public function checkRateLimit($endpoint = 'login') {
        $ip = $this->getClientIP();
        
        try {
            // Clean up old records first
            $this->pdo->exec("DELETE FROM rate_limits WHERE blocked_until < NOW() OR (first_attempt < DATE_SUB(NOW(), INTERVAL 1 HOUR) AND is_blocked = FALSE)");
            
            $stmt = $this->pdo->prepare("SELECT * FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$ip, $endpoint]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                // First attempt
                $stmt = $this->pdo->prepare("INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)");
                $stmt->execute([$ip, $endpoint]);
                return true;
            }
            
            if ($record['is_blocked'] && strtotime($record['blocked_until']) > time()) {
                return false;
            }
            
            if ($record['attempts'] >= $this->max_attempts) {
                // Block the IP
                $blocked_until = date('Y-m-d H:i:s', time() + $this->lockout_time);
                $stmt = $this->pdo->prepare("UPDATE rate_limits SET is_blocked = TRUE, blocked_until = ? WHERE ip_address = ? AND endpoint = ?");
                $stmt->execute([$blocked_until, $ip, $endpoint]);
                return false;
            }
            
            // Increment attempts
            $stmt = $this->pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1 WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$ip, $endpoint]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("SecurityManager: Rate limit check failed - " . $e->getMessage());
            return true; // Allow on error to avoid blocking legitimate users
        }
    }
    
    /**
     * Reset rate limit for successful action
     */
    public function resetRateLimit($endpoint = 'login') {
        $ip = $this->getClientIP();
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = ?");
            $stmt->execute([$ip, $endpoint]);
        } catch (PDOException $e) {
            error_log("SecurityManager: Failed to reset rate limit - " . $e->getMessage());
        }
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken($userId = null) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        try {
            // Clean old tokens first
            $this->pdo->exec("DELETE FROM csrf_tokens WHERE expires_at < NOW()");
            
            $stmt = $this->pdo->prepare("INSERT INTO csrf_tokens (token, user_id, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$token, $userId, $expires]);
            
            return $token;
        } catch (PDOException $e) {
            error_log("SecurityManager: Failed to generate CSRF token - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token, $userId = null) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM csrf_tokens WHERE token = ? AND expires_at > NOW() AND (user_id = ? OR user_id IS NULL)");
            $stmt->execute([$token, $userId]);
            
            if ($stmt->fetch()) {
                // Delete the token after use
                $stmt = $this->pdo->prepare("DELETE FROM csrf_tokens WHERE token = ?");
                $stmt->execute([$token]);
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("SecurityManager: CSRF verification failed - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log security events
     */
    public function auditLog($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $action,
                $tableName,
                $recordId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("SecurityManager: Audit log failed - " . $e->getMessage());
        }
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) ?? 0;
            case 'float':
                return filter_var($input, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0.0;
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) ? trim($input) : '';
            case 'url':
                return filter_var($input, FILTER_VALIDATE_URL) ? trim($input) : '';
            case 'html':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            default: // string
                return trim(strip_tags($input));
        }
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) { // 5MB default
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid file parameters');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed with error code: ' . $file['error']);
        }
        
        if ($file['size'] > $maxSize) {
            throw new RuntimeException('File size exceeds limit');
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes) || !in_array($mimeType, $allowedMimes)) {
            throw new RuntimeException('Invalid file type');
        }
        
        return true;
    }
    
    /**
     * Generate secure filename
     */
    public function generateSecureFilename($originalName, $prefix = '') {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = $prefix . bin2hex(random_bytes(16)) . '.' . $extension;
        return $filename;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Secure session configuration
     */
    public static function secureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecureConnection() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
    
    /**
     * Generate secure password hash
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
}
?>