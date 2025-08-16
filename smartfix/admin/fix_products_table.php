<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Set a message
$_SESSION['success_message'] = "Redirecting to update the products table structure...";

// Redirect to the update script
header("Location: update_products_table.php");
exit();
?>