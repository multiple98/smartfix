<?php
session_start();
include('includes/db.php');
require_once 'includes/EmailVerification.php';
require_once 'includes/SecurityManager.php';

$error_message = '';
$success_message = '';
$current_form = isset($_GET['form']) ? $_GET['form'] : 'login';

// Check for logout message
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_message = "You have been successfully logged out.";
}

// Initialize security manager for admin functionality
$security = new SecurityManager($pdo);
$security::secureSession();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'];
    
    if ($form_type === 'login') {
        // Handle regular user login
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error_message = "Please enter both username and password.";
        } else {
            try {
                // Check user credentials
                $stmt = $pdo->prepare("SELECT id, name, email, password, is_verified FROM users WHERE name = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Check if email is verified
                    if (!$user['is_verified']) {
                        $error_message = "Please verify your email address before logging in. <a href='resend_verification.php' style='color: #007BFF;'>Resend verification email</a> or <a href='quick_verify.php' style='color: #007BFF; font-weight: bold;'>Quick Verify Page</a>";
                    } else {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['name'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_type'] = 'user';
                        $_SESSION['logged_in'] = true;
                        
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
    } elseif ($form_type === 'admin') {
        // Handle admin login
        $username = trim($_POST['admin_username']);
        $password = $_POST['admin_password'];
        
        if (empty($username) || empty($password)) {
            $error_message = "Please enter both username and password.";
        } else {
            try {
                // Check admin credentials in database first
                $stmt = $pdo->prepare("SELECT id, username, password, email, name FROM admins WHERE username = ? OR email = ?");
                $stmt->execute([$username, $username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Admin login successful
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['user_name'] = $admin['name'] ?? $admin['username'];
                    $_SESSION['user_email'] = $admin['email'];
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['role'] = 'admin';
                    
                    header("Location: admin/admin_dashboard_new.php");
                    exit();
                } else {
                    // Fallback to hardcoded admin for compatibility
                    if (($username === 'admin' && $password === '1234') || 
                        ($username === 'admin@smartfix.com' && $password === '1234')) {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = 1;
                        $_SESSION['admin_username'] = 'admin';
                        $_SESSION['admin_email'] = 'admin@smartfix.com';
                        $_SESSION['user_name'] = 'Administrator';
                        $_SESSION['user_email'] = 'admin@smartfix.com';
                        $_SESSION['user_id'] = 1;
                        $_SESSION['user_type'] = 'admin';
                        $_SESSION['role'] = 'admin';
                        
                        header("Location: admin/admin_dashboard_new.php");
                        exit();
                    } else {
                        $error_message = "Invalid admin credentials.";
                    }
                }
            } catch (PDOException $e) {
                // Fallback to hardcoded admin if database error
                if (($username === 'admin' && $password === '1234') || 
                    ($username === 'admin@smartfix.com' && $password === '1234')) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = 1;
                    $_SESSION['admin_username'] = 'admin';
                    $_SESSION['admin_email'] = 'admin@smartfix.com';
                    $_SESSION['user_name'] = 'Administrator';
                    $_SESSION['user_email'] = 'admin@smartfix.com';
                    $_SESSION['user_id'] = 1;
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['role'] = 'admin';
                    
                    header("Location: admin/admin_dashboard_new.php");
                    exit();
                } else {
                    $error_message = "Admin login failed. Please try again.";
                }
            }
        }
    } elseif ($form_type === 'register') {
        // Handle user registration
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
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
                $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ? OR email = ?");
                $stmt->execute([$username, $email]);
                
                if ($stmt->fetch()) {
                    $error_message = "Username or email already exists.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user (unverified)
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, is_verified, created_at) 
                        VALUES (?, ?, ?, 0, NOW())
                    ");
                    $stmt->execute([$username, $email, $hashed_password]);
                    $user_id = $pdo->lastInsertId();
                    
                    // Send verification email
                    $emailVerification = new EmailVerification($pdo);
                    $email_sent = $emailVerification->sendVerificationEmail($user_id, $email, $username);
                    
                    if ($email_sent) {
                        $success_message = "Registration successful! 📧 Since we're in development mode, your verification email is available at: <a href='quick_verify.php' style='color: #007BFF; font-weight: bold;'>Quick Verify Page</a>";
                    } else {
                        $success_message = "Registration successful! However, we couldn't send the verification email. You can request a new one from the login page.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Registration failed. Please try again.";
                error_log("Registration error: " . $e->getMessage());
            }
        }
    } elseif ($form_type === 'admin') {
        // Handle admin login
        if (!$security->checkRateLimit('admin_login')) {
            $error_message = "Too many login attempts. Please try again in 15 minutes.";
        } else {
            // Verify CSRF token
            if (!isset($_POST['csrf_token']) || !$security->verifyCSRFToken($_POST['csrf_token'])) {
                $error_message = "Invalid security token. Please refresh and try again.";
            } else {
                $username = $security->sanitizeInput($_POST['admin_username']);
                $password = $_POST['admin_password'];

                // Check if username exists
                try {
                    $query = "SELECT * FROM admins WHERE username = ?";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    // If admins table doesn't exist, show helpful error
                    if (strpos($e->getMessage(), "doesn't exist") !== false) {
                        $error_message = "Admin table not found. Please run the <a href='create_admin_user.php' style='color: #007BFF;'>database setup</a> first.";
                        $admin = false;
                    } else {
                        $error_message = "Database error. Please try again.";
                        $admin = false;
                    }
                }

                if ($admin && password_verify($password, $admin['password'])) {
                    // Successful login
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['user_name'] = $admin['username'];
                    $_SESSION['user_id'] = $admin['id'];
                    
                    // Log the successful login
                    $security->auditLog($admin['id'], 'admin_login_success', null, null, null, ['username' => $username]);
                    
                    // Reset rate limit on success
                    $security->resetRateLimit('admin_login');
                    
                    // Redirect to dashboard
                    header("Location: admin/admin_dashboard_new.php");
                    exit();
                } else {
                    $error_message = "Invalid admin credentials!";
                    
                    // Log failed login attempt
                    $security->auditLog(null, 'admin_login_failed', null, null, null, ['username' => $username]);
                }
            }
        }
    }
}

// Generate CSRF token for admin form
$csrfToken = $security->generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartFix - Authentication</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #007BFF, #0056b3);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #007BFF, #28a745, #ffc107, #dc3545);
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

        .form-tabs {
            display: flex;
            margin-bottom: 30px;
            border-radius: 10px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .form-tab {
            flex: 1;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            border: none;
            background: transparent;
        }

        .form-tab.active {
            background: #007BFF;
            color: white;
        }

        .form-tab:not(.active):hover {
            background: #e9ecef;
        }

        .form-content {
            display: none;
        }

        .form-content.active {
            display: block;
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

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

        .auth-btn {
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

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }

        .auth-btn.admin {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }

        .auth-btn.admin:hover {
            box-shadow: 0 8px 25px rgba(220,53,69,0.3);
        }

        .auth-btn.register {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .auth-btn.register:hover {
            box-shadow: 0 8px 25px rgba(40,167,69,0.3);
        }

        .auth-btn i {
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

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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

        .features {
            margin-top: 25px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.05), rgba(5, 150, 105, 0.05));
            border-radius: 15px;
            border: 1px solid rgba(6, 182, 212, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .features h4 {
            color: #06b6d4;
            margin-bottom: 20px;
            font-size: 18px;
            text-align: center;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .features ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 12px;
        }

        .features li {
            padding: 10px 15px;
            color: rgba(6, 182, 212, 0.9);
            font-size: 14px;
            background: rgba(6, 182, 212, 0.05);
            border-radius: 8px;
            border-left: 3px solid #06b6d4;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .features li:hover {
            background: rgba(6, 182, 212, 0.1);
            transform: translateX(5px);
            box-shadow: 0 3px 10px rgba(6, 182, 212, 0.2);
        }

        .features li i {
            color: #059669;
            margin-right: 12px;
            width: 16px;
            font-size: 14px;
            text-align: center;
        }

        /* Professional Admin Form Styling */
        .auth-container.admin-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            border: 2px solid #ffc107;
            box-shadow: 0 20px 40px rgba(255, 193, 7, 0.2), 0 0 20px rgba(255, 193, 7, 0.1);
        }

        .auth-container.admin-mode .logo {
            color: #ffc107;
        }

        .auth-container.admin-mode .logo h1 {
            background: linear-gradient(135deg, #ffc107, #ffecb3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
        }

        .auth-container.admin-mode .form-tab {
            background: linear-gradient(135deg, #ffc107, #e6ac00);
            color: #1a1a2e;
            font-weight: bold;
            border: none;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        /* Professional User Form Styling */
        .auth-container:not(.admin-mode) {
            background: linear-gradient(135deg, #0a1929 0%, #1e293b 50%, #0f172a 100%);
            border: 2px solid #06b6d4;
            box-shadow: 0 20px 40px rgba(6, 182, 212, 0.2), 0 0 20px rgba(6, 182, 212, 0.1);
        }

        .auth-container:not(.admin-mode) .logo {
            color: #06b6d4;
        }

        .auth-container:not(.admin-mode) .logo h1 {
            background: linear-gradient(135deg, #06b6d4, #67e8f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(6, 182, 212, 0.3);
        }

        .auth-container:not(.admin-mode) .form-tab {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: #0a1929;
            font-weight: bold;
            border: none;
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.3);
        }

        /* Professional User Forms Header */
        .user-form-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .user-form-header h2 {
            color: #06b6d4;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .user-form-header p {
            color: rgba(6, 182, 212, 0.8);
            font-size: 14px;
            margin: 0;
        }

        /* Enhanced User Form Fields */
        .auth-container:not(.admin-mode) .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .auth-container:not(.admin-mode) .form-group label {
            color: #06b6d4;
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .auth-container:not(.admin-mode) .form-group label i {
            margin-right: 12px;
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        .auth-container:not(.admin-mode) .form-group input {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .auth-container:not(.admin-mode) .form-group input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.4), 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        .auth-container:not(.admin-mode) .form-group input:valid {
            border-color: #059669;
        }

        /* Professional User Button */
        .auth-btn {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 50%, #0e7490 100%);
            color: white;
            font-weight: bold;
            font-size: 18px;
            padding: 18px 40px;
            border: none;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.4), 0 5px 15px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            width: 100%;
            cursor: pointer;
        }

        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .auth-btn:hover::before {
            left: 100%;
        }

        .auth-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(6, 182, 212, 0.6), 0 8px 25px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 50%, #22d3ee 100%);
        }

        .auth-btn:active {
            transform: translateY(-1px);
        }

        .auth-btn i {
            margin-right: 12px;
            font-size: 20px;
        }

        /* Register button variant */
        .auth-btn.register {
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%);
            box-shadow: 0 10px 30px rgba(5, 150, 105, 0.4), 0 5px 15px rgba(0,0,0,0.2);
        }

        .auth-btn.register:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            box-shadow: 0 15px 40px rgba(5, 150, 105, 0.6), 0 8px 25px rgba(0,0,0,0.3);
        }

        /* Enhanced User Links */
        .auth-container:not(.admin-mode) .links a {
            color: #06b6d4;
            text-decoration: none;
            font-weight: 500;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .auth-container:not(.admin-mode) .links a:hover {
            background: rgba(6, 182, 212, 0.1);
            border-color: #06b6d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.2);
        }

        /* Professional User Security Badge */
        .auth-container:not(.admin-mode) #login-form::before {
            content: '\f023';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.4);
            z-index: 10;
        }

        .auth-container:not(.admin-mode) #register-form::before {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 5px 15px rgba(5, 150, 105, 0.4);
            z-index: 10;
        }

        .form-content {
            position: relative;
        }

        .admin-warning {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            border: none;
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .admin-warning::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ffc107, #dc3545, #ffc107);
            border-radius: 17px;
            z-index: -1;
            animation: warningGlow 3s ease-in-out infinite alternate;
        }

        @keyframes warningGlow {
            0% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .admin-warning i {
            margin-right: 10px;
            font-size: 18px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .auth-container {
                margin: 10px;
                padding: 30px 25px;
                max-width: 100%;
            }
            
            .logo h1 {
                font-size: 24px;
            }
            
            .form-tabs {
                flex-direction: column;
            }
            
            .form-tab {
                padding: 15px;
                font-size: 16px;
            }
            
            .auth-btn {
                padding: 16px 30px;
                font-size: 16px;
            }
            
            .form-group input {
                padding: 14px 18px;
                font-size: 16px;
            }
            
            .form-container::after {
                display: none;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .auth-container {
                padding: 25px 20px;
            }
            
            .logo h1 {
                font-size: 20px;
            }
            
            .auth-btn {
                padding: 14px 25px;
                font-size: 15px;
            }
        }

        /* Enhanced Focus States */
        .form-group input:focus::placeholder {
            opacity: 0.5;
            transform: translateX(10px);
        }

        .form-group textarea:focus::placeholder {
            opacity: 0.5;
            transform: translateY(10px);
        }

        /* Loading Animation for Buttons */
        .auth-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .auth-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Mobile Menu Hamburger */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 8px;
            z-index: 1001;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: white;
            margin: 3px 0;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        /* Mobile Navigation */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 25px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .header-nav {
                position: relative;
            }

            .hamburger {
                display: flex;
            }

            .nav-links {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                height: 100vh;
                background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                flex-direction: column;
                align-items: flex-start;
                padding: 100px 30px 30px;
                transition: left 0.3s ease;
                z-index: 1000;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            }

            .nav-links.active {
                left: 0;
            }

            .nav-links a {
                width: 100%;
                margin: 10px 0;
                padding: 15px 20px;
                border-radius: 10px;
                font-size: 16px;
            }
        }

        /* Enhanced Admin Form Fields */
        #admin-form .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        #admin-form .form-group label {
            color: #ffc107;
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        #admin-form .form-group label i {
            margin-right: 12px;
            width: 20px;
            font-size: 16px;
            text-align: center;
        }

        #admin-form .form-group input {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        #admin-form .form-group input:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.4), 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(-2px);
        }

        #admin-form .form-group input:valid {
            border-color: #28a745;
        }

        /* Professional Admin Button */
        .auth-btn.admin {
            background: linear-gradient(135deg, #ffc107 0%, #e6ac00 50%, #cc9900 100%);
            color: #1a1a2e;
            font-weight: bold;
            font-size: 18px;
            padding: 18px 40px;
            border: none;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(255, 193, 7, 0.4), 0 5px 15px rgba(0,0,0,0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-btn.admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .auth-btn.admin:hover::before {
            left: 100%;
        }

        .auth-btn.admin:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 193, 7, 0.6), 0 8px 25px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #ffd54f 0%, #ffc107 50%, #e6ac00 100%);
        }

        .auth-btn.admin:active {
            transform: translateY(-1px);
        }

        .auth-btn.admin i {
            margin-right: 12px;
            font-size: 20px;
        }

        /* Enhanced Admin Links */
        #admin-form .links {
            margin-top: 30px;
            text-align: center;
        }

        #admin-form .links a {
            color: #ffc107;
            text-decoration: none;
            font-weight: 500;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        #admin-form .links a:hover {
            background: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2);
        }

        /* Professional Security Badge */
        #admin-form::before {
            content: '\f21b';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: -10px;
            right: -10px;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            z-index: 10;
        }

        #admin-form {
            position: relative;
        }

        /* Enhanced Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .auth-container {
                padding: 30px 20px;
                margin: 10px 0;
                width: calc(100% - 20px);
                max-width: none;
            }
            
            .logo h1 {
                font-size: 24px;
            }
            
            .form-group input {
                font-size: 16px; /* Prevent zoom on iOS */
            }
            
            .auth-btn {
                font-size: 18px;
                padding: 18px;
            }

            .form-tab {
                font-size: 12px;
                padding: 10px 6px;
            }

            /* Admin Mobile Styles */
            .auth-container.admin-mode {
                padding: 25px 15px;
                margin: 5px 0;
            }

            .admin-warning {
                padding: 15px;
                font-size: 13px;
                margin-bottom: 20px;
            }

            .admin-warning i {
                font-size: 16px;
            }

            #admin-form .form-group {
                margin-bottom: 20px;
            }

            #admin-form .form-group input {
                padding: 12px 15px;
                font-size: 16px;
            }

            .auth-btn.admin {
                font-size: 16px;
                padding: 15px 30px;
                letter-spacing: 0.5px;
            }

            #admin-form::before {
                width: 35px;
                height: 35px;
                font-size: 16px;
                top: -8px;
                right: -8px;
            }

            /* User Mobile Styles */
            .auth-container:not(.admin-mode) {
                padding: 25px 15px;
                margin: 5px 0;
            }

            .auth-container:not(.admin-mode) .form-group {
                margin-bottom: 20px;
            }

            .auth-container:not(.admin-mode) .form-group input {
                padding: 12px 15px;
                font-size: 16px;
            }

            .auth-btn {
                font-size: 16px;
                padding: 15px 30px;
                letter-spacing: 0.5px;
            }

            .user-form-header h2 {
                font-size: 20px;
            }

            .auth-container:not(.admin-mode) #login-form::before,
            .auth-container:not(.admin-mode) #register-form::before {
                width: 30px;
                height: 30px;
                font-size: 14px;
                top: -8px;
                right: -8px;
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 25px 15px;
                margin: 5px 0;
            }
            
            .logo i {
                font-size: 40px;
            }
            
            .logo h1 {
                font-size: 22px;
            }

            .auth-container.admin-mode {
                padding: 20px 10px;
            }

            .admin-warning {
                padding: 12px;
                font-size: 12px;
            }

            #admin-form .form-group label {
                font-size: 14px;
            }

            #admin-form .form-group input {
                padding: 10px 12px;
                border-radius: 10px;
            }

            .auth-btn.admin {
                font-size: 14px;
                padding: 12px 25px;
                border-radius: 30px;
            }

            #admin-form .links a {
                font-size: 12px;
                padding: 6px 12px;
                margin: 0 5px;
            }

            /* User Mobile Small Styles */
            .auth-container:not(.admin-mode) {
                padding: 20px 10px;
            }

            .user-form-header h2 {
                font-size: 18px;
            }

            .auth-container:not(.admin-mode) .form-group label {
                font-size: 14px;
            }

            .auth-container:not(.admin-mode) .form-group input {
                padding: 10px 12px;
                border-radius: 10px;
            }

            .auth-btn {
                font-size: 14px;
                padding: 12px 25px;
                border-radius: 30px;
            }

            .auth-container:not(.admin-mode) .links a {
                font-size: 12px;
                padding: 6px 12px;
                margin: 0 5px;
            }

            .features {
                padding: 15px;
                margin-top: 20px;
            }

            .features h4 {
                font-size: 16px;
            }

            .features li {
                font-size: 12px;
            }
        }

        /* Additional Professional Admin Features */
        .admin-form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-form-header h2 {
            color: #ffc107;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .admin-form-header p {
            color: rgba(255, 193, 7, 0.8);
            font-size: 14px;
            margin: 0;
        }

        /* Loading Animation for Admin Form */
        .admin-loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 26, 46, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .admin-loading.active {
            display: flex;
        }

        .admin-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 193, 7, 0.3);
            border-left-color: #ffc107;
            border-radius: 50%;
            animation: adminSpin 1s ease-in-out infinite;
        }

        @keyframes adminSpin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="auth-container<?php echo $current_form === 'admin' ? ' admin-mode' : ''; ?>">
        <div class="logo">
            <i class="fas fa-tools"></i>
            <h1>SmartFix</h1>
            <p>Your trusted repair service</p>
        </div>

        <?php if ($current_form !== 'admin'): ?>
        <div class="form-tabs">
            <button class="form-tab active" onclick="switchForm('login')">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            <button class="form-tab" onclick="switchForm('register')">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </div>
        <?php else: ?>
        <div class="form-tabs">
            <button class="form-tab active">
                <i class="fas fa-shield-alt"></i> Administrator Login
            </button>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo $error_message; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?php echo $success_message; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($current_form !== 'admin'): ?>
        <!-- Login Form -->
        <div class="form-content active" id="login-form">
            <div class="user-form-header">
                <h2><i class="fas fa-sign-in-alt"></i> Welcome Back</h2>
                <p>Sign in to your SmartFix account</p>
            </div>

            <form method="POST" id="loginForm">
                <input type="hidden" name="form_type" value="login">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username or email"
                           value="<?php echo isset($_POST['username']) && $_POST['form_type'] === 'login' ? htmlspecialchars($_POST['username']) : ''; ?>"
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password"
                           autocomplete="current-password">
                </div>

                <button type="submit" class="auth-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In Securely
                </button>
            </form>

            <div class="links">
                <a href="resend_verification.php"><i class="fas fa-envelope"></i> Resend Verification</a>
                <a href="index.php"><i class="fas fa-key"></i> Forgot Password?</a>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="form-content" id="register-form">
            <div class="user-form-header">
                <h2><i class="fas fa-user-plus"></i> Join SmartFix</h2>
                <p>Create your account and get started today</p>
            </div>

            <?php if (!$success_message || $_POST['form_type'] !== 'register'): ?>
                <form method="POST" id="registerForm">
                    <input type="hidden" name="form_type" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="form-group">
                        <label for="reg_username">
                            <i class="fas fa-user"></i> Choose Username
                        </label>
                        <input type="text" id="reg_username" name="username" required 
                               placeholder="Create a unique username"
                               value="<?php echo isset($_POST['username']) && $_POST['form_type'] === 'register' ? htmlspecialchars($_POST['username']) : ''; ?>"
                               autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label for="reg_email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="reg_email" name="email" required 
                               placeholder="Enter your email address"
                               value="<?php echo isset($_POST['email']) && $_POST['form_type'] === 'register' ? htmlspecialchars($_POST['email']) : ''; ?>"
                               autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="reg_password">
                            <i class="fas fa-lock"></i> Create Password
                        </label>
                        <input type="password" id="reg_password" name="password" required 
                               placeholder="Create a strong password"
                               autocomplete="new-password">
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-check-double"></i> Confirm Password
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Re-enter your password"
                               autocomplete="new-password">
                    </div>

                    <button type="submit" class="auth-btn register">
                        <i class="fas fa-user-plus"></i> Create My Account
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$success_message): ?>
                <div class="features">
                    <h4><i class="fas fa-star"></i> Why choose SmartFix?</h4>
                    <ul>
                        <li><i class="fas fa-tools"></i> Professional repair services</li>
                        <li><i class="fas fa-user-check"></i> Qualified technicians</li>
                        <li><i class="fas fa-clock"></i> Quick response time</li>
                        <li><i class="fas fa-shield-alt"></i> Secure online platform</li>
                        <li><i class="fas fa-star"></i> 5-star customer service</li>
                        <li><i class="fas fa-mobile-alt"></i> Mobile-friendly experience</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Admin Form -->
        <div class="form-content <?php echo $current_form === 'admin' ? 'active' : ''; ?>" id="admin-form">
            <div class="admin-form-header">
                <h2><i class="fas fa-shield-alt"></i> Administrator Access</h2>
                <p>Secure Login Portal</p>
            </div>

            <div class="admin-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>RESTRICTED ACCESS</strong><br>
                This is for authorized administrators only. All access attempts are monitored and logged for security purposes.
            </div>

            <form method="POST" id="adminLoginForm">
                <input type="hidden" name="form_type" value="admin">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="admin_username">
                        <i class="fas fa-user-shield"></i> Administrator Username
                    </label>
                    <input type="text" id="admin_username" name="admin_username" required 
                           placeholder="Enter your admin username"
                           value="<?php echo isset($_POST['admin_username']) && $_POST['form_type'] === 'admin' ? htmlspecialchars($_POST['admin_username']) : ''; ?>"
                           autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="admin_password">
                        <i class="fas fa-key"></i> Administrator Password
                    </label>
                    <input type="password" id="admin_password" name="admin_password" required 
                           placeholder="Enter your secure password"
                           autocomplete="current-password">
                </div>

                <button type="submit" class="auth-btn admin">
                    <i class="fas fa-shield-alt"></i> Secure Admin Login
                </button>
            </form>

            <div class="links">
                <a href="admin/admin_register.php"><i class="fas fa-user-plus"></i> Register as Admin</a>
                <a href="admin/admin_recover.php"><i class="fas fa-unlock-alt"></i> Forgot Password?</a>
            </div>
        </div>

        <!-- Loading Animation -->
        <div class="admin-loading" id="adminLoading">
            <div class="admin-spinner"></div>
        </div>
    </div>

    <script>
        function switchForm(formType) {
            // Remove active class from all tabs and forms
            document.querySelectorAll('.form-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to selected tab and form
            event.target.classList.add('active');
            document.getElementById(formType + '-form').classList.add('active');
            
            // Update URL parameter
            const url = new URL(window.location);
            url.searchParams.set('form', formType);
            window.history.pushState({}, '', url);
        }

        // Set active form based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const formType = urlParams.get('form') || 'login';
            
            if (['login', 'register', 'admin'].includes(formType)) {
                document.querySelectorAll('.form-tab').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.form-content').forEach(content => content.classList.remove('active'));
                
                // Only try to activate tab if it exists (not in admin-only mode)
                const tabElement = document.querySelector(`[onclick="switchForm('${formType}')"]`);
                if (tabElement) {
                    tabElement.classList.add('active');
                }
                
                const formElement = document.getElementById(formType + '-form');
                if (formElement) {
                    formElement.classList.add('active');
                }
            }
        });

        // Password strength checker (only if register form exists)
        const regPasswordElement = document.getElementById('reg_password');
        if (regPasswordElement) {
            regPasswordElement.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength++;
            else feedback.push('At least 8 characters');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('Lowercase letter');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('Uppercase letter');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('Number');
            
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback.push('Special character');
            
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 2) {
                strengthText = 'Weak';
                strengthClass = 'strength-weak';
            } else if (strength < 4) {
                strengthText = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">Password strength: ${strengthText}</span>`;
            if (feedback.length > 0) {
                strengthDiv.innerHTML += `<br><small>Missing: ${feedback.join(', ')}</small>`;
            }
            });
        }

        // Password confirmation validation (only if confirm password element exists)
        const confirmPasswordElement = document.getElementById('confirm_password');
        if (confirmPasswordElement) {
            confirmPasswordElement.addEventListener('input', function() {
                const password = document.getElementById('reg_password').value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        }

        // Handle form errors - switch to appropriate tab
        <?php if ($error_message && isset($_POST['form_type'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const formType = '<?php echo $_POST['form_type']; ?>';
                if (['login', 'register', 'admin'].includes(formType)) {
                    document.querySelectorAll('.form-tab').forEach(tab => tab.classList.remove('active'));
                    document.querySelectorAll('.form-content').forEach(content => content.classList.remove('active'));
                    
                    // Only try to activate tab if it exists (not in admin-only mode)
                    const tabElement = document.querySelector(`[onclick="switchForm('${formType}')"]`);
                    if (tabElement) {
                        tabElement.classList.add('active');
                    }
                    
                    const formElement = document.getElementById(formType + '-form');
                    if (formElement) {
                        formElement.classList.add('active');
                    }
                }
            });
        <?php endif; ?>

        // Professional Admin Form Enhancements
        document.addEventListener('DOMContentLoaded', function() {
            const adminForm = document.getElementById('adminLoginForm');
            const adminLoading = document.getElementById('adminLoading');
            const adminUsernameInput = document.getElementById('admin_username');
            const adminPasswordInput = document.getElementById('admin_password');

            // Admin form submission with loading animation
            if (adminForm) {
                adminForm.addEventListener('submit', function(e) {
                    const username = adminUsernameInput.value.trim();
                    const password = adminPasswordInput.value.trim();

                    if (username && password) {
                        adminLoading.classList.add('active');
                        
                        // Add slight delay for better UX (remove if not needed)
                        setTimeout(function() {
                            adminForm.submit();
                        }, 500);
                        
                        e.preventDefault();
                        return false;
                    }
                });

                // Enhanced admin input validation
                adminUsernameInput.addEventListener('input', function() {
                    validateAdminInput(this);
                });

                adminPasswordInput.addEventListener('input', function() {
                    validateAdminInput(this);
                });

                function validateAdminInput(input) {
                    const value = input.value.trim();
                    
                    if (value.length > 0) {
                        input.style.borderColor = '#28a745';
                        input.style.boxShadow = '0 0 10px rgba(40, 167, 69, 0.3)';
                    } else {
                        input.style.borderColor = 'transparent';
                        input.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                    }
                }

                // Admin security features
                let adminAttempts = 0;
                const maxAttempts = 3;

                adminForm.addEventListener('submit', function(e) {
                    adminAttempts++;
                    
                    if (adminAttempts >= maxAttempts) {
                        console.warn('Multiple admin login attempts detected');
                    }
                });

                // Admin form focus effects
                [adminUsernameInput, adminPasswordInput].forEach(input => {
                    input.addEventListener('focus', function() {
                        this.closest('.form-group').style.transform = 'scale(1.02)';
                        this.closest('.form-group').style.transition = 'transform 0.3s ease';
                    });

                    input.addEventListener('blur', function() {
                        this.closest('.form-group').style.transform = 'scale(1)';
                    });
                });
            }

            // Professional User Form Enhancements
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const regUsernameInput = document.getElementById('reg_username');
            const regEmailInput = document.getElementById('reg_email');
            const regPasswordInput = document.getElementById('reg_password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            // Enhanced user input validation
            function validateUserInput(input) {
                const value = input.value.trim();
                
                if (input.hasAttribute('required') && value.length > 0) {
                    if (input.type === 'email') {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (emailRegex.test(value)) {
                            input.style.borderColor = '#059669';
                            input.style.boxShadow = '0 0 10px rgba(5, 150, 105, 0.3)';
                        } else {
                            input.style.borderColor = '#dc3545';
                            input.style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
                        }
                    } else {
                        input.style.borderColor = '#059669';
                        input.style.boxShadow = '0 0 10px rgba(5, 150, 105, 0.3)';
                    }
                } else if (value.length === 0) {
                    input.style.borderColor = 'transparent';
                    input.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                }
            }

            // Professional user form focus effects
            const userInputs = [usernameInput, passwordInput, regUsernameInput, regEmailInput, regPasswordInput, confirmPasswordInput];
            userInputs.forEach(input => {
                if (input) {
                    input.addEventListener('input', function() {
                        validateUserInput(this);
                    });

                    input.addEventListener('focus', function() {
                        this.closest('.form-group').style.transform = 'scale(1.02)';
                        this.closest('.form-group').style.transition = 'transform 0.3s ease';
                    });

                    input.addEventListener('blur', function() {
                        this.closest('.form-group').style.transform = 'scale(1)';
                    });
                }
            });

            // Enhanced login form submission
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const username = usernameInput.value.trim();
                    const password = passwordInput.value.trim();

                    if (username && password) {
                        // Add loading state to button
                        const submitBtn = this.querySelector('.auth-btn');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                        submitBtn.disabled = true;

                        // Reset after delay if form doesn't submit
                        setTimeout(function() {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            }

            // Enhanced register form submission
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const username = regUsernameInput.value.trim();
                    const email = regEmailInput.value.trim();
                    const password = regPasswordInput.value.trim();
                    const confirmPassword = confirmPasswordInput.value.trim();

                    if (username && email && password && confirmPassword) {
                        if (password !== confirmPassword) {
                            e.preventDefault();
                            confirmPasswordInput.style.borderColor = '#dc3545';
                            confirmPasswordInput.focus();
                            return false;
                        }

                        // Add loading state to button
                        const submitBtn = this.querySelector('.auth-btn');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                        submitBtn.disabled = true;

                        // Reset after delay if form doesn't submit
                        setTimeout(function() {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            }

            // Professional keyboard shortcuts for user
            document.addEventListener('keydown', function(e) {
                // Ctrl + Alt + A for admin access (Easter egg)
                if (e.ctrlKey && e.altKey && e.key === 'a') {
                    const adminForm = document.getElementById('admin-form');
                    if (adminForm && !adminForm.classList.contains('active')) {
                        window.location.href = '?form=admin';
                    }
                }

                // ESC to close loading animation
                if (e.key === 'Escape' && adminLoading && adminLoading.classList.contains('active')) {
                    adminLoading.classList.remove('active');
                }

                // Tab enhancement for better accessibility
                if (e.key === 'Tab') {
                    const activeElement = document.activeElement;
                    if (activeElement && activeElement.classList.contains('auth-btn')) {
                        activeElement.style.boxShadow = '0 0 0 3px rgba(6, 182, 212, 0.3)';
                    }
                }
            });

            // Auto-hide loading on page load completion
            window.addEventListener('load', function() {
                if (adminLoading && adminLoading.classList.contains('active')) {
                    setTimeout(function() {
                        adminLoading.classList.remove('active');
                    }, 1000);
                }
            });

            // Professional welcome animation for user forms
            function animateFormEntry() {
                const activeForm = document.querySelector('.form-content.active');
                if (activeForm && !activeForm.classList.contains('admin-form')) {
                    activeForm.style.opacity = '0';
                    activeForm.style.transform = 'translateY(20px)';
                    
                    setTimeout(function() {
                        activeForm.style.transition = 'all 0.5s ease';
                        activeForm.style.opacity = '1';
                        activeForm.style.transform = 'translateY(0)';
                    }, 100);
                }
            }

            // Trigger welcome animation
            animateFormEntry();
        });
    </script>
</body>
</html>