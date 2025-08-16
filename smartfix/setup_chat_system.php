<?php
// Setup Chat System Database Tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$messages = [];
$success = true;

try {
    $messages[] = "🚀 Setting up In-App Chat System";
    $messages[] = "================================";
    
    // 1. Create chat_rooms table
    $messages[] = "\n💬 Creating chat_rooms table...";
    $create_chat_rooms = "
        CREATE TABLE IF NOT EXISTS chat_rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(50) UNIQUE NOT NULL,
            room_type ENUM('service_request', 'general', 'emergency') DEFAULT 'service_request',
            service_request_id INT DEFAULT NULL,
            customer_id INT DEFAULT NULL,
            technician_id INT DEFAULT NULL,
            admin_id INT DEFAULT NULL,
            room_name VARCHAR(255) DEFAULT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_service_request (service_request_id),
            INDEX idx_customer (customer_id),
            INDEX idx_technician (technician_id),
            INDEX idx_active (is_active),
            INDEX idx_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_chat_rooms);
    $messages[] = "✅ chat_rooms table created";
    
    // 2. Create chat_messages table
    $messages[] = "\n💌 Creating chat_messages table...";
    $create_chat_messages = "
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(50) NOT NULL,
            sender_id INT NOT NULL,
            sender_type ENUM('customer', 'technician', 'admin') NOT NULL,
            sender_name VARCHAR(100) NOT NULL,
            message_type ENUM('text', 'image', 'file', 'system') DEFAULT 'text',
            message TEXT NOT NULL,
            file_path VARCHAR(500) DEFAULT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            file_size INT DEFAULT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_room_id (room_id),
            INDEX idx_sender (sender_id, sender_type),
            INDEX idx_sent_at (sent_at),
            INDEX idx_is_read (is_read),
            FOREIGN KEY (room_id) REFERENCES chat_rooms(room_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_chat_messages);
    $messages[] = "✅ chat_messages table created";
    
    // 3. Create chat_participants table (for group chats)
    $messages[] = "\n👥 Creating chat_participants table...";
    $create_chat_participants = "
        CREATE TABLE IF NOT EXISTS chat_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            room_id VARCHAR(50) NOT NULL,
            user_id INT NOT NULL,
            user_type ENUM('customer', 'technician', 'admin') NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_online BOOLEAN DEFAULT FALSE,
            unread_count INT DEFAULT 0,
            INDEX idx_room_user (room_id, user_id, user_type),
            INDEX idx_online (is_online),
            INDEX idx_last_seen (last_seen),
            FOREIGN KEY (room_id) REFERENCES chat_rooms(room_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_chat_participants);
    $messages[] = "✅ chat_participants table created";
    
    // 4. Create chat_files table (for file attachments)
    $messages[] = "\n📎 Creating chat_files table...";
    $create_chat_files = "
        CREATE TABLE IF NOT EXISTS chat_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            uploaded_by INT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_message_id (message_id),
            FOREIGN KEY (message_id) REFERENCES chat_messages(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($create_chat_files);
    $messages[] = "✅ chat_files table created";
    
    // 5. Create uploads/chat directory for file attachments
    $messages[] = "\n📁 Creating chat uploads directory...";
    $chat_upload_dir = 'uploads/chat';
    if (!file_exists($chat_upload_dir)) {
        if (mkdir($chat_upload_dir, 0755, true)) {
            $messages[] = "✅ Chat uploads directory created";
        } else {
            $messages[] = "⚠️ Could not create chat uploads directory";
        }
    } else {
        $messages[] = "✅ Chat uploads directory already exists";
    }
    
    // 6. Add sample chat rooms for existing service requests
    $messages[] = "\n🔄 Creating chat rooms for existing service requests...";
    
    try {
        // Get existing service requests
        $service_requests = $pdo->query("SELECT id, name, email FROM service_requests LIMIT 10")->fetchAll();
        
        foreach ($service_requests as $request) {
            $room_id = 'service_' . $request['id'];
            
            // Check if room already exists
            $existing_room = $pdo->prepare("SELECT id FROM chat_rooms WHERE room_id = ?");
            $existing_room->execute([$room_id]);
            
            if (!$existing_room->fetch()) {
                // Create chat room
                $create_room = $pdo->prepare("
                    INSERT INTO chat_rooms (room_id, room_type, service_request_id, room_name) 
                    VALUES (?, 'service_request', ?, ?)
                ");
                $create_room->execute([
                    $room_id,
                    $request['id'],
                    "Service Request #{$request['id']} - {$request['name']}"
                ]);
                
                // Add customer as participant
                $add_participant = $pdo->prepare("
                    INSERT INTO chat_participants (room_id, user_id, user_type, user_name) 
                    VALUES (?, ?, 'customer', ?)
                ");
                $add_participant->execute([$room_id, $request['id'], $request['name']]);
                
                // Add system welcome message
                $welcome_msg = $pdo->prepare("
                    INSERT INTO chat_messages (room_id, sender_id, sender_type, sender_name, message_type, message) 
                    VALUES (?, 0, 'system', 'System', 'system', ?)
                ");
                $welcome_msg->execute([
                    $room_id,
                    "Welcome to your service request chat! You can communicate with technicians and support team here."
                ]);
                
                $messages[] = "✅ Created chat room for service request #{$request['id']}";
            }
        }
        
    } catch (PDOException $e) {
        $messages[] = "⚠️ Could not create sample chat rooms: " . $e->getMessage();
    }
    
    // 7. Test the chat system
    $messages[] = "\n🧪 Testing chat system...";
    
    try {
        // Test room creation
        $test_room_id = 'test_room_' . time();
        $pdo->prepare("INSERT INTO chat_rooms (room_id, room_type, room_name) VALUES (?, 'general', 'Test Room')")->execute([$test_room_id]);
        
        // Test message insertion
        $pdo->prepare("
            INSERT INTO chat_messages (room_id, sender_id, sender_type, sender_name, message) 
            VALUES (?, 1, 'admin', 'Test Admin', 'Test message')
        ")->execute([$test_room_id]);
        
        // Test participant addition
        $pdo->prepare("INSERT INTO chat_participants (room_id, user_id, user_type, user_name) VALUES (?, 1, 'admin', 'Test Admin')")->execute([$test_room_id]);
        
        // Clean up test data
        $pdo->prepare("DELETE FROM chat_rooms WHERE room_id = ?")->execute([$test_room_id]);
        
        $messages[] = "✅ Chat system test passed";
        
    } catch (PDOException $e) {
        $messages[] = "❌ Chat system test failed: " . $e->getMessage();
        $success = false;
    }
    
    if ($success) {
        $messages[] = "\n🎉 SUCCESS: In-App Chat System database setup completed!";
        $messages[] = "✅ Chat rooms can now be created for service requests";
        $messages[] = "✅ Real-time messaging is ready to be implemented";
        $messages[] = "✅ File sharing capabilities are set up";
        $messages[] = "✅ Multi-user chat support is enabled";
    }
    
} catch (PDOException $e) {
    $messages[] = "❌ Database error: " . $e->getMessage();
    $success = false;
} catch (Exception $e) {
    $messages[] = "❌ General error: " . $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Setup - SmartFix</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: <?php echo $success ? '#28a745' : '#dc3545'; ?>;
            margin-top: 0;
            border-bottom: 3px solid <?php echo $success ? '#28a745' : '#dc3545'; ?>;
            padding-bottom: 15px;
            text-align: center;
            font-size: 2.5rem;
        }
        
        .status-badge {
            display: block;
            padding: 15px 25px;
            border-radius: 50px;
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
            font-size: 18px;
            <?php if ($success): ?>
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            <?php else: ?>
                background: linear-gradient(135deg, #dc3545, #e74c3c);
                color: white;
                box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            <?php endif; ?>
        }
        
        .results {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            border-left: 5px solid #007bff;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            white-space: pre-line;
            margin: 25px 0;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .buttons {
            margin-top: 40px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #212529;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }
        
        .feature-card h3 {
            margin-top: 0;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 Chat System Setup</h1>
        
        <div class="status-badge">
            <?php if ($success): ?>
                🎉 Chat System Database Setup Completed Successfully!
            <?php else: ?>
                ⚠️ Setup Issues Need Attention
            <?php endif; ?>
        </div>
        
        <div class="results"><?php 
            foreach ($messages as $message) {
                echo htmlspecialchars($message) . "\n";
            }
        ?></div>
        
        <?php if ($success): ?>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>💬 Real-time Messaging</h3>
                <p>Customers, technicians, and admins can now chat in real-time about service requests.</p>
            </div>
            <div class="feature-card">
                <h3>📎 File Sharing</h3>
                <p>Share images and files within chat conversations for better communication.</p>
            </div>
            <div class="feature-card">
                <h3>👥 Multi-user Support</h3>
                <p>Group conversations with multiple participants in a single chat room.</p>
            </div>
            <div class="feature-card">
                <h3>📱 Mobile Ready</h3>
                <p>Chat interface will be fully responsive and mobile-friendly.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="buttons">
            <?php if ($success): ?>
                <a href="create_chat_interface.php" class="btn btn-success">🚀 Create Chat Interface</a>
                <a href="admin/admin_dashboard_new.php" class="btn">👨‍💼 Admin Dashboard</a>
            <?php else: ?>
                <a href="check_database_structure.php" class="btn btn-warning">🔍 Check Database</a>
            <?php endif; ?>
            <a href="index.php" class="btn">🏠 Home</a>
        </div>
    </div>
</body>
</html>