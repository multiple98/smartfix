<?php
session_start();
include('../includes/db.php'); // Make sure your DB is connected

// Get submitted credentials
$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = mysqli_real_escape_string($conn, $_POST['password']);

// You can either store admin credentials in the DB or hardcode them temporarily
// Option 1: Hardcoded login (for quick testing)
$admin_user = "admin";
$admin_pass = "1234"; // Change this to something secure later

if ($username === $admin_user && $password === $admin_pass) {
    $_SESSION['admin_logged_in'] = true;
    header("Location: dashboard.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid username or password.";
    header("Location: login.php");
    exit();
}
