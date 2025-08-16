<?php
// Create Email Verification Components
error_reporting(E_ALL);
ini_set('display_errors', 1);

$messages = [];

// 1. Create EmailVerification class
$emailVerificationClass = '<?php
class EmailVerification {
    private $pdo;
    private $from_email = "noreply@smartfix.com";
    private $from_name = "SmartFix";
    
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
            $verification_url = "http://" . $_SERVER[\'HTTP_HOST\'] . "/smartfix/verify_email.php?token=" . $token;
            
            // Email subject and body
            $subject = "Verify Your SmartFix Account";
            $message = $this->getEmailTemplate($username, $verification_url);
            
            // Email headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
            $headers .= "Reply-To: {$this->from_email}" . "\r\n";
            
            // Send email
            $sent = mail($email, $subject, $message, $headers);
            
            // Log the attempt
            $this->logVerificationAction($user_id, $email, $token, $sent ? \'sent\' : \'failed\');
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify email token
     */
    public function verifyToken($token) {
        try {
            // Find user with this token
            $stmt = $this->pdo->prepare("
                SELECT id, email, username, verification_sent_at 
                FROM users 
                WHERE verification_token = ? AND is_verified = 0
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [\'success\' => false, \'message\' => \'Invalid or expired verification token.\'];
            }
            
            // Check if token is not too old (24 hours)
            $sent_time = strtotime($user[\'verification_sent_at\']);
            $current_time = time();
            $hours_passed = ($current_time - $sent_time) / 3600;
            
            if ($hours_passed > 24) {
                return [\'success\' => false, \'message\' => \'Verification token has expired. Please request a new one.\'];
            }
            
            // Mark user as verified
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_verified = 1, email_verified_at = NOW(), verification_token = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user[\'id\']]);
            
            // Log verification
            $this->logVerificationAction($user[\'id\'], $user[\'email\'], $token, \'verified\');
            
            return [\'success\' => true, \'message\' => \'Email verified successfully! You can now log in.\'];
            
        } catch (Exception $e) {
            error_log("Token verification error: " . $e->getMessage());
            return [\'success\' => false, \'message\' => \'An error occurred during verification.\'];
        }
    }
    
    /**
     * Resend verification email
     */
    public function resendVerification($email) {
        try {
            // Find unverified user
            $stmt = $this->pdo->prepare("
                SELECT id, username, email 
                FROM users 
                WHERE email = ? AND is_verified = 0
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [\'success\' => false, \'message\' => \'Email not found or already verified.\'];
            }
            
            // Send new verification email
            $sent = $this->sendVerificationEmail($user[\'id\'], $user[\'email\'], $user[\'username\']);
            
            if ($sent) {
                return [\'success\' => true, \'message\' => \'Verification email sent successfully!\'];
            } else {
                return [\'success\' => false, \'message\' => \'Failed to send verification email.\'];
            }
            
        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            return [\'success\' => false, \'message\' => \'An error occurred while resending verification.\'];
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
            return $result && $result[\'is_verified\'];
        } catch (Exception $e) {
            return false;
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
                $_SERVER[\'REMOTE_ADDR\'] ?? null,
                $_SERVER[\'HTTP_USER_AGENT\'] ?? null
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
                
                <p>If the button above doesn\'t work, you can also copy and paste this link into your browser:</p>
                <p style=\"word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace;\">{$verification_url}</p>
                
                <hr style=\"margin: 30px 0; border: none; border-top: 1px solid #e9ecef;\">
                
                <p style=\"font-size: 14px; color: #666;\">
                    <strong>Important:</strong> This verification link will expire in 24 hours for security reasons.
                </p>
                
                <p style=\"font-size: 14px; color: #666;\">
                    If you didn\'t create an account with SmartFix, please ignore this email.
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
?>';

// Write EmailVerification class
if (file_put_contents('includes/EmailVerification.php', $emailVerificationClass)) {
    $messages[] = "✅ EmailVerification.php created successfully";
} else {
    $messages[] = "❌ Failed to create EmailVerification.php";
}

// 2. Create verify_email.php
$verifyEmailPage = '<?php
session_start();
require_once \'includes/db.php\';
require_once \'includes/EmailVerification.php\';

$message = \'\';
$success = false;

if (isset($_GET[\'token\']) && !empty($_GET[\'token\'])) {
    $token = $_GET[\'token\'];
    
    $emailVerification = new EmailVerification($pdo);
    $result = $emailVerification->verifyToken($token);
    
    $message = $result[\'message\'];
    $success = $result[\'success\'];
} else {
    $message = \'No verification token provided.\';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - SmartFix</title>
    <style>
        body {
            font-family: \'Segoe UI\', sans-serif;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verification-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #28a745;
        }
        
        .error-icon {
            color: #dc3545;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .message {
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .success-message {
            color: #28a745;
        }
        
        .error-message {
            color: #dc3545;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.3s;
            margin: 0 10px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="icon <?php echo $success ? \'success-icon\' : \'error-icon\'; ?>">
            <?php echo $success ? \'✅\' : \'❌\'; ?>
        </div>
        
        <h1><?php echo $success ? \'Email Verified!\' : \'Verification Failed\'; ?></h1>
        
        <div class="message <?php echo $success ? \'success-message\' : \'error-message\'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        
        <div>
            <?php if ($success): ?>
                <a href="login.php" class="btn">Login Now</a>
                <a href="index.php" class="btn btn-secondary">Go Home</a>
            <?php else: ?>
                <a href="resend_verification.php" class="btn">Resend Verification</a>
                <a href="register.php" class="btn btn-secondary">Register Again</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>';

if (file_put_contents('verify_email.php', $verifyEmailPage)) {
    $messages[] = "✅ verify_email.php created successfully";
} else {
    $messages[] = "❌ Failed to create verify_email.php";
}

// 3. Create resend_verification.php
$resendVerificationPage = '<?php
session_start();
require_once \'includes/db.php\';
require_once \'includes/EmailVerification.php\';

$message = \'\';
$success = false;

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && isset($_POST[\'email\'])) {
    $email = trim($_POST[\'email\']);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailVerification = new EmailVerification($pdo);
        $result = $emailVerification->resendVerification($email);
        
        $message = $result[\'message\'];
        $success = $result[\'success\'];
    } else {
        $message = \'Please enter a valid email address.\';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - SmartFix</title>
    <style>
        body {
            font-family: \'Segoe UI\', sans-serif;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 90%;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: #007BFF;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>📧 Resend Verification</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? \'success\' : \'error\'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST[\'email\']) ? htmlspecialchars($_POST[\'email\']) : \'\'; ?>">
                </div>
                
                <button type="submit" class="btn">Resend Verification Email</button>
            </form>
        <?php endif; ?>
        
        <div class="links">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <a href="index.php">Home</a>
        </div>
    </div>
</body>
</html>';

if (file_put_contents('resend_verification.php', $resendVerificationPage)) {
    $messages[] = "✅ resend_verification.php created successfully";
} else {
    $messages[] = "❌ Failed to create resend_verification.php";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Components Created - SmartFix</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .message-list {
            list-style: none;
            padding: 0;
        }
        
        .message-list li {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-left: 4px solid #28a745;
            background-color: #d4edda;
            border-radius: 4px;
        }
        
        .error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .buttons {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Verification Components Created</h1>
        
        <ul class="message-list">
            <?php foreach ($messages as $message): ?>
                <li class="<?php echo strpos($message, '❌') !== false ? 'error' : ''; ?>">
                    <?php echo $message; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="buttons">
            <a href="update_registration_system.php" class="btn btn-success">🔄 Update Registration System</a>
            <a href="register.php" class="btn">🧪 Test Registration</a>
            <a href="index.php" class="btn">🏠 Home Page</a>
        </div>
    </div>
</body>
</html>