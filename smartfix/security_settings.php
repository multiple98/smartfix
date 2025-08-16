<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/TwoFactorAuth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$twoFA = new TwoFactorAuth($pdo);
$userId = $_SESSION['user_id'];
$message = "";
$messageType = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_2fa'])) {
        $enable2FA = isset($_POST['enable_2fa']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?");
        if ($stmt->execute([$enable2FA, $userId])) {
            $message = $enable2FA ? "Two-factor authentication has been enabled." : "Two-factor authentication has been disabled.";
            $messageType = "success";
        } else {
            $message = "Failed to update 2FA settings.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['remove_device'])) {
        $deviceFingerprint = $_POST['device_fingerprint'];
        if ($twoFA->removeTrustedDevice($userId, $deviceFingerprint)) {
            $message = "Device has been removed from trusted devices.";
            $messageType = "success";
        } else {
            $message = "Failed to remove device.";
            $messageType = "error";
        }
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT name, email, two_factor_enabled FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get trusted devices
$trustedDevices = $twoFA->getUserTrustedDevices($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .section h2 {
            color: #343a40;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .section p {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #28a745;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .form-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .device-list {
            list-style: none;
            padding: 0;
        }
        .device-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .device-info {
            flex-grow: 1;
        }
        .device-name {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 5px;
        }
        .device-details {
            font-size: 12px;
            color: #6c757d;
        }
        .device-actions {
            display: flex;
            gap: 10px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-enabled {
            background-color: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Security Settings</h1>
        </div>
        
        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Two-Factor Authentication Section -->
            <div class="section">
                <h2><i class="fas fa-mobile-alt"></i> Two-Factor Authentication</h2>
                <p>Add an extra layer of security to your account by requiring a verification code when logging in from new devices.</p>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_2fa" <?php echo ($user['two_factor_enabled'] ?? true) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div>
                            <strong>Enable Two-Factor Authentication</strong>
                            <div class="status-badge <?php echo ($user['two_factor_enabled'] ?? true) ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo ($user['two_factor_enabled'] ?? true) ? 'Enabled' : 'Disabled'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="toggle_2fa" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
            
            <!-- Trusted Devices Section -->
            <div class="section">
                <h2><i class="fas fa-devices"></i> Trusted Devices</h2>
                <p>These devices won't require a verification code when you log in. You can remove any device you no longer trust.</p>
                
                <?php if (empty($trustedDevices)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <i class="fas fa-mobile-alt" style="font-size: 48px; margin-bottom: 15px;"></i>
                        <p>No trusted devices found.</p>
                        <p>When you log in from a new device and choose to trust it, it will appear here.</p>
                    </div>
                <?php else: ?>
                    <ul class="device-list">
                        <?php foreach ($trustedDevices as $device): ?>
                            <li class="device-item">
                                <div class="device-info">
                                    <div class="device-name">
                                        <i class="fas fa-<?php echo strpos($device['device_name'], 'Mobile') !== false || strpos($device['device_name'], 'iPhone') !== false || strpos($device['device_name'], 'Android') !== false ? 'mobile-alt' : 'desktop'; ?>"></i>
                                        <?php echo htmlspecialchars($device['device_name']); ?>
                                    </div>
                                    <div class="device-details">
                                        IP: <?php echo htmlspecialchars($device['ip_address']); ?> | 
                                        Added: <?php echo date('M j, Y', strtotime($device['created_at'])); ?> | 
                                        Last used: <?php echo date('M j, Y g:i A', strtotime($device['last_used'])); ?>
                                    </div>
                                </div>
                                <div class="device-actions">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="device_fingerprint" value="<?php echo htmlspecialchars($twoFA->createDeviceFingerprint()); ?>">
                                        <button type="submit" name="remove_device" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to remove this trusted device?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- Account Information Section -->
            <div class="section">
                <h2><i class="fas fa-user"></i> Account Information</h2>
                <p>Your account details used for security verification.</p>
                
                <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div style="margin-bottom: 15px;">
                        <strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?>
                    </div>
                    <div>
                        <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                        <span style="color: #28a745; margin-left: 10px;">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    </div>
                </div>
            </div>
            
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>