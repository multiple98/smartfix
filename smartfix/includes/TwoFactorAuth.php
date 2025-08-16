<?php
class TwoFactorAuth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a 6-digit verification code
     */
    public function generateCode() {
        return sprintf('%06d', mt_rand(100000, 999999));
    }
    
    /**
     * Create device fingerprint based on user agent and other factors
     */
    public function createDeviceFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }
    
    /**
     * Check if device is trusted for this user
     */
    public function isDeviceTrusted($userId, $deviceFingerprint) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM user_trusted_devices 
            WHERE user_id = ? AND device_fingerprint = ?
        ");
        $stmt->execute([$userId, $deviceFingerprint]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Add device to trusted devices
     */
    public function trustDevice($userId, $deviceFingerprint, $deviceName = null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        
        if (!$deviceName) {
            $deviceName = $this->parseDeviceName($userAgent);
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO user_trusted_devices 
            (user_id, device_fingerprint, device_name, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            last_used = CURRENT_TIMESTAMP,
            ip_address = VALUES(ip_address)
        ");
        
        return $stmt->execute([$userId, $deviceFingerprint, $deviceName, $ipAddress, $userAgent]);
    }
    
    /**
     * Generate and store 2FA code for user
     */
    public function generateAndStore2FACode($userId) {
        // Clean up old codes for this user
        $this->cleanupOldCodes($userId);
        
        $code = $this->generateCode();
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        $stmt = $this->pdo->prepare("
            INSERT INTO user_2fa_codes (user_id, code, expires_at) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$userId, $code, $expiresAt])) {
            return $code;
        }
        
        return false;
    }
    
    /**
     * Verify 2FA code
     */
    public function verifyCode($userId, $code) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM user_2fa_codes 
            WHERE user_id = ? AND code = ? AND expires_at > NOW() AND is_used = FALSE
        ");
        $stmt->execute([$userId, $code]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Mark code as used
            $updateStmt = $this->pdo->prepare("
                UPDATE user_2fa_codes SET is_used = TRUE WHERE id = ?
            ");
            $updateStmt->execute([$result['id']]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Clean up old/expired codes
     */
    private function cleanupOldCodes($userId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM user_2fa_codes 
            WHERE user_id = ? AND (expires_at < NOW() OR is_used = TRUE)
        ");
        $stmt->execute([$userId]);
    }
    
    /**
     * Parse device name from user agent
     */
    private function parseDeviceName($userAgent) {
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
            if (strpos($userAgent, 'Android') !== false) {
                return 'Android Device';
            }
            return 'Mobile Device';
        }
        
        if (strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            return strpos($userAgent, 'iPad') !== false ? 'iPad' : 'iPhone';
        }
        
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows Computer';
        }
        
        if (strpos($userAgent, 'Mac') !== false) {
            return 'Mac Computer';
        }
        
        if (strpos($userAgent, 'Linux') !== false) {
            return 'Linux Computer';
        }
        
        return 'Unknown Device';
    }
    
    /**
     * Send 2FA code via email
     */
    public function sendCodeByEmail($email, $name, $code) {
        $subject = "SmartFix - Your Verification Code";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .code { font-size: 24px; font-weight: bold; color: #007bff; text-align: center; 
                        padding: 15px; background-color: white; border: 2px dashed #007bff; margin: 20px 0; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>SmartFix Verification</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($name) . ",</h2>
                    <p>We detected a login attempt from a new device. To complete your login, please use the verification code below:</p>
                    <div class='code'>" . $code . "</div>
                    <p><strong>This code will expire in 10 minutes.</strong></p>
                    <p>If you didn't attempt to log in, please ignore this email and consider changing your password.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from SmartFix. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: SmartFix <noreply@smartfix.com>" . "\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Get user's trusted devices
     */
    public function getUserTrustedDevices($userId) {
        $stmt = $this->pdo->prepare("
            SELECT device_name, ip_address, created_at, last_used 
            FROM user_trusted_devices 
            WHERE user_id = ? 
            ORDER BY last_used DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Remove trusted device
     */
    public function removeTrustedDevice($userId, $deviceFingerprint) {
        $stmt = $this->pdo->prepare("
            DELETE FROM user_trusted_devices 
            WHERE user_id = ? AND device_fingerprint = ?
        ");
        return $stmt->execute([$userId, $deviceFingerprint]);
    }
}
?>