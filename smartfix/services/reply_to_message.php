<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_id = $_SESSION['user_id'];
    $message_id = $_POST['message_id'];
    $reply_text = trim($_POST['reply_text']);

    if (!empty($reply_text)) {
        $stmt = $pdo->prepare("INSERT INTO replies (message_id, admin_id, reply_text) VALUES (?, ?, ?)");
        $stmt->execute([$message_id, $admin_id, $reply_text]);
    }

    header("Location: admin_messages.php");
    exit();
}
?>
