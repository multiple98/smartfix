<?php
class EmailVerification {
    private $pdo;
    private $from_email = "noreply@smartfix.com";
    private $from_name = "SmartFix";
    private $debug_mode = true; // Set to false in production
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a secure verification token
     */
    public function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Send verification email to user
     */
    public function sendVerificationEmail($user_id, $email, $username) {
        try {
            // Generate verification token
            $token = $this->generateToken();
            
            // Update user with verification token
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET verification_token = ?, verification_sent_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$token, $user_id]);
            
            // Create verification URL
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $verification_url = "http://" . $host . "/smartfix/verify_email.php?token=" . $token;
            
            // Email subject and body
            $subject = "Verify Your SmartFix Account";
            $message = $this->getEmailTemplate($username, $verification_url);
            
            // Try to send email
            $sent = $this->attemptEmailSend($email, $subject, $message);
            
            // Log the attempt
            $this->logVerificationAction($user_id, $email, $token, $sent ? 'sent' : 'failed');
            
            // In debug mode, always return true and show debug info
            if ($this->debug_mode) {
                $this->logDebugInfo($email, $subject, $verification_url, $token);
                return true; // Always return true in debug mode
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            
            // In debug mode, still create the token for manual verification
            if ($this->debug_mode) {
                try {
                    $token = $this->generateToken();
                    $stmt = $this->pdo->prepare("
                        UPDATE users 
                        SET verification_token = ?, verification_sent_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$token, $user_id]);
                    $this->logVerificationAction($user_id, $email, $token, 'debug_mode');
                    return true;
                } catch (Exception $e2) {
                    return false;
                }
            }
            
            return false;
        }
    }
    
    /**
     * Attempt to send email with multiple methods
     */
    private function attemptEmailSend($email, $subject, $message) {
        // Method 1: Try PHP mail() function
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
        $headers .= "Reply-To: {$this->from_email}" . "\r\n";
        
        // Suppress warnings and try to send
        $sent = @mail($email, $subject, $message, $headers);
        
        if ($sent) {
            return true;
        }
        
        // Method 2: Try with ini_set for SMTP (for development)
        if ($this->debug_mode) {
            // Try to configure SMTP on the fly for Gmail (example)
            ini_set("SMTP", "smtp.gmail.com");
            ini_set("smtp_port", "587");
            ini_set("sendmail_from", $this->from_email);
            
            $sent = @mail($email, $subject, $message, $headers);
            if ($sent) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log debug information for development
     */
    private function logDebugInfo($email, $subject, $verification_url, $token) {
        $debug_file = "debug_emails.log";
        $debug_info = "\n" . str_repeat("=", 50) . "\n";
        $debug_info .= "DEBUG EMAIL LOG - " . date("Y-m-d H:i:s") . "\n";
        $debug_info .= "To: $email\n";
        $debug_info .= "Subject: $subject\n";
        $debug_info .= "Verification URL: $verification_url\n";
        $debug_info .= "Token: $token\n";
        $debug_info .= "Manual verification link: http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/smartfix/verify_email.php?token=" . $token . "\n";
        $debug_info .= str_repeat("=", 50) . "\n";
        
        file_put_contents($debug_file, $debug_info, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Verify email token
     */
    public function verifyToken($token) {
        try {
            // Find user with this token
            $stmt = $this->pdo->prepare("
                SELECT id, email, name, verification_sent_at 
                FROM users 
                WHERE verification_token = ? AND is_verified = 0
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid or expired verification token.'];
            }
            
            // Check if token is not too old (24 hours)
            $sent_time = strtotime($user['verification_sent_at']);
            $current_time = time();
            $hours_passed = ($current_time - $sent_time) / 3600;
            
            if ($hours_passed > 24) {
                return ['success' => false, 'message' => 'Verification token has expired. Please request a new one.'];
            }
            
            // Mark user as verified
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_verified = 1, email_verified_at = NOW(), verification_token = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // Log verification
            $this->logVerificationAction($user['id'], $user['email'], $token, 'verified');
            
            return ['success' => true, 'message' => 'Email verified successfully! You can now log in.'];
            
        } catch (Exception $e) {
            error_log("Token verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during verification.'];
        }
    }
    
    /**
     * Resend verification email
     */
    public function resendVerification($email) {
        try {
            // Find unverified user
            $stmt = $this->pdo->prepare("
                SELECT id, name, email 
                FROM users 
                WHERE email = ? AND is_verified = 0
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found or already verified.'];
            }
            
            // Send new verification email
            $sent = $this->sendVerificationEmail($user['id'], $user['email'], $user['name']);
            
            if ($sent) {
                return ['success' => true, 'message' => 'Verification email sent successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to send verification email.'];
            }
            
        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while resending verification.'];
        }
    }
    
    /**
     * Check if user is verified
     */
    public function isUserVerified($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            return $result && $result['is_verified'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get verification link for manual testing
     */
    public function getVerificationLink($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT verification_token FROM users WHERE id = ? AND is_verified = 0");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result && $result['verification_token']) {
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                return "http://" . $host . "/smartfix/verify_email.php?token=" . $result['verification_token'];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Log verification actions
     */
    private function logVerificationAction($user_id, $email, $token, $action) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_verification_logs 
                (user_id, email, verification_token, action, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $email,
                $token,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Verification logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($username, $verification_url) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <title>Verify Your SmartFix Account</title>
        </head>
        <body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;\">
            <div style=\"background: #007BFF; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;\">
                <h1 style=\"margin: 0;\">Welcome to SmartFix!</h1>
            </div>
            
            <div style=\"background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #e9ecef;\">
                <h2 style=\"color: #007BFF;\">Hello {$username},</h2>
                
                <p>Thank you for registering with SmartFix! To complete your registration and start using our services, please verify your email address.</p>
                
                <div style=\"text-align: center; margin: 30px 0;\">
                    <a href=\"{$verification_url}\" style=\"background: #007BFF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;\">Verify Email Address</a>
                </div>
                
                <p>If the button above doesn't work, you can also copy and paste this link into your browser:</p>
                <p style=\"word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace;\">{$verification_url}</p>
                
                <hr style=\"margin: 30px 0; border: none; border-top: 1px solid #e9ecef;\">
                
                <p style=\"font-size: 14px; color: #666;\">
                    <strong>Important:</strong> This verification link will expire in 24 hours for security reasons.
                </p>
                
                <p style=\"font-size: 14px; color: #666;\">
                    If you didn't create an account with SmartFix, please ignore this email.
                </p>
                
                <div style=\"text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;\">
                    <p style=\"font-size: 12px; color: #999;\">
                        © 2024 SmartFix. All rights reserved.<br>
                        This is an automated message, please do not reply.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>