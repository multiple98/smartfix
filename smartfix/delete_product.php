<?php
session_start();
include('includes/db.php');

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: admin_login.php");
  exit();
}

// Check if product ID is passed
if (!isset($_GET['id'])) {
  echo "Product ID not found.";
  exit();
}

// Delete product
$product_id = $_GET['id'];
$query = "DELETE FROM products WHERE id = $product_id";

if (mysqli_query($conn, $query)) {
  header("Location: admin_dashboard.php?success=1");
  exit();
} else {
  echo "Database error: " . mysqli_error($conn);
}
?>
