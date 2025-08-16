<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/EmailVerification.php';

$message = '';
$success = false;

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    $emailVerification = new EmailVerification($pdo);
    $result = $emailVerification->verifyToken($token);
    
    $message = $result['message'];
    $success = $result['success'];
} else {
    $message = 'No verification token provided.';
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
            font-family: 'Segoe UI', sans-serif;
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
        <div class="icon <?php echo $success ? 'success-icon' : 'error-icon'; ?>">
            <?php echo $success ? '✅' : '❌'; ?>
        </div>
        
        <h1><?php echo $success ? 'Email Verified!' : 'Verification Failed'; ?></h1>
        
        <div class="message <?php echo $success ? 'success-message' : 'error-message'; ?>">
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
</html>