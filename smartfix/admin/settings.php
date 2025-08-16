<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = '';
$error = '';

// Check if system_settings table exists, create if not
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'system_settings'");
    if ($table_check->rowCount() == 0) {
        // Create system_settings table
        $create_table = "CREATE TABLE system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) NOT NULL DEFAULT 'SmartFix',
            contact_email VARCHAR(255) NOT NULL DEFAULT 'admin@smartfix.com',
            contact_phone VARCHAR(50) DEFAULT '+260-97-000-0000',
            address TEXT DEFAULT 'Lusaka, Zambia',
            enable_sms_alert TINYINT(1) DEFAULT 1,
            enable_email_alert TINYINT(1) DEFAULT 1,
            maintenance_mode TINYINT(1) DEFAULT 0,
            max_file_size INT DEFAULT 5242880,
            allowed_file_types VARCHAR(255) DEFAULT 'jpg,jpeg,png,gif,pdf,doc,docx',
            timezone VARCHAR(50) DEFAULT 'Africa/Lusaka',
            currency VARCHAR(10) DEFAULT 'ZMW',
            tax_rate DECIMAL(5,2) DEFAULT 16.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        
        // Insert default settings
        $insert_defaults = "INSERT INTO system_settings (
            company_name, contact_email, contact_phone, address, 
            enable_sms_alert, enable_email_alert
        ) VALUES (
            'SmartFix', 'admin@smartfix.com', '+260-97-000-0000', 
            'Lusaka, Zambia', 1, 1
        )";
        $pdo->exec($insert_defaults);
        
        $success = "System settings table created with default values.";
    }
} catch (PDOException $e) {
    $error = "Error creating system_settings table: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    try {
        $company_name = trim($_POST['company_name']);
        $contact_email = trim($_POST['contact_email']);
        $contact_phone = trim($_POST['contact_phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $enable_sms = isset($_POST['enable_sms']) ? 1 : 0;
        $enable_email = isset($_POST['enable_email']) ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $timezone = $_POST['timezone'] ?? 'Africa/Lusaka';
        $currency = $_POST['currency'] ?? 'ZMW';
        $tax_rate = floatval($_POST['tax_rate'] ?? 16.00);

        // Check what columns exist in the table
        $columns_result = $pdo->query("SHOW COLUMNS FROM system_settings");
        $existing_columns = $columns_result->fetchAll(PDO::FETCH_COLUMN);
        
        // Build update query based on existing columns
        $update_fields = [];
        $update_values = [];
        
        if (in_array('company_name', $existing_columns)) {
            $update_fields[] = "company_name = ?";
            $update_values[] = $company_name;
        }
        if (in_array('contact_email', $existing_columns)) {
            $update_fields[] = "contact_email = ?";
            $update_values[] = $contact_email;
        }
        if (in_array('contact_phone', $existing_columns)) {
            $update_fields[] = "contact_phone = ?";
            $update_values[] = $contact_phone;
        }
        if (in_array('address', $existing_columns)) {
            $update_fields[] = "address = ?";
            $update_values[] = $address;
        }
        if (in_array('enable_sms_alert', $existing_columns)) {
            $update_fields[] = "enable_sms_alert = ?";
            $update_values[] = $enable_sms;
        }
        if (in_array('enable_email_alert', $existing_columns)) {
            $update_fields[] = "enable_email_alert = ?";
            $update_values[] = $enable_email;
        }
        if (in_array('maintenance_mode', $existing_columns)) {
            $update_fields[] = "maintenance_mode = ?";
            $update_values[] = $maintenance_mode;
        }
        if (in_array('timezone', $existing_columns)) {
            $update_fields[] = "timezone = ?";
            $update_values[] = $timezone;
        }
        if (in_array('currency', $existing_columns)) {
            $update_fields[] = "currency = ?";
            $update_values[] = $currency;
        }
        if (in_array('tax_rate', $existing_columns)) {
            $update_fields[] = "tax_rate = ?";
            $update_values[] = $tax_rate;
        }
        
        if (!empty($update_fields)) {
            $query = "UPDATE system_settings SET " . implode(', ', $update_fields) . " WHERE id = 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute($update_values);
            $success = "Settings updated successfully.";
        }
        
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Load current settings
$settings = null;
try {
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings found, create default
    if (!$settings) {
        $insert_defaults = "INSERT INTO system_settings (
            company_name, contact_email, contact_phone, address, 
            enable_sms_alert, enable_email_alert
        ) VALUES (
            'SmartFix', 'admin@smartfix.com', '+260-97-000-0000', 
            'Lusaka, Zambia', 1, 1
        )";
        $pdo->exec($insert_defaults);
        
        // Load again
        $stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Error loading settings: " . $e->getMessage();
    // Set default values if database error
    $settings = [
        'company_name' => 'SmartFix',
        'contact_email' => 'admin@smartfix.com',
        'contact_phone' => '+260-97-000-0000',
        'address' => 'Lusaka, Zambia',
        'enable_sms_alert' => 1,
        'enable_email_alert' => 1,
        'maintenance_mode' => 0,
        'timezone' => 'Africa/Lusaka',
        'currency' => 'ZMW',
        'tax_rate' => 16.00
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Settings</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f8f9fa;
        }

        .header {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 15px 0 5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        textarea {
            min-height: 80px;
            resize: vertical;
        }

        input[type="checkbox"] {
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn {
            margin-top: 20px;
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background: #0069d9;
        }

        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .success {
            color: #155724;
            background: #d4edda;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
        }

        .error {
            color: #721c24;
            background: #f8d7da;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
            color: #007bff;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="header">⚙️ System Settings</div>

<div class="container">
    <h2>Update Platform Settings</h2>

    <?php if (!empty($success)): ?>
        <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="section-title">Company Information</div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? 'SmartFix'); ?>" required>
            </div>
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'admin@smartfix.com'); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+260-97-000-0000'); ?>">
            </div>
            <div class="form-group">
                <label>Currency</label>
                <select name="currency">
                    <option value="ZMW" <?php echo ($settings['currency'] ?? 'ZMW') == 'ZMW' ? 'selected' : ''; ?>>ZMW (Zambian Kwacha)</option>
                    <option value="USD" <?php echo ($settings['currency'] ?? 'ZMW') == 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                    <option value="EUR" <?php echo ($settings['currency'] ?? 'ZMW') == 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Business Address</label>
            <textarea name="address" placeholder="Enter your business address"><?php echo htmlspecialchars($settings['address'] ?? 'Lusaka, Zambia'); ?></textarea>
        </div>

        <div class="section-title">System Configuration</div>

        <div class="form-row">
            <div class="form-group">
                <label>Timezone</label>
                <select name="timezone">
                    <option value="Africa/Lusaka" <?php echo ($settings['timezone'] ?? 'Africa/Lusaka') == 'Africa/Lusaka' ? 'selected' : ''; ?>>Africa/Lusaka</option>
                    <option value="UTC" <?php echo ($settings['timezone'] ?? 'Africa/Lusaka') == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                    <option value="Africa/Johannesburg" <?php echo ($settings['timezone'] ?? 'Africa/Lusaka') == 'Africa/Johannesburg' ? 'selected' : ''; ?>>Africa/Johannesburg</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tax Rate (%)</label>
                <input type="number" name="tax_rate" step="0.01" min="0" max="100" value="<?php echo $settings['tax_rate'] ?? 16.00; ?>">
            </div>
        </div>

        <div class="section-title">Notification Settings</div>

        <div class="checkbox-group">
            <div class="checkbox-item">
                <input type="checkbox" name="enable_sms" id="enable_sms" <?php echo ($settings['enable_sms_alert'] ?? 1) ? 'checked' : ''; ?>>
                <label for="enable_sms">Enable SMS Alerts</label>
            </div>
            <div class="checkbox-item">
                <input type="checkbox" name="enable_email" id="enable_email" <?php echo ($settings['enable_email_alert'] ?? 1) ? 'checked' : ''; ?>>
                <label for="enable_email">Enable Email Alerts</label>
            </div>
            <div class="checkbox-item">
                <input type="checkbox" name="maintenance_mode" id="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                <label for="maintenance_mode">Maintenance Mode</label>
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn">💾 Update Settings</button>
            <a href="admin_dashboard_new.php" class="btn btn-secondary">🔙 Back to Dashboard</a>
        </div>
    </form>

    <div class="section-title">System Information</div>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 20px;">
        <p><strong>Database Status:</strong> <?php echo empty($error) ? '✅ Connected' : '❌ Issues detected'; ?></p>
        <p><strong>Settings Table:</strong> <?php echo $settings ? '✅ Available' : '❌ Missing'; ?></p>
        <p><strong>Last Updated:</strong> <?php echo isset($settings['updated_at']) ? date('Y-m-d H:i:s', strtotime($settings['updated_at'])) : 'Never'; ?></p>
    </div>
</div>

</body>
</html>
