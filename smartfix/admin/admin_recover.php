<?php
session_start();
include('../includes/db.php');

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  header("Location: admin_dashboard.php");
  exit();
}

// Define security questions (same as in admin_register.php)
$security_questions = [
    "What was the name of your first pet?",
    "In which city were you born?",
    "What is your mother's maiden name?",
    "What was your childhood nickname?",
    "What is the name of your favorite childhood teacher?"
];

$error_message = "";
$success_message = "";
$show_username_form = true;
$show_security_form = false;
$show_reset_form = false;
$username = "";
$security_question = "";
$question_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Username submission
    if (isset($_POST['find_account'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        
        if (empty($username)) {
            $error_message = "Please enter your username";
        } else {
            // Check if admins table exists first
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
            if (mysqli_num_rows($table_check) == 0) {
                $error_message = "Admin system not initialized. Please contact system administrator.";
            } else {
                // Check if username exists
                $query = "SELECT * FROM admins WHERE username = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
            
                if (mysqli_num_rows($result) > 0) {
                    $admin = mysqli_fetch_assoc($result);
                    $question_id = $admin['security_question'];
                    $security_question = $security_questions[$question_id];
                    
                    // Move to security question step
                    $show_username_form = false;
                    $show_security_form = true;
                    
                    // Store username in session for next step
                    $_SESSION['recover_username'] = $username;
                } else {
                    $error_message = "Username not found";
                }
            }
        }
    }
    
    // Step 2: Security question answer
    elseif (isset($_POST['verify_answer'])) {
        $security_answer = mysqli_real_escape_string($conn, $_POST['security_answer']);
        $username = $_SESSION['recover_username'] ?? '';
        
        if (empty($security_answer) || empty($username)) {
            $error_message = "Please provide your security answer";
            $show_username_form = true;
        } else {
            // Verify security answer
            $query = "SELECT * FROM admins WHERE username = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $admin = mysqli_fetch_assoc($result);
                
                // Case-insensitive comparison
                if (strtolower($security_answer) === strtolower($admin['security_answer'])) {
                    // Move to password reset step
                    $show_username_form = false;
                    $show_security_form = false;
                    $show_reset_form = true;
                    
                    // Generate a token and store it in session
                    $_SESSION['reset_token'] = bin2hex(random_bytes(32));
                    $_SESSION['reset_token_time'] = time();
                } else {
                    $error_message = "Incorrect security answer";
                    $question_id = $admin['security_question'];
                    $security_question = $security_questions[$question_id];
                    $show_username_form = false;
                    $show_security_form = true;
                }
            } else {
                $error_message = "User not found. Please try again.";
                $show_username_form = true;
                $show_security_form = false;
            }
        }
    }
    
    // Step 3: Password reset
    elseif (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $token = $_POST['token'];
        $username = $_SESSION['recover_username'] ?? '';
        
        // Verify token
        if (empty($token) || empty($_SESSION['reset_token']) || $token !== $_SESSION['reset_token']) {
            $error_message = "Invalid or expired token. Please start over.";
            $show_username_form = true;
            $show_security_form = false;
            $show_reset_form = false;
            unset($_SESSION['recover_username']);
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_token_time']);
        } 
        // Check if token is expired (30 minutes)
        elseif (time() - $_SESSION['reset_token_time'] > 1800) {
            $error_message = "Your reset token has expired. Please start over.";
            $show_username_form = true;
            $show_security_form = false;
            $show_reset_form = false;
            unset($_SESSION['recover_username']);
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_token_time']);
        }
        // Validate passwords
        elseif (empty($new_password) || empty($confirm_password)) {
            $error_message = "Please enter and confirm your new password";
            $show_reset_form = true;
        } 
        elseif ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match";
            $show_reset_form = true;
        } 
        elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long";
            $show_reset_form = true;
        } 
        else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE admins SET password = ? WHERE username = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $username);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Password has been reset successfully! You can now log in with your new password.";
                $show_username_form = false;
                $show_security_form = false;
                $show_reset_form = false;
                
                // Clear recovery session data
                unset($_SESSION['recover_username']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_token_time']);
                
                // Redirect to login page after a delay
                header("Refresh: 3; URL=admin_login.php");
            } else {
                $error_message = "Failed to reset password: " . mysqli_error($conn);
                $show_reset_form = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartFix Admin Account Recovery</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f0f2f5;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .recover-container {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 30px;
      width: 100%;
      max-width: 450px;
    }
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    .header h1 {
      color: #343a40;
      margin-bottom: 10px;
    }
    .header p {
      color: #6c757d;
      margin: 0;
    }
    .message {
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 20px;
      text-align: center;
    }
    .error-message {
      background-color: #f8d7da;
      color: #721c24;
    }
    .success-message {
      background-color: #d4edda;
      color: #155724;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #343a40;
      font-weight: 500;
    }
    .form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ced4da;
      border-radius: 5px;
      font-size: 16px;
      box-sizing: border-box;
    }
    .form-group input:focus {
      border-color: #007bff;
      outline: none;
    }
    .submit-btn {
      width: 100%;
      padding: 12px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .submit-btn:hover {
      background-color: #0069d9;
    }
    .links {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }
    .links a {
      color: #007bff;
      text-decoration: none;
    }
    .links a:hover {
      text-decoration: underline;
    }
    .step-indicator {
      display: flex;
      justify-content: center;
      margin-bottom: 20px;
    }
    .step {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: #e9ecef;
      color: #6c757d;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 5px;
      font-weight: bold;
    }
    .step.active {
      background-color: #007bff;
      color: white;
    }
    .step-line {
      height: 3px;
      width: 50px;
      background-color: #e9ecef;
      margin-top: 15px;
    }
    .step-line.active {
      background-color: #007bff;
    }
    .password-requirements {
      font-size: 0.85em;
      color: #6c757d;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="recover-container">
    <div class="header">
      <h1>Account Recovery</h1>
      <p>Reset your admin password</p>
    </div>
    
    <div class="step-indicator">
      <div class="step <?php echo $show_username_form ? 'active' : ''; ?>">1</div>
      <div class="step-line <?php echo $show_security_form || $show_reset_form ? 'active' : ''; ?>"></div>
      <div class="step <?php echo $show_security_form ? 'active' : ''; ?>">2</div>
      <div class="step-line <?php echo $show_reset_form ? 'active' : ''; ?>"></div>
      <div class="step <?php echo $show_reset_form ? 'active' : ''; ?>">3</div>
    </div>
    
    <?php if (!empty($error_message)): ?>
      <div class="message error-message">
        <?php echo $error_message; ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
      <div class="message success-message">
        <?php echo $success_message; ?>
      </div>
    <?php endif; ?>
    
    <?php if ($show_username_form): ?>
      <!-- Step 1: Enter Username -->
      <form method="POST" action="admin_recover.php">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" required value="<?php echo htmlspecialchars($username); ?>">
        </div>
        
        <button type="submit" name="find_account" class="submit-btn">Find Account</button>
      </form>
    <?php endif; ?>
    
    <?php if ($show_security_form): ?>
      <!-- Step 2: Answer Security Question -->
      <form method="POST" action="admin_recover.php">
        <div class="form-group">
          <label for="security_question">Security Question</label>
          <input type="text" value="<?php echo htmlspecialchars($security_question); ?>" readonly>
        </div>
        
        <div class="form-group">
          <label for="security_answer">Your Answer</label>
          <input type="text" name="security_answer" id="security_answer" required>
        </div>
        
        <button type="submit" name="verify_answer" class="submit-btn">Verify Answer</button>
      </form>
    <?php endif; ?>
    
    <?php if ($show_reset_form): ?>
      <!-- Step 3: Reset Password -->
      <form method="POST" action="admin_recover.php">
        <input type="hidden" name="token" value="<?php echo $_SESSION['reset_token']; ?>">
        
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" required>
          <div class="password-requirements">Password must be at least 8 characters long</div>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        
        <button type="submit" name="reset_password" class="submit-btn">Reset Password</button>
      </form>
    <?php endif; ?>
    
    <div class="links">
      <a href="admin_login.php">Back to Login</a>
      <a href="../index.php">Home</a>
    </div>
  </div>
</body>
</html>