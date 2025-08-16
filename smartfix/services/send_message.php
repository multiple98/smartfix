<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $sender_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Validate inputs
    if ($request_id <= 0) {
        die("Invalid request ID");
    }
    
    if (empty($message)) {
        die("Message cannot be empty");
    }
    
    // Check if the request exists
    $check_stmt = $pdo->prepare("SELECT id FROM service_requests WHERE id = ?");
    $check_stmt->execute([$request_id]);
    if ($check_stmt->rowCount() == 0) {
        die("Service request not found");
    }
    
    // Insert the message
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, request_id, message, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$sender_id, $request_id, $message]);
        
        // Create a notification for admin
        $notify_stmt = $pdo->prepare("INSERT INTO notifications (message, type) VALUES (?, 'message')");
        $notify_stmt->execute(["New message received for request #$request_id"]);
        
        // Redirect back to the tracking page
        header("Location: track_service.php?id=$request_id&sent=1");
        exit();
    } catch (PDOException $e) {
        // Check if the messages table exists
        if ($e->getCode() == '42S02') { // Table doesn't exist error
            // Create the messages table
            $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT,
                request_id INT,
                message TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
            )");
            
            // Try again
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, request_id, message, timestamp) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$sender_id, $request_id, $message]);
            
            header("Location: track_service.php?id=$request_id&sent=1");
            exit();
        } else {
            die("Error: " . $e->getMessage());
        }
    }
} else {
    // Redirect to tracking page if accessed directly
    header("Location: track_service.php");
    exit();
}
?>