<?php
session_start();
include('includes/db.php');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on where they came from or default
    $redirect = $_GET['redirect'] ?? 'dashboard.php';
    header("Location: $redirect");
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            // Check in users table first
            $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_logged_in'] = true;
                    
                    // Log successful login
                    try {
                        $log_stmt = $pdo->prepare("INSERT INTO user_login_logs (user_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, NOW())");
                        $log_stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                    } catch (PDOException $e) {
                        // Table might not exist, continue without logging
                    }
                    
                    // Redirect to intended page or dashboard
                    $redirect = $_GET['redirect'] ?? 'dashboard.php';
                    if (strpos($redirect, 'shop/') !== false || strpos($redirect, 'cart') !== false || strpos($redirect, 'checkout') !== false) {
                        header("Location: $redirect");
                    } else {
                        header("Location: user/dashboard.php");
                    }
                    exit;
                } else {
                    $error_message = "Your account has been deactivated. Please contact support.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: flex;
            min-height: 600px;
        }

        .login-form-section {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-info-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo h1 {
            font-size: 32px;
            color: #004080;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .input-with-icon .form-control {
            padding-left: 45px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s ease;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
        }

        .divider span {
            background: white;
            padding: 0 20px;
            color: #666;
        }

        .links {
            text-align: center;
            margin-top: 30px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin: 0 15px;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #764ba2;
        }

        .info-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }

        .info-section p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .features {
            list-style: none;
            text-align: left;
        }

        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .features li i {
            margin-right: 15px;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }

            .login-form-section,
            .login-info-section {
                padding: 40px 30px;
            }

            .login-info-section {
                order: -1;
            }
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            font-size: 14px;
        }

        .remember-forgot a {
            color: #667eea;
            text-decoration: none;
        }

        .remember-forgot a:hover {
            text-decoration: underline;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form-section">
            <div class="logo">
                <h1><i class="fas fa-tools"></i> SmartFix</h1>
                <p>Welcome back! Please login to your account</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" class="form-control" 
                               placeholder="Enter your username or email" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="remember-forgot">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#">Forgot password?</a>
                </div>

                <button type="submit" name="login" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="divider">
                <span>Don't have an account?</span>
            </div>

            <div class="links">
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>

        <div class="login-info-section">
            <div class="info-section">
                <h2>Access Your SmartFix Account</h2>
                <p>Login to access your dashboard, track orders, manage your profile, and enjoy all SmartFix services.</p>
                
                <ul class="features">
                    <li><i class="fas fa-shopping-cart"></i> Shop for products</li>
                    <li><i class="fas fa-clipboard-list"></i> Track your orders</li>
                    <li><i class="fas fa-tools"></i> Request services</li>
                    <li><i class="fas fa-user-cog"></i> Manage your profile</li>
                    <li><i class="fas fa-envelope"></i> Message support</li>
                </ul>

                <div style="margin-top: 40px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 10px;">
                    <h4 style="margin-bottom: 15px;">Test Account</h4>
                    <p style="font-size: 14px;">
                        <strong>Username:</strong> customer1<br>
                        <strong>Password:</strong> password123
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>