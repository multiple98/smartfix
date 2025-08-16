<?php
session_start();
require_once '../includes/db.php';

// Only allow admins
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    $reply_text = isset($_POST['reply_text']) ? trim($_POST['reply_text']) : '';
    
    // Validate inputs
    if ($message_id <= 0) {
        die("Invalid message ID");
    }
    
    if (empty($reply_text)) {
        die("Reply cannot be empty");
    }
    
    // Insert the reply
    try {
        $stmt = $pdo->prepare("INSERT INTO replies (message_id, reply_text) VALUES (?, ?)");
        $stmt->execute([$message_id, $reply_text]);
        
        // Redirect back to the messages page
        header("Location: admin_messages.php?replied=1");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    // Redirect to messages page if accessed directly
    header("Location: admin_messages.php");
    exit();
}
?>