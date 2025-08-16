<?php
session_start();
require_once '../includes/db.php'; // adjust if db.php is elsewhere

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message'], $_POST['request_id'])) {
    $message = trim($_POST['message']);
    $request_id = intval($_POST['request_id']);
    $sender_id = $_SESSION['user_id'];

    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (request_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$request_id, $sender_id, $message]);
        header("Location: ../services/track_service.php?sent=1");
        exit();
    }
}
header("Location: ../services/track_service.php?error=1");
exit();
