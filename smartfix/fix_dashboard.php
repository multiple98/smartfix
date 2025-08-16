<?php
// This script updates the database and redirects to the new dashboard
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database update script
include('update_service_requests_table.php');

// Rename files
if (file_exists('dashboard.php')) {
    // Backup the old dashboard
    if (!file_exists('dashboard_old.php')) {
        rename('dashboard.php', 'dashboard_old.php');
    }
}

if (file_exists('dashboard_new.php')) {
    // Move the new dashboard to replace the old one
    rename('dashboard_new.php', 'dashboard.php');
}

// Redirect to the dashboard
header("Location: dashboard.php");
exit();
?>