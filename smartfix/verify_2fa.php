<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/TwoFactorAuth.php';

// Check if user is in 2FA verification state
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_required'])) {
    header("Location: login.php");
    exit();
}

$twoFA = new TwoFactorAuth($pdo);
$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    $trustDevice = isset($_POST['trust_device']);
    
    if (empty($code)) {
        $error_message = "Please enter the verification code.";
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error_message = "Please enter a valid 6-digit code.";
    } else {
        $userId = $_SESSION['2fa_user_id'];
        
        if ($twoFA->verifyCode($userId, $code)) {
            // Code is valid, complete login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Trust device if requested
                if ($trustDevice) {
                    $deviceFingerprint = $twoFA->createDeviceFingerprint();
                    $twoFA->trustDevice($userId, $deviceFingerprint);
                }
                
                // Clean up 2FA session variables
                unset($_SESSION['2fa_user_id']);
                unset($_SESSION['2fa_required']);
                
                // Redirect based on user type
                if ($user['user_type'] == 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }
        } else {
            $error_message = "Invalid or expired verification code. Please try again.";
        }
    }
}

// Handle resend code request
if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    $userId = $_SESSION['2fa_user_id'];
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $newCode = $twoFA->generateAndStore2FACode($userId);
        if ($newCode && $twoFA->sendCodeByEmail($user['email'], $user['name'], $newCode)) {
            $success_message = "A new verification code has been sent to your email.";
        } else {
            $error_message = "Failed to send verification code. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .verification-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        .logo {
            margin-bottom: 20px;
        }
        .logo span {
            font-size: 28px;
            font-weight: bold;
            color: #004080;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            color: #343a40;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .header p {
            color: #6c757d;
            margin: 0;
            line-height: 1.5;
        }
        .security-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #343a40;
            font-weight: 500;
            text-align: left;
        }
        .code-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ced4da;
            border-radius: 8px;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 8px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .code-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
            outline: none;
        }
        .trust-device {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        .trust-device input {
            margin: 0;
        }
        .trust-device label {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }
        .submit-btn {
            width: 100%;
            padding: 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(40,167,69,0.1);
            margin-bottom: 15px;
        }
        .submit-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(40,167,69,0.2);
        }
        .resend-link {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        .resend-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            color: #007bff;
        }
        .timer {
            font-size: 14px;
            color: #dc3545;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="logo">
            <span>SmartFix</span>
        </div>
        
        <div class="security-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <div class="header">
            <h1>Two-Factor Authentication</h1>
            <p>We've sent a 6-digit verification code to your email address. Please enter it below to complete your login.</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="message error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="message success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="verify_2fa.php">
            <div class="form-group">
                <label for="code">Verification Code</label>
                <input type="text" name="code" id="code" class="code-input" maxlength="6" 
                       pattern="\d{6}" placeholder="000000" required autocomplete="off">
            </div>
            
            <div class="trust-device">
                <input type="checkbox" name="trust_device" id="trust_device">
                <label for="trust_device">Trust this device for 30 days</label>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-check"></i> Verify Code
            </button>
        </form>
        
        <div>
            <a href="verify_2fa.php?resend=1" class="resend-link">
                <i class="fas fa-redo"></i> Didn't receive the code? Resend
            </a>
        </div>
        
        <div class="timer" id="timer"></div>
        
        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>
    
    <script>
        // Auto-focus on code input
        document.getElementById('code').focus();
        
        // Auto-submit when 6 digits are entered
        document.getElementById('code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            e.target.value = value;
            
            if (value.length === 6) {
                // Auto-submit after a short delay
                setTimeout(() => {
                    document.querySelector('form').submit();
                }, 500);
            }
        });
        
        // Countdown timer (10 minutes)
        let timeLeft = 600; // 10 minutes in seconds
        const timerElement = document.getElementById('timer');
        
        function updateTimer() {
            if (timeLeft <= 0) {
                timerElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Code expired. Please request a new one.';
                timerElement.style.color = '#dc3545';
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerElement.innerHTML = `<i class="fas fa-clock"></i> Code expires in ${minutes}:${seconds.toString().padStart(2, '0')}`;
            timeLeft--;
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
    </script>
</body>
</html>