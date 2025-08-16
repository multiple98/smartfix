<?php
/**
 * Test Service Request Form
 * Simple form to test if service request submissions are working
 */
session_start();
include('includes/db.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $service_type = trim($_POST['service_type']);
    $description = trim($_POST['description']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($description)) {
        $message = "Please fill in all required fields.";
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = 'error';
    } else {
        try {
            // Check/create table first
            $checkTable = $pdo->query("SHOW TABLES LIKE 'service_requests'");
            if ($checkTable->rowCount() == 0) {
                $createTable = "CREATE TABLE service_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reference_number VARCHAR(20) UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    service_type VARCHAR(50) NOT NULL,
                    service_option VARCHAR(100),
                    description TEXT NOT NULL,
                    address TEXT,
                    preferred_date DATE,
                    preferred_time VARCHAR(20),
                    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
                    technician_id INT,
                    user_id INT,
                    notes TEXT,
                    completed_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                $pdo->exec($createTable);
            }
            
            // Insert the service request
            $query = "INSERT INTO service_requests (name, email, phone, service_type, description, status, created_at) 
                      VALUES (:name, :email, :phone, :service_type, :description, 'pending', NOW())";
            $stmt = $pdo->prepare($query);
            $success = $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'service_type' => $service_type,
                'description' => $description
            ]);
            
            if ($success) {
                $request_id = $pdo->lastInsertId();
                $reference_number = 'SF' . str_pad($request_id, 6, '0', STR_PAD_LEFT);
                
                // Update reference number
                try {
                    $updateRef = $pdo->prepare("UPDATE service_requests SET reference_number = :ref WHERE id = :id");
                    $updateRef->execute(['ref' => $reference_number, 'id' => $request_id]);
                } catch (Exception $e) {
                    // Reference column might not exist, that's OK
                }
                
                $message = "✅ SUCCESS! Test service request submitted successfully.<br>
                          <strong>Reference Number:</strong> $reference_number<br>
                          <strong>Service Type:</strong> $service_type<br>
                          <strong>Customer:</strong> $name ($email)";
                $messageType = 'success';
                
                // Clear form
                $name = $email = $phone = $service_type = $description = '';
                
            } else {
                throw new PDOException("Insert failed");
            }
            
        } catch (PDOException $e) {
            $message = "❌ Database Error: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Service Request Form - SmartFix</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #007BFF;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-style: italic;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #007BFF;
        }
        
        .btn {
            background-color: #007BFF;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .instructions {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 4px;
            border-left: 4px solid #007BFF;
            margin-bottom: 30px;
        }
        
        .instructions h3 {
            margin-top: 0;
            color: #007BFF;
        }
        
        .links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .links a {
            display: inline-block;
            margin: 0 10px;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .links a:hover {
            background-color: #545b62;
        }
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Service Request Form</h1>
        <p class="subtitle">Use this form to test if service request submissions are working correctly</p>
        
        <div class="instructions">
            <h3>📝 Instructions:</h3>
            <ul>
                <li>Fill out the form below with test data</li>
                <li>Click "Submit Test Request" to test the submission process</li>
                <li>The system will create the database table if it doesn't exist</li>
                <li>You should see a success message with a reference number if everything works</li>
                <li>Check the admin panel to verify the request was recorded</li>
            </ul>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? 'Test Customer'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? 'test@example.com'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number <span class="required">*</span></label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? '+250123456789'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="service_type">Service Type <span class="required">*</span></label>
                <select id="service_type" name="service_type" required>
                    <option value="">Select Service Type</option>
                    <option value="phone" <?php echo ($service_type ?? '') === 'phone' ? 'selected' : ''; ?>>Phone Repair</option>
                    <option value="computer" <?php echo ($service_type ?? '') === 'computer' ? 'selected' : ''; ?>>Computer Repair</option>
                    <option value="car" <?php echo ($service_type ?? '') === 'car' ? 'selected' : ''; ?>>Vehicle Repair</option>
                    <option value="plumber" <?php echo ($service_type ?? '') === 'plumber' ? 'selected' : ''; ?>>Plumbing</option>
                    <option value="electrician" <?php echo ($service_type ?? '') === 'electrician' ? 'selected' : ''; ?>>Electrical</option>
                    <option value="house" <?php echo ($service_type ?? '') === 'house' ? 'selected' : ''; ?>>Real Estate</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Problem Description <span class="required">*</span></label>
                <textarea id="description" name="description" rows="4" required placeholder="Describe the issue or service needed..."><?php echo htmlspecialchars($description ?? 'This is a test service request to verify the system is working correctly.'); ?></textarea>
            </div>
            
            <button type="submit" name="test_submit" class="btn">🧪 Submit Test Request</button>
        </form>
        
        <div class="links">
            <a href="services/request_service.php?type=phone" target="_blank">📱 Phone Service Form</a>
            <a href="admin/service_requests.php" target="_blank">🛠️ Admin Panel</a>
            <a href="fix_service_requests_system.php" target="_blank">🔧 Database Repair Tool</a>
            <a href="index.php">🏠 Home</a>
        </div>
    </div>
    
    <script>
        // Auto-focus on first empty field
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select, textarea');
            for (let input of inputs) {
                if (input.value === '' || input.value === 'Select Service Type') {
                    input.focus();
                    break;
                }
            }
        });
    </script>
</body>
</html>