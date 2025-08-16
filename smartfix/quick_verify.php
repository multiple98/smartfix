<?php
// Quick Email Verification Access
session_start();
include('includes/db.php');

$message = '';
$verification_done = false;

// Handle quick verification
if (isset($_GET['verify']) && isset($_GET['token'])) {
    require_once 'includes/EmailVerification.php';
    $emailVerification = new EmailVerification($pdo);
    $result = $emailVerification->verifyToken($_GET['token']);
    
    if ($result['success']) {
        $message = "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'><strong>✅ Success!</strong> " . $result['message'] . "</div>";
        $verification_done = true;
    } else {
        $message = "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'><strong>❌ Error:</strong> " . $result['message'] . "</div>";
    }
}

// Get unverified users
$unverified_users = [];
try {
    $stmt = $pdo->query("
        SELECT id, name, email, verification_token, verification_sent_at 
        FROM users 
        WHERE is_verified = 0 AND verification_token IS NOT NULL 
        ORDER BY verification_sent_at DESC 
        LIMIT 10
    ");
    $unverified_users = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Email Verification - SmartFix</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007BFF;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: #007BFF;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        .user-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .user-table tr:hover {
            background: #f8f9fa;
        }
        
        .verify-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .verify-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-users i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #117a8b);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .success-animation {
            text-align: center;
            padding: 40px;
        }
        
        .success-animation i {
            font-size: 64px;
            color: #28a745;
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 60%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            80% { transform: translateY(-10px); }
        }
        
        .timestamp {
            font-size: 11px;
            color: #999;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope-check"></i> Quick Email Verification</h1>
            <p>Access your verification emails instantly - No email server required!</p>
        </div>
        
        <div class="content">
            <?php echo $message; ?>
            
            <?php if ($verification_done): ?>
                <div class="success-animation">
                    <i class="fas fa-check-circle"></i>
                    <h3>Account Verified Successfully!</h3>
                    <p>You can now login to your SmartFix account.</p>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> How This Works</h3>
                    <p><strong>Why no emails?</strong> The system is in development mode to avoid mail server issues.</p>
                    <p><strong>How to verify:</strong> Your verification emails are shown below. Click "Verify Now" to activate your account.</p>
                </div>
                
                <h3><i class="fas fa-users"></i> Accounts Waiting for Verification</h3>
                
                <?php if (empty($unverified_users)): ?>
                    <div class="no-users">
                        <i class="fas fa-inbox"></i>
                        <h4>No Unverified Accounts</h4>
                        <p>All accounts are verified, or no accounts have been created yet.</p>
                        <p><a href="register.php" class="btn btn-success">Create New Account</a></p>
                    </div>
                <?php else: ?>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Name</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-clock"></i> Registered</th>
                                <th><i class="fas fa-check"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unverified_users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($user['verification_sent_at'])); ?>
                                        <br><span class="timestamp"><?php echo date('D', strtotime($user['verification_sent_at'])); ?></span>
                                    </td>
                                    <td>
                                        <a href="?verify=1&token=<?php echo $user['verification_token']; ?>" 
                                           class="verify-btn"
                                           onclick="return confirm('Verify account for <?php echo htmlspecialchars($user['name']); ?>?')">
                                            <i class="fas fa-check"></i> Verify Now
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="buttons">
                <a href="register.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Register New Account
                </a>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="dev_email_viewer.php" class="btn btn-info">
                    <i class="fas fa-code"></i> Advanced Debug View
                </a>
            </div>
        </div>
    </div>
</body>
</html>