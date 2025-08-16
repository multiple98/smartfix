<?php
session_start();
include('../includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth.php?form=login');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: ../auth.php?form=login');
        exit;
    }
} catch (PDOException $e) {
    $error_message = "Error loading user data.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $bio = trim($_POST['bio']);
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $error_message = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if email is already used by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->fetch()) {
                $error_message = "This email is already registered by another user.";
            } else {
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET full_name = ?, email = ?, phone = ?, address = ?, bio = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $stmt->execute([$full_name, $email, $phone, $address, $bio, $user_id]);
                
                // Update session email if changed
                if ($_SESSION['email'] !== $email) {
                    $_SESSION['email'] = $email;
                }
                
                $success_message = "Your profile has been updated successfully!";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile. Please try again.";
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - SmartFix</title>
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
            color: #333;
            line-height: 1.6;
        }

        /* Professional Header */
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-welcome h2 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .user-welcome p {
            font-size: 14px;
            opacity: 0.9;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 0 20px;
        }

        /* Professional Form Container */
        .profile-container {
            background: linear-gradient(135deg, #ffffff 0%, #f8fdff 100%);
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.15);
            padding: 60px 50px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .profile-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        }

        .profile-container::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .profile-header h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .profile-header p {
            color: #666;
            font-size: 16px;
        }

        /* Profile Avatar */
        .profile-avatar {
            text-align: center;
            margin-bottom: 40px;
        }

        .avatar-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .avatar-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.4);
        }

        .avatar-circle i {
            font-size: 48px;
            color: white;
        }

        .avatar-name {
            font-size: 24px;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 5px;
        }

        .avatar-email {
            color: #667eea;
            font-size: 16px;
        }

        /* Professional Form Elements */
        .form-section {
            margin-bottom: 40px;
        }

        .form-section h3 {
            color: #1e3c72;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-section h3 i {
            color: #667eea;
            font-size: 18px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-col {
            flex: 1;
            min-width: 250px;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: #1e3c72;
            font-size: 16px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .form-group label i {
            margin-right: 10px;
            width: 20px;
            color: #667eea;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 18px 24px;
            border: 2px solid transparent;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: linear-gradient(135deg, #f8fdff 0%, #ffffff 100%);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }

        .form-control:focus {
            border-color: #667eea;
            outline: none;
            background: white;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2), inset 0 2px 4px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }

        .form-control:valid {
            border-color: #28a745;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        /* Professional Button */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 18px 40px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            position: relative;
            overflow: hidden;
            min-width: 200px;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
            background: linear-gradient(135deg, #7b8bf0 0%, #8a63c7 100%);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-primary i {
            margin-right: 12px;
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .btn-primary:hover i {
            transform: translateX(-3px);
        }

        /* Professional Alerts */
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            font-size: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .alert i {
            margin-right: 15px;
            font-size: 20px;
            margin-top: 2px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-success::before {
            background: #28a745;
        }

        .alert-success i {
            color: #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .alert-danger::before {
            background: #dc3545;
        }

        .alert-danger i {
            color: #dc3545;
        }

        /* Form Actions */
        .form-actions {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
        }

        @media (max-width: 768px) {
            .profile-container {
                padding: 40px 30px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1><i class="fas fa-tools"></i> SmartFix</h1>
            </div>
            <div class="user-info">
                <div class="user-welcome">
                    <h2>Edit Profile</h2>
                    <p>Update your account information</p>
                </div>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Keep your information up to date for the best service experience</p>
            </div>

            <div class="profile-avatar">
                <div class="avatar-circle">
                    <i class="fas fa-user"></i>
                </div>
                <div class="avatar-name"><?php echo htmlspecialchars($user['full_name'] ?: $user['name']); ?></div>
                <div class="avatar-email"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $success_message; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?php echo $error_message; ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-section">
                    <h3><i class="fas fa-user-circle"></i>Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="full_name"><i class="fas fa-id-card"></i>Full Name *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?: $user['name']); ?>" 
                                       required placeholder="Enter your full name">
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i>Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required placeholder="your.email@example.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone"><i class="fas fa-phone"></i>Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               placeholder="+260 XXX XXX XXX">
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i>Contact Details</h3>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-home"></i>Address</label>
                        <textarea id="address" name="address" class="form-control" 
                                  placeholder="Enter your full address including city and district..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i>Additional Information</h3>
                    
                    <div class="form-group">
                        <label for="bio"><i class="fas fa-user-tag"></i>Bio / About Me</label>
                        <textarea id="bio" name="bio" class="form-control" 
                                  placeholder="Tell us a bit about yourself (optional)..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>