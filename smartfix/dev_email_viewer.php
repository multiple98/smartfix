<?php
// Development Email Viewer
session_start();
include("includes/db.php");

// Check if user is admin or in development mode
$is_dev = true; // Set to false in production

if (!$is_dev) {
    die("Access denied");
}

echo "<h2>📧 Development Email Viewer</h2>";
echo "<p>This page shows verification emails that couldn't be sent due to mail server configuration.</p>";

// Show debug email log
if (file_exists("debug_emails.log")) {
    echo "<h3>Recent Email Debug Log:</h3>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars(file_get_contents("debug_emails.log"));
    echo "</div>";
    
    echo "<br><a href='?clear_log=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Clear Log</a>";
    
    if (isset($_GET['clear_log'])) {
        file_put_contents("debug_emails.log", "");
        echo "<script>window.location.href = 'dev_email_viewer.php';</script>";
    }
} else {
    echo "<p>No debug emails found. Register a new user to see debug emails here.</p>";
}

// Show recent unverified users with manual verification links
echo "<h3>Unverified Users (Manual Verification Links):</h3>";
try {
    $stmt = $pdo->query("
        SELECT id, name, email, verification_token, verification_sent_at 
        FROM users 
        WHERE is_verified = 0 AND verification_token IS NOT NULL 
        ORDER BY verification_sent_at DESC 
        LIMIT 10
    ");
    $unverified = $stmt->fetchAll();
    
    if (empty($unverified)) {
        echo "<p>No unverified users found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;' >";
        echo "<tr><th>Name</th><th>Email</th><th>Sent At</th><th>Manual Verification</th></tr>";
        
        foreach ($unverified as $user) {
            $verification_url = "http://" . $_SERVER['HTTP_HOST'] . "/smartfix/verify_email.php?token=" . $user['verification_token'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['verification_sent_at'] . "</td>";
            echo "<td><a href='" . $verification_url . "' target='_blank' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px;'>Verify Now</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='register.php' style='background: #007BFF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Register New User</a>";
echo "<a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Home</a>";
?>