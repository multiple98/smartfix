<?php
// Complete Chat System Integration
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

$messages = [];
$success = true;

try {
    $messages[] = "🚀 Integrating Chat System into SmartFix Platform";
    $messages[] = "================================================";
    
    // 1. Check if database tables exist
    $messages[] = "\n🔍 Checking chat system database tables...";
    
    $tables = ['chat_rooms', 'chat_messages', 'chat_participants', 'chat_files'];
    $missing_tables = [];
    
    foreach ($tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            $messages[] = "✅ Table '$table' exists";
        } catch (PDOException $e) {
            $missing_tables[] = $table;
            $messages[] = "❌ Table '$table' missing";
            $success = false;
        }
    }
    
    if (!empty($missing_tables)) {
        $messages[] = "\n⚠️ Please run 'setup_chat_system.php' first to create the required tables";
    } else {
        $messages[] = "\n✅ All chat system tables are ready";
    }
    
    // 2. Create chat integration files
    $messages[] = "\n📁 Creating chat integration files...";
    
    // Create API directory if it doesn't exist
    if (!file_exists('api')) {
        if (mkdir('api', 0755)) {
            $messages[] = "✅ Created 'api' directory";
        } else {
            $messages[] = "❌ Could not create 'api' directory";
            $success = false;
        }
    } else {
        $messages[] = "✅ API directory exists";
    }
    
    // Check if required files exist
    $required_files = [
        'api/chat.php' => 'Chat API endpoints',
        'includes/chat_widget.php' => 'Chat widget component'
    ];
    
    foreach ($required_files as $file => $description) {
        if (file_exists($file)) {
            $messages[] = "✅ $description file exists";
        } else {
            $messages[] = "❌ $description file missing: $file";
            $success = false;
        }
    }
    
    // 3. Create chat demo page
    $messages[] = "\n🎭 Creating chat demo page...";
    
    $demo_page = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Demo - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .demo-card {
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #007bff;
        }
        
        .demo-card h3 {
            color: #007bff;
            margin-top: 0;
        }
        
        .user-selector {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .user-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .user-btn.admin {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .user-btn.technician {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .user-btn.customer {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .user-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .instructions {
            background: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .instructions h3 {
            color: #007bff;
            margin-top: 0;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .feature-list li i {
            margin-right: 10px;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💬 SmartFix Chat System Demo</h1>
            <p>Experience real-time communication between customers, technicians, and administrators</p>
        </div>
        
        <div class="instructions">
            <h3>🚀 How to Test the Chat System:</h3>
            <ol>
                <li><strong>Choose a user type</strong> below to simulate different perspectives</li>
                <li><strong>Login</strong> with the corresponding account</li>
                <li><strong>Look for the blue chat bubble</strong> in the bottom-right corner</li>
                <li><strong>Click the chat bubble</strong> to open the chat interface</li>
                <li><strong>Start chatting</strong> and test real-time messaging</li>
            </ol>
        </div>
        
        <div class="user-selector">
            <a href="admin/admin_login.php" class="user-btn admin">
                <i class="fas fa-user-shield"></i> Login as Admin
            </a>
            <a href="login.php" class="user-btn customer">
                <i class="fas fa-user"></i> Login as Customer
            </a>
            <a href="login.php" class="user-btn technician">
                <i class="fas fa-wrench"></i> Login as Technician
            </a>
        </div>
        
        <div class="demo-grid">
            <div class="demo-card">
                <h3>👨‍💼 Admin Features</h3>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> View all chat rooms</li>
                    <li><i class="fas fa-check"></i> Create new chat rooms</li>
                    <li><i class="fas fa-check"></i> Join any conversation</li>
                    <li><i class="fas fa-check"></i> Monitor all communications</li>
                    <li><i class="fas fa-check"></i> Emergency chat support</li>
                </ul>
            </div>
            
            <div class="demo-card">
                <h3>🔧 Technician Features</h3>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Chat with assigned customers</li>
                    <li><i class="fas fa-check"></i> Share progress updates</li>
                    <li><i class="fas fa-check"></i> Send photos and files</li>
                    <li><i class="fas fa-check"></i> Coordinate with admin</li>
                    <li><i class="fas fa-check"></i> Real-time notifications</li>
                </ul>
            </div>
            
            <div class="demo-card">
                <h3>👤 Customer Features</h3>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> Chat about service requests</li>
                    <li><i class="fas fa-check"></i> Get real-time updates</li>
                    <li><i class="fas fa-check"></i> Share photos of issues</li>
                    <li><i class="fas fa-check"></i> Direct line to support</li>
                    <li><i class="fas fa-check"></i> Chat history access</li>
                </ul>
            </div>
        </div>
        
        <div class="instructions">
            <h3>💡 Chat System Features:</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4>🔄 Real-time Messaging</h4>
                    <p>Messages appear instantly across all connected users</p>
                </div>
                <div>
                    <h4>📱 Mobile Responsive</h4>
                    <p>Works perfectly on all devices and screen sizes</p>
                </div>
                <div>
                    <h4>📎 File Sharing</h4>
                    <p>Share images and documents within conversations</p>
                </div>
                <div>
                    <h4>🔔 Smart Notifications</h4>
                    <p>Unread message badges and desktop notifications</p>
                </div>
                <div>
                    <h4>👥 Multi-user Rooms</h4>
                    <p>Group conversations with multiple participants</p>
                </div>
                <div>
                    <h4>🔐 Secure & Private</h4>
                    <p>Messages are secure and tied to user sessions</p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <p><strong>Need help?</strong> The chat widget will appear in the bottom-right corner once you log in!</p>
            <a href="index.php" class="user-btn customer">← Back to SmartFix Home</a>
        </div>
    </div>
</body>
</html>
    ';
    
    if (file_put_contents('chat_demo.php', $demo_page)) {
        $messages[] = "✅ Chat demo page created: chat_demo.php";
    } else {
        $messages[] = "❌ Could not create demo page";
        $success = false;
    }
    
    // 4. Integration instructions for existing pages
    $messages[] = "\n🔗 Integration instructions:";
    $messages[] = "To add chat to any page, include these lines before closing </body> tag:";
    $messages[] = "";
    $messages[] = "<?php include('includes/chat_widget.php'); ?>";
    $messages[] = "";
    $messages[] = "Pages that would benefit from chat integration:";
    
    $integration_pages = [
        'admin/admin_dashboard_new.php' => 'Admin dashboard',
        'user/dashboard.php' => 'Customer dashboard', 
        'technician/dashboard.php' => 'Technician dashboard',
        'services/request_service.php' => 'Service request pages',
        'index.php' => 'Main homepage'
    ];
    
    foreach ($integration_pages as $page => $description) {
        if (file_exists($page)) {
            $messages[] = "  - $page ($description)";
        }
    }
    
    // 5. Automatically integrate chat into key pages
    $messages[] = "\n⚡ Auto-integrating chat widget into key pages...";
    
    $pages_to_integrate = [
        'admin/admin_dashboard_new.php' => 'Admin dashboard',
        'index.php' => 'Homepage'
    ];
    
    foreach ($pages_to_integrate as $page => $description) {
        if (file_exists($page)) {
            $content = file_get_contents($page);
            
            // Check if chat widget is already integrated
            if (strpos($content, "chat_widget.php") === false) {
                // Add chat widget before closing body tag
                $chat_include = "\n<?php include('includes/chat_widget.php'); ?>\n</body>";
                $content = str_replace('</body>', $chat_include, $content);
                
                if (file_put_contents($page, $content)) {
                    $messages[] = "✅ Integrated chat into $description";
                } else {
                    $messages[] = "❌ Could not integrate chat into $page";
                }
            } else {
                $messages[] = "✅ Chat already integrated in $description";
            }
        }
    }
    
    // 6. Create service request chat rooms for recent requests
    $messages[] = "\n🔄 Creating chat rooms for recent service requests...";
    
    try {
        $recent_requests = $pdo->query("
            SELECT id, name, email, service_type 
            FROM service_requests 
            WHERE DATE(COALESCE(created_at, request_date, NOW())) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            LIMIT 10
        ")->fetchAll();
        
        foreach ($recent_requests as $request) {
            $room_id = "service_" . $request['id'];
            
            // Check if room already exists
            $existing = $pdo->prepare("SELECT id FROM chat_rooms WHERE room_id = ?");
            $existing->execute([$room_id]);
            
            if (!$existing->fetch()) {
                // Create chat room
                $create_room = $pdo->prepare("
                    INSERT INTO chat_rooms (room_id, room_type, service_request_id, room_name) 
                    VALUES (?, 'service_request', ?, ?)
                ");
                $create_room->execute([
                    $room_id,
                    $request['id'],
                    "Service: {$request['service_type']} - {$request['name']}"
                ]);
                
                // Add welcome message
                $welcome = $pdo->prepare("
                    INSERT INTO chat_messages (room_id, sender_id, sender_type, sender_name, message_type, message) 
                    VALUES (?, 0, 'system', 'System', 'system', ?)
                ");
                $welcome->execute([
                    $room_id,
                    "Welcome! This chat is for your service request. Our team will assist you here."
                ]);
                
                $messages[] = "✅ Created chat room for service request #{$request['id']}";
            }
        }
        
    } catch (PDOException $e) {
        $messages[] = "⚠️ Could not create service request chat rooms: " . $e->getMessage();
    }
    
    if ($success) {
        $messages[] = "\n🎉 SUCCESS: Chat system integration completed!";
        $messages[] = "🔗 Visit chat_demo.php to test the chat system";
        $messages[] = "💬 Chat widget is now available on integrated pages";
        $messages[] = "📱 The system supports real-time messaging across all user types";
    }
    
} catch (Exception $e) {
    $messages[] = "❌ Integration error: " . $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat System Integration - SmartFix</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: <?php echo $success ? '#28a745' : '#dc3545'; ?>;
            margin-top: 0;
            border-bottom: 4px solid <?php echo $success ? '#28a745' : '#dc3545'; ?>;
            padding-bottom: 20px;
            text-align: center;
            font-size: 2.8rem;
        }
        
        .status-badge {
            display: block;
            padding: 20px 30px;
            border-radius: 50px;
            font-weight: bold;
            margin-bottom: 40px;
            text-align: center;
            font-size: 20px;
            <?php if ($success): ?>
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            <?php else: ?>
                background: linear-gradient(135deg, #dc3545, #e74c3c);
                color: white;
                box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3);
            <?php endif; ?>
        }
        
        .results {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border-left: 6px solid #007bff;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 14px;
            white-space: pre-line;
            margin: 30px 0;
            max-height: 600px;
            overflow-y: auto;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .buttons {
            margin-top: 50px;
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            display: inline-block;
            padding: 18px 35px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 123, 255, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #212529;
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
        }
        
        .btn-warning:hover {
            box-shadow: 0 12px 35px rgba(255, 193, 7, 0.4);
        }
        
        .feature-showcase {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .feature-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card i {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .integration-note {
            background: #e7f3ff;
            border: 2px solid #bee5eb;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        
        .integration-note h3 {
            color: #007bff;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💬 Chat System Integration</h1>
        
        <div class="status-badge">
            <?php if ($success): ?>
                🚀 Chat System Successfully Integrated!
            <?php else: ?>
                ⚠️ Integration Issues Detected
            <?php endif; ?>
        </div>
        
        <div class="results"><?php 
            foreach ($messages as $message) {
                echo htmlspecialchars($message) . "\n";
            }
        ?></div>
        
        <?php if ($success): ?>
        <div class="feature-showcase">
            <div class="feature-card">
                <i class="fas fa-comments"></i>
                <h3>Real-time Chat</h3>
                <p>Instant messaging between customers, technicians, and admins with live updates</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-mobile-alt"></i>
                <h3>Mobile Ready</h3>
                <p>Fully responsive design that works perfectly on all devices and screen sizes</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-paperclip"></i>
                <h3>File Sharing</h3>
                <p>Share images and documents directly within chat conversations</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-bell"></i>
                <h3>Smart Notifications</h3>
                <p>Unread message badges and real-time notification system</p>
            </div>
        </div>
        
        <div class="integration-note">
            <h3>🎯 Chat Widget Now Available!</h3>
            <p><strong>Look for the blue chat bubble in the bottom-right corner</strong> of your integrated pages. The chat widget automatically appears for logged-in users and provides seamless communication across your platform.</p>
        </div>
        <?php endif; ?>
        
        <div class="buttons">
            <?php if ($success): ?>
                <a href="chat_demo.php" class="btn btn-success">🎭 Try Chat Demo</a>
                <a href="admin/admin_dashboard_new.php" class="btn">👨‍💼 Admin Dashboard</a>
                <a href="index.php" class="btn">🏠 SmartFix Home</a>
            <?php else: ?>
                <a href="setup_chat_system.php" class="btn btn-warning">🔧 Run Chat Setup</a>
                <a href="check_database_structure.php" class="btn">🔍 Check Database</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>