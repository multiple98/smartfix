<?php
session_start();
include('includes/db.php');

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: user/dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']) ?: null;
    $address = trim($_POST['address']) ?: null;
    $city = trim($_POST['city']) ?: null;
    $province = trim($_POST['province']) ?: null;
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error_message = "Please fill in all required fields.";
    } elseif (strlen($username) < 3) {
        $error_message = "Username must be at least 3 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        try {
            // Debug: Show what we're trying to insert
            $debug_info .= "Attempting to register: $username, $email, $full_name<br>";
            
            // Check if username or email already exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = "Username or email already exists. Please choose another.";
            } else {
                // Create new user with error handling
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $debug_info .= "Password hashed successfully<br>";
                
                // Try full insert first
                try {
                    $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address, city, province, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                    
                    $result = $insert_stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $address, $city, $province]);
                    
                    if ($result) {
                        $success_message = "Account created successfully! You can now login.";
                        $debug_info .= "Full registration successful<br>";
                        $_POST = array(); // Clear form
                    } else {
                        throw new Exception("Insert failed");
                    }
                    
                } catch (PDOException $e) {
                    $debug_info .= "Full insert failed: " . $e->getMessage() . "<br>";
                    
                    // Try minimal insert as fallback
                    try {
                        $simple_stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, status) VALUES (?, ?, ?, ?, 'active')");
                        $result = $simple_stmt->execute([$username, $email, $hashed_password, $full_name]);
                        
                        if ($result) {
                            $success_message = "Account created successfully (basic info)! You can now login.";
                            $debug_info .= "Basic registration successful<br>";
                            $_POST = array(); // Clear form
                        } else {
                            throw new Exception("Basic insert also failed");
                        }
                    } catch (PDOException $e2) {
                        $error_message = "Registration failed. Database error: " . $e2->getMessage();
                        $debug_info .= "Both inserts failed: " . $e2->getMessage() . "<br>";
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error occurred: " . $e->getMessage();
            $debug_info .= "General DB error: " . $e->getMessage() . "<br>";
        } catch (Exception $e) {
            $error_message = "An error occurred: " . $e->getMessage();
            $debug_info .= "General error: " . $e->getMessage() . "<br>";
        }
    }
}

// Zambian provinces for dropdown
$zambian_provinces = [
    'Lusaka', 'Copperbelt', 'Central', 'Eastern', 'Luapula', 
    'Muchinga', 'Northern', 'North-Western', 'Southern', 'Western'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SmartFix</title>
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
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            min-height: 700px;
        }

        .register-form-section {
            flex: 2;
            padding: 40px;
            overflow-y: auto;
        }

        .register-info-section {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 28px;
            color: #004080;
            margin-bottom: 8px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .required::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus, .form-select:focus {
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
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 14px;
        }

        .input-with-icon .form-control {
            padding-left: 40px;
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
            margin-top: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
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

        .debug-info {
            background-color: #e7f3ff;
            color: #004085;
            border: 1px solid #bee5eb;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 12px;
            border-radius: 4px;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
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
            padding: 0 15px;
            color: #666;
            font-size: 14px;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            margin: 0 10px;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #764ba2;
        }

        .info-section h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .info-section p {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 25px;
            opacity: 0.9;
        }

        .benefits {
            list-style: none;
            text-align: left;
        }

        .benefits li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }

        .benefits li i {
            margin-right: 12px;
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }

            .register-info-section {
                order: -1;
                flex: none;
                min-height: 200px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .register-form-section {
                padding: 30px 20px;
            }
        }

        .password-strength {
            font-size: 12px;
            margin-top: 5px;
            height: 16px;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #fd7e14; }
        .strength-strong { color: #28a745; }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin: 20px 0;
            font-size: 13px;
        }

        .terms-checkbox input[type="checkbox"] {
            margin-right: 8px;
            margin-top: 2px;
        }

        .terms-checkbox a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form-section">
            <div class="logo">
                <h1><i class="fas fa-tools"></i> SmartFix</h1>
                <p>Create your account to get started</p>
            </div>

            <?php if ($debug_info && (isset($_GET['debug']) || !empty($error_message))): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong><br>
                    <?php echo $debug_info; ?>
                </div>
            <?php endif; ?>

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
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name" class="required">Full Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" name="full_name" id="full_name" class="form-control" 
                                   placeholder="Enter your full name" required
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="username" class="required">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-at"></i>
                            <input type="text" name="username" id="username" class="form-control" 
                                   placeholder="Choose a username" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="required">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" class="form-control" 
                                   placeholder="Enter your email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phone" class="form-control" 
                                   placeholder="+260 977 123456"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="required">Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="Create a password" required>
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="required">Confirm Password</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" 
                                   placeholder="Confirm your password" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="address" id="address" class="form-control" 
                               placeholder="Street address"
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" name="city" id="city" class="form-control" 
                               placeholder="City"
                               value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="province">Province</label>
                        <select name="province" id="province" class="form-select">
                            <option value="">Select Province</option>
                            <?php foreach ($zambian_provinces as $province): ?>
                                <option value="<?php echo $province; ?>" 
                                        <?php echo (isset($_POST['province']) && $_POST['province'] == $province) ? 'selected' : ''; ?>>
                                    <?php echo $province; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" name="register" class="btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="divider">
                <span>Already have an account?</span>
            </div>

            <div class="links">
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="index.php">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="fix_registration_database.php">
                    <i class="fas fa-database"></i> Fix Database
                </a>
            </div>
        </div>

        <div class="register-info-section">
            <div class="info-section">
                <h2>Join SmartFix Today</h2>
                <p>Create your account and unlock access to our comprehensive service management platform.</p>
                
                <ul class="benefits">
                    <li><i class="fas fa-shopping-cart"></i> Shop for quality products</li>
                    <li><i class="fas fa-tools"></i> Request repair services</li>
                    <li><i class="fas fa-truck"></i> Track your orders</li>
                    <li><i class="fas fa-user-friends"></i> Connect with technicians</li>
                    <li><i class="fas fa-star"></i> Rate and review services</li>
                    <li><i class="fas fa-envelope"></i> Get support & updates</li>
                    <li><i class="fas fa-shield-alt"></i> Secure & safe platform</li>
                </ul>

                <div style="margin-top: 30px; padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                    <h4 style="margin-bottom: 10px; font-size: 16px;">Having Issues?</h4>
                    <p style="font-size: 12px; margin: 0;">
                        If registration fails, click "Fix Database" below the form.<br>
                        This will set up the database properly.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthDiv = document.getElementById('password-strength');
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
            } else if (strength < 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 3) {
                strengthDiv.textContent = 'Medium strength';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = e.target.value;
            
            if (confirmPassword && password !== confirmPassword) {
                e.target.style.borderColor = '#dc3545';
            } else {
                e.target.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>
</html>