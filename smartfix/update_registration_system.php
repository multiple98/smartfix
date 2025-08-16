<?php
// Update Registration System with Email Verification
error_reporting(E_ALL);
ini_set('display_errors', 1);

$messages = [];

// 1. Update register.php to include email verification
$registerContent = '<?php
session_start();
include(\'includes/db.php\');
require_once \'includes/EmailVerification.php\';

$error_message = \'\';
$success_message = \'\';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST[\'username\']);
    $email = trim($_POST[\'email\']);
    $password = $_POST[\'password\'];
    $confirm_password = $_POST[\'confirm_password\'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error_message = "Username or email already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (unverified)
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, is_verified, created_at) 
                    VALUES (?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$username, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();
                
                // Send verification email
                $emailVerification = new EmailVerification($pdo);
                $email_sent = $emailVerification->sendVerificationEmail($user_id, $email, $username);
                
                if ($email_sent) {
                    $success_message = "Registration successful! Please check your email to verify your account before logging in.";
                } else {
                    $success_message = "Registration successful! However, we couldn\'t send the verification email. You can request a new one from the login page.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Registration failed. Please try again.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: \'\';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #007BFF, #28a745, #ffc107);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 50px;
            color: #007BFF;
            margin-bottom: 10px;
        }

        .logo h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007BFF;
            background: white;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        .register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .register-btn i {
            margin-right: 8px;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #999;
            font-size: 14px;
        }

        .divider::before {
            content: \'\';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
        }

        .login-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .features {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .features h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .features ul {
            list-style: none;
            padding: 0;
        }

        .features li {
            padding: 5px 0;
            color: #666;
            font-size: 13px;
        }

        .features li i {
            color: #28a745;
            margin-right: 8px;
            width: 12px;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .logo h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-tools"></i>
            <h1>SmartFix</h1>
            <p>Create your account</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php else: ?>
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST[\'username\']) ? htmlspecialchars($_POST[\'username\']) : \'\'; ?>">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST[\'email\']) ? htmlspecialchars($_POST[\'email\']) : \'\'; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
        <?php endif; ?>

        <div class="divider">
            <span>Already have an account?</span>
        </div>

        <div class="login-link">
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Sign In Here
            </a>
        </div>

        <?php if (!$success_message): ?>
            <div class="features">
                <h4>Why choose SmartFix?</h4>
                <ul>
                    <li><i class="fas fa-check"></i> Professional repair services</li>
                    <li><i class="fas fa-check"></i> Qualified technicians</li>
                    <li><i class="fas fa-check"></i> Quick response time</li>
                    <li><i class="fas fa-check"></i> Secure online platform</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Password strength checker
        document.getElementById(\'password\').addEventListener(\'input\', function() {
            const password = this.value;
            const strengthDiv = document.getElementById(\'passwordStrength\');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = \'\';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength++;
            else feedback.push(\'At least 8 characters\');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push(\'Lowercase letter\');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push(\'Uppercase letter\');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push(\'Number\');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push(\'Special character\');
            
            let strengthText = \'\';
            let strengthClass = \'\';
            
            if (strength < 2) {
                strengthText = \'Weak\';
                strengthClass = \'strength-weak\';
            } else if (strength < 4) {
                strengthText = \'Medium\';
                strengthClass = \'strength-medium\';
            } else {
                strengthText = \'Strong\';
                strengthClass = \'strength-strong\';
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">Password strength: ${strengthText}</span>`;
            if (feedback.length > 0 && strength < 4) {
                strengthDiv.innerHTML += `<br><small>Missing: ${feedback.join(\', \')}</small>`;
            }
        });

        // Form validation
        document.getElementById(\'registerForm\').addEventListener(\'submit\', function(e) {
            const password = document.getElementById(\'password\').value;
            const confirmPassword = document.getElementById(\'confirm_password\').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert(\'Passwords do not match!\');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert(\'Password must be at least 6 characters long!\');
                return false;
            }
        });
    </script>
</body>
</html>';

// Write updated register.php
if (file_put_contents('register.php', $registerContent)) {
    $messages[] = "✅ register.php updated with email verification";
} else {
    $messages[] = "❌ Failed to update register.php";
}

// 2. Update login.php to check email verification
$loginContent = '<?php
session_start();
include(\'includes/db.php\');

$error_message = \'\';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST[\'username\']);
    $password = $_POST[\'password\'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, username, email, password, is_verified FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user[\'password\'])) {
                // Check if email is verified
                if (!$user[\'is_verified\']) {
                    $error_message = "Please verify your email address before logging in. <a href=\'resend_verification.php\' style=\'color: #007BFF;\'>Resend verification email</a>";
                } else {
                    // Login successful
                    $_SESSION[\'user_id\'] = $user[\'id\'];
                    $_SESSION[\'username\'] = $user[\'username\'];
                    $_SESSION[\'email\'] = $user[\'email\'];
                    $_SESSION[\'logged_in\'] = true;
                    
                    // Redirect to dashboard
                    header("Location: user/dashboard.php");
                    exit();
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error_message = "Login failed. Please try again.";
            error_log("Login error: " . $e->getMessage());
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: \'\';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #007BFF, #28a745, #ffc107);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 50px;
            color: #007BFF;
            margin-bottom: 10px;
        }

        .logo h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007BFF;
            background: white;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }

        .login-btn i {
            margin-right: 8px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
        }

        .alert i {
            margin-right: 10px;
            font-size: 16px;
            margin-top: 2px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #999;
            font-size: 14px;
        }

        .divider::before {
            content: \'\';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e1e5e9;
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
        }

        .register-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #007BFF;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .logo h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-tools"></i>
            <h1>SmartFix</h1>
            <p>Welcome back!</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error_message; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username or Email
                </label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST[\'username\']) ? htmlspecialchars($_POST[\'username\']) : \'\'; ?>">
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="links">
            <a href="resend_verification.php">Resend Verification</a>
            <a href="index.php">Forgot Password?</a>
        </div>

        <div class="divider">
            <span>Don\'t have an account?</span>
        </div>

        <div class="register-link">
            <a href="register.php">
                <i class="fas fa-user-plus"></i> Create Account
            </a>
        </div>
    </div>
</body>
</html>';

// Write updated login.php
if (file_put_contents('login.php', $loginContent)) {
    $messages[] = "✅ login.php updated with email verification check";
} else {
    $messages[] = "❌ Failed to update login.php";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration System Updated - SmartFix</title>
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
        
        .status-info {
            background-color: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .status-info h3 {
            color: #007BFF;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Registration System Updated</h1>
        
        <ul class="message-list">
            <?php foreach ($messages as $message): ?>
                <li class="<?php echo strpos($message, '❌') !== false ? 'error' : ''; ?>">
                    <?php echo $message; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="status-info">
            <h3>✅ Email Verification System Complete!</h3>
            <p>Your SmartFix platform now includes:</p>
            <ul>
                <li>✅ Email verification for new user registrations</li>
                <li>✅ Verification email sending with secure tokens</li>
                <li>✅ Email verification page with user-friendly interface</li>
                <li>✅ Resend verification functionality</li>
                <li>✅ Login system that checks email verification status</li>
                <li>✅ Database logging of all verification activities</li>
            </ul>
        </div>
        
        <div class="buttons">
            <a href="register.php" class="btn btn-success">🧪 Test Registration</a>
            <a href="login.php" class="btn">🔐 Test Login</a>
            <a href="resend_verification.php" class="btn">📧 Test Resend</a>
            <a href="index.php" class="btn">🏠 Home Page</a>
        </div>
    </div>
</body>
</html>