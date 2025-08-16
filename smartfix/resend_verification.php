<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/EmailVerification.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailVerification = new EmailVerification($pdo);
        $result = $emailVerification->resendVerification($email);
        
        $message = $result['message'];
        $success = $result['success'];
        
        // Add quick verify link in development mode
        if ($success) {
            $message .= " 📧 Since we're in development mode, your verification email is available at: <a href='quick_verify.php' style='color: #007BFF; font-weight: bold;'>Quick Verify Page</a>";
        }
    } else {
        $message = 'Please enter a valid email address.';
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
            font-family: 'Segoe UI', sans-serif;
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
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <div><?php echo $message; ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
</html>