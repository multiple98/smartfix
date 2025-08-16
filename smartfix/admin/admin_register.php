<?php
session_start();
include('../includes/db.php');

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  header("Location: admin_dashboard.php");
  exit();
}

// Define security questions
$security_questions = [
    "What was the name of your first pet?",
    "In which city were you born?",
    "What is your mother's maiden name?",
    "What was your childhood nickname?",
    "What is the name of your favorite childhood teacher?"
];

$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $security_question = (int)$_POST['security_question'];
    $security_answer = mysqli_real_escape_string($conn, $_POST['security_answer']);
    $admin_code = mysqli_real_escape_string($conn, $_POST['admin_code']);
    
    // Validate form data
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || 
        empty($full_name) || empty($security_answer) || empty($admin_code)) {
        $error_message = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } elseif ($admin_code !== "SMARTFIX2023") { // This should be a secure code known only to administrators
        $error_message = "Invalid admin registration code";
    } else {
        // Check if admins table exists, if not create it
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
        if (mysqli_num_rows($table_check) == 0) {
            $create_table = "CREATE TABLE admins (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                security_question INT(11) NOT NULL,
                security_answer VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if (!mysqli_query($conn, $create_table)) {
                $error_message = "Failed to create admins table: " . mysqli_error($conn);
            }
        }
        
        // Check if username already exists (only if table creation was successful)
        if (empty($error_message)) {
            $check_query = "SELECT * FROM admins WHERE username = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $username);
            mysqli_stmt_execute($check_stmt);
            $result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error_message = "Username already exists";
            } else {
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new admin
            $insert_query = "INSERT INTO admins (username, password, email, full_name, security_question, security_answer) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ssssis", $username, $hashed_password, $email, $full_name, $security_question, $security_answer);
            
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_message = "Registration successful! You can now log in.";
                    
                    // Automatically log in the new admin
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['user_name'] = $username;
                    
                    // Redirect to dashboard after a short delay
                    header("Refresh: 3; URL=admin_dashboard.php");
                } else {
                    $error_message = "Registration failed: " . mysqli_error($conn);
                }
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
  <title>SmartFix Admin Registration - Secure Registration Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #0c0c0c 0%, #1a1a2e 25%, #16213e 50%, #0f3460 75%, #1a1a2e 100%);
      background-attachment: fixed;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 50%, rgba(255, 193, 7, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(220, 53, 69, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255, 193, 7, 0.05) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    .register-container {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      border: 2px solid #ffc107;
      border-radius: 20px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 30px rgba(255, 193, 7, 0.2);
      padding: 40px;
      width: 100%;
      max-width: 580px;
      position: relative;
      backdrop-filter: blur(20px);
      z-index: 1;
    }

    .register-container::before {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      background: linear-gradient(45deg, #ffc107, #dc3545, #ffc107, #dc3545);
      border-radius: 22px;
      z-index: -1;
      animation: borderGlow 4s ease-in-out infinite alternate;
    }

    @keyframes borderGlow {
      0% { opacity: 0.7; }
      100% { opacity: 1; }
    }

    /* Professional Security Badge */
    .register-container::after {
      content: '\f21b';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      top: -15px;
      right: -15px;
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
      z-index: 10;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    .logo {
      text-align: center;
      margin-bottom: 30px;
    }

    .logo i {
      font-size: 50px;
      color: #ffc107;
      margin-bottom: 15px;
      text-shadow: 0 0 20px rgba(255, 193, 7, 0.5);
    }

    .logo h1 {
      background: linear-gradient(135deg, #ffc107, #ffecb3);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 8px;
      text-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
    }

    .logo p {
      color: rgba(255, 193, 7, 0.8);
      font-size: 14px;
      font-weight: 500;
    }

    .admin-form-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .admin-form-header h2 {
      color: #ffc107;
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .admin-form-header p {
      color: rgba(255, 193, 7, 0.8);
      font-size: 15px;
      margin: 0;
    }

    .admin-warning {
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 30px;
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

    .message {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      text-align: center;
      font-weight: 500;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .error-message {
      background: linear-gradient(135deg, #dc3545, #c82333);
      color: white;
      border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .success-message {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      border: 1px solid rgba(40, 167, 69, 0.3);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 25px;
    }

    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-group label {
      color: #ffc107;
      font-weight: 600;
      font-size: 15px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .form-group label i {
      margin-right: 12px;
      width: 20px;
      font-size: 16px;
      text-align: center;
    }

    .form-group input, .form-group select {
      background: rgba(255, 255, 255, 0.95);
      border: 2px solid transparent;
      border-radius: 12px;
      padding: 15px 20px;
      font-size: 16px;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      width: 100%;
      box-sizing: border-box;
    }

    .form-group input:focus, .form-group select:focus {
      outline: none;
      border-color: #ffc107;
      box-shadow: 0 0 20px rgba(255, 193, 7, 0.4), 0 5px 15px rgba(0,0,0,0.2);
      transform: translateY(-2px);
    }

    .form-group input:valid, .form-group select:valid {
      border-color: #28a745;
    }

    .password-requirements, .admin-code-info {
      font-size: 12px;
      color: rgba(255, 193, 7, 0.7);
      margin-top: 8px;
      padding-left: 32px;
    }

    .password-strength {
      margin-top: 8px;
      padding-left: 32px;
      font-size: 12px;
    }

    .strength-weak { color: #dc3545; }
    .strength-medium { color: #ffc107; }
    .strength-strong { color: #28a745; }

    .submit-btn {
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
      width: 100%;
      cursor: pointer;
    }

    .submit-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.6s;
    }

    .submit-btn:hover::before {
      left: 100%;
    }

    .submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(255, 193, 7, 0.6), 0 8px 25px rgba(0,0,0,0.3);
      background: linear-gradient(135deg, #ffd54f 0%, #ffc107 50%, #e6ac00 100%);
    }

    .submit-btn:active {
      transform: translateY(-1px);
    }

    .submit-btn i {
      margin-right: 12px;
      font-size: 20px;
    }

    .links {
      margin-top: 30px;
      text-align: center;
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 15px;
    }

    .links a {
      color: #ffc107;
      text-decoration: none;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 25px;
      transition: all 0.3s ease;
      border: 1px solid transparent;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .links a:hover {
      background: rgba(255, 193, 7, 0.1);
      border-color: #ffc107;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2);
    }

    /* Loading Animation */
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

    /* Mobile Responsive */
    @media (max-width: 768px) {
      body {
        padding: 10px;
      }

      .register-container {
        padding: 30px 20px;
        margin: 10px 0;
        max-width: calc(100% - 20px);
      }

      .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .logo h1 {
        font-size: 24px;
      }

      .admin-form-header h2 {
        font-size: 22px;
      }

      .form-group input, .form-group select {
        font-size: 16px; /* Prevent zoom on iOS */
        padding: 12px 15px;
      }

      .submit-btn {
        font-size: 16px;
        padding: 15px 30px;
        letter-spacing: 0.5px;
      }

      .links {
        flex-direction: column;
        text-align: center;
      }

      .register-container::after {
        width: 40px;
        height: 40px;
        font-size: 18px;
        top: -12px;
        right: -12px;
      }
    }

    @media (max-width: 480px) {
      .register-container {
        padding: 25px 15px;
        margin: 5px 0;
      }

      .logo i {
        font-size: 40px;
      }

      .logo h1 {
        font-size: 22px;
      }

      .admin-form-header h2 {
        font-size: 20px;
      }

      .admin-warning {
        padding: 15px;
        font-size: 13px;
      }

      .form-group {
        margin-bottom: 20px;
      }

      .form-group input, .form-group select {
        padding: 10px 12px;
        border-radius: 10px;
      }

      .submit-btn {
        font-size: 14px;
        padding: 12px 25px;
        border-radius: 30px;
      }

      .links a {
        font-size: 12px;
        padding: 6px 12px;
      }
    }
  </style>
</head>
<body>
  <div class="register-container">
    <div class="logo">
      <i class="fas fa-tools"></i>
      <h1>SmartFix</h1>
      <p>Your trusted repair service</p>
    </div>

    <div class="admin-form-header">
      <h2><i class="fas fa-user-shield"></i> Administrator Registration</h2>
      <p>Secure Account Creation Portal</p>
    </div>

    <div class="admin-warning">
      <i class="fas fa-exclamation-triangle"></i>
      <strong>RESTRICTED REGISTRATION</strong><br>
      This registration is for authorized personnel only. Valid admin registration code required. All registration attempts are monitored and logged for security purposes.
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
    
    <form method="POST" action="admin_register.php" id="adminRegisterForm">
      <div class="form-row">
        <div class="form-group">
          <label for="username">
            <i class="fas fa-user-shield"></i> Administrator Username
          </label>
          <input type="text" name="username" id="username" required 
                 placeholder="Choose a unique username"
                 value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                 autocomplete="username">
        </div>
        
        <div class="form-group">
          <label for="full_name">
            <i class="fas fa-id-card"></i> Full Name
          </label>
          <input type="text" name="full_name" id="full_name" required 
                 placeholder="Enter your full name"
                 value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                 autocomplete="name">
        </div>
      </div>
      
      <div class="form-group">
        <label for="email">
          <i class="fas fa-envelope"></i> Administrator Email
        </label>
        <input type="email" name="email" id="email" required 
               placeholder="Enter your professional email address"
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
               autocomplete="email">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="password">
            <i class="fas fa-key"></i> Secure Password
          </label>
          <input type="password" name="password" id="password" required 
                 placeholder="Create a strong password"
                 autocomplete="new-password">
          <div class="password-requirements">Minimum 8 characters with mixed case, numbers, and symbols</div>
          <div class="password-strength" id="passwordStrength"></div>
        </div>
        
        <div class="form-group">
          <label for="confirm_password">
            <i class="fas fa-check-double"></i> Confirm Password
          </label>
          <input type="password" name="confirm_password" id="confirm_password" required 
                 placeholder="Re-enter your password"
                 autocomplete="new-password">
        </div>
      </div>
      
      <div class="form-group">
        <label for="security_question">
          <i class="fas fa-question-circle"></i> Security Question
        </label>
        <select name="security_question" id="security_question" required>
          <option value="">Choose a security question...</option>
          <?php foreach ($security_questions as $key => $question): ?>
            <option value="<?php echo $key; ?>" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == $key) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($question); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="security_answer">
          <i class="fas fa-shield-alt"></i> Security Answer
        </label>
        <input type="text" name="security_answer" id="security_answer" required 
               placeholder="Enter your security answer"
               value="<?php echo isset($_POST['security_answer']) ? htmlspecialchars($_POST['security_answer']) : ''; ?>">
      </div>
      
      <div class="form-group">
        <label for="admin_code">
          <i class="fas fa-key"></i> Admin Registration Code
        </label>
        <input type="password" name="admin_code" id="admin_code" required 
               placeholder="Enter the admin registration code">
        <div class="admin-code-info">
          <i class="fas fa-info-circle"></i> This secure code is provided by the system administrator
        </div>
      </div>
      
      <button type="submit" class="submit-btn">
        <i class="fas fa-user-plus"></i> Create Administrator Account
      </button>
    </form>
    
    <div class="links">
      <a href="../auth.php?form=admin">
        <i class="fas fa-sign-in-alt"></i> Already have an account? Login
      </a>
      <a href="../index.php">
        <i class="fas fa-home"></i> Back to Home
      </a>
    </div>
  </div>

  <!-- Loading Animation -->
  <div class="admin-loading" id="adminLoading">
    <div class="admin-spinner"></div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const adminForm = document.getElementById('adminRegisterForm');
      const adminLoading = document.getElementById('adminLoading');
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirm_password');
      const strengthDiv = document.getElementById('passwordStrength');

      // Password strength checker
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        
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
        
        if (strength < 3) {
          strengthText = 'Weak';
          strengthClass = 'strength-weak';
        } else if (strength < 5) {
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

      // Password confirmation validation
      confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
          this.setCustomValidity('Passwords do not match');
          this.style.borderColor = '#dc3545';
        } else {
          this.setCustomValidity('');
          this.style.borderColor = confirmPassword ? '#28a745' : 'transparent';
        }
      });

      // Enhanced input validation
      const inputs = document.querySelectorAll('input, select');
      inputs.forEach(input => {
        input.addEventListener('input', function() {
          validateInput(this);
        });

        input.addEventListener('focus', function() {
          this.closest('.form-group').style.transform = 'scale(1.02)';
          this.closest('.form-group').style.transition = 'transform 0.3s ease';
        });

        input.addEventListener('blur', function() {
          this.closest('.form-group').style.transform = 'scale(1)';
        });
      });

      function validateInput(input) {
        const value = input.value.trim();
        
        if (input.hasAttribute('required') && value.length > 0) {
          if (input.type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(value)) {
              input.style.borderColor = '#28a745';
              input.style.boxShadow = '0 0 10px rgba(40, 167, 69, 0.3)';
            } else {
              input.style.borderColor = '#dc3545';
              input.style.boxShadow = '0 0 10px rgba(220, 53, 69, 0.3)';
            }
          } else {
            input.style.borderColor = '#28a745';
            input.style.boxShadow = '0 0 10px rgba(40, 167, 69, 0.3)';
          }
        } else if (value.length === 0) {
          input.style.borderColor = 'transparent';
          input.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        }
      }

      // Form submission with loading animation
      adminForm.addEventListener('submit', function(e) {
        const requiredFields = adminForm.querySelectorAll('input[required], select[required]');
        let allValid = true;

        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            allValid = false;
            field.style.borderColor = '#dc3545';
          }
        });

        if (allValid) {
          adminLoading.classList.add('active');
          
          // Add slight delay for better UX
          setTimeout(function() {
            adminForm.submit();
          }, 800);
          
          e.preventDefault();
          return false;
        }
      });

      // Admin code security features
      const adminCodeInput = document.getElementById('admin_code');
      adminCodeInput.addEventListener('paste', function(e) {
        // Log paste attempts for security
        console.warn('Admin code paste attempt detected');
      });

      // Professional keyboard shortcuts
      document.addEventListener('keydown', function(e) {
        // ESC to close loading animation
        if (e.key === 'Escape' && adminLoading.classList.contains('active')) {
          adminLoading.classList.remove('active');
        }
      });

      // Auto-hide loading on page load completion
      window.addEventListener('load', function() {
        if (adminLoading.classList.contains('active')) {
          setTimeout(function() {
            adminLoading.classList.remove('active');
          }, 1000);
        }
      });
    });
  </script>
</body>
</html>