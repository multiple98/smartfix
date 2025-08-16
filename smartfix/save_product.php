<?php
session_start();
include('includes/db.php');
require_once 'includes/SecurityManager.php';
require_once 'includes/PerformanceManager.php';

// Initialize security manager
$security = new SecurityManager($pdo);
$security::secureSession();

if (!isset($_SESSION['admin_logged_in'])) {
  header("Location: admin_login.php");
  exit();
}

// Check rate limiting
if (!$security->checkRateLimit('product_upload')) {
  die("Too many upload attempts. Please try again later.");
}

$response = ['success' => false, 'message' => ''];

try {
  // Verify CSRF token
  if (!isset($_POST['csrf_token']) || !$security->verifyCSRFToken($_POST['csrf_token'], $_SESSION['user_id'] ?? null)) {
    throw new Exception("Invalid security token. Please refresh and try again.");
  }
  
  // Sanitize and validate inputs
  $name = $security->sanitizeInput($_POST['name']);
  $description = $security->sanitizeInput($_POST['description']);
  $price = $security->sanitizeInput($_POST['price'], 'float');
  $category = $security->sanitizeInput($_POST['category']);
  
  // Validate required fields
  if (empty($name) || empty($description) || $price <= 0 || empty($category)) {
    throw new Exception("All fields are required and price must be greater than 0.");
  }
  
  // Validate category
  $allowedCategories = ['phone', 'computer', 'spare', 'car'];
  if (!in_array($category, $allowedCategories)) {
    throw new Exception("Invalid category selected.");
  }
  
  $imagePath = null;
  
  // Handle image upload with security validation
  if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    try {
      // Validate file
      $security->validateFileUpload($_FILES['image'], ['jpg', 'jpeg', 'png'], 5242880); // 5MB limit
      
      // Generate secure filename
      $secureFilename = $security->generateSecureFilename($_FILES['image']['name'], 'product_');
      $uploadDir = "uploads/";
      $imagePath = $uploadDir . $secureFilename;
      
      // Create upload directory if it doesn't exist
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }
      
      // Move uploaded file
      if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
        throw new Exception("Failed to move uploaded file.");
      }
      
      // Optimize image
      $performance = new PerformanceManager($pdo);
      $tempPath = $imagePath . '.tmp';
      if ($performance->optimizeImage($imagePath, $tempPath, 85)) {
        unlink($imagePath);
        rename($tempPath, $imagePath);
      }
      
    } catch (Exception $e) {
      throw new Exception("Image upload failed: " . $e->getMessage());
    }
  }
  
  // Check if user_id column exists
  $check_user_id = $pdo->prepare("SHOW COLUMNS FROM products LIKE 'user_id'");
  $check_user_id->execute();
  $user_id_exists = $check_user_id->rowCount() > 0;
  
  // Get admin user_id from session or default to 1
  $admin_user_id = $_SESSION['user_id'] ?? 1;
  
  // Insert product using prepared statement
  if ($user_id_exists) {
    $query = "INSERT INTO products (name, description, price, category, image, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $execute_params = [$name, $description, $price, $category, $imagePath, $admin_user_id];
  } else {
    $query = "INSERT INTO products (name, description, price, category, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($query);
    $execute_params = [$name, $description, $price, $category, $imagePath];
  }
  
  if ($stmt->execute($execute_params)) {
    $productId = $pdo->lastInsertId();
    
    // Log the action
    $security->auditLog($_SESSION['user_id'] ?? null, 'product_created', 'products', $productId, null, [
      'name' => $name,
      'price' => $price,
      'category' => $category
    ]);
    
    // Reset rate limit on success
    $security->resetRateLimit('product_upload');
    
    $response = [
      'success' => true,
      'message' => 'Product uploaded successfully!',
      'product_id' => $productId,
      'redirect' => 'admin/admin_dashboard.php'
    ];
  } else {
    throw new Exception("Failed to save product to database.");
  }
  
} catch (Exception $e) {
  // Clean up uploaded file if database insert failed
  if (isset($imagePath) && file_exists($imagePath)) {
    unlink($imagePath);
  }
  
  $response = [
    'success' => false,
    'message' => $e->getMessage()
  ];
  
  // Log the error
  error_log("Product upload error: " . $e->getMessage());
}

// Return JSON response for AJAX requests, otherwise redirect
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  header('Content-Type: application/json');
  echo json_encode($response);
} else {
  if ($response['success']) {
    header("Location: admin/admin_dashboard.php?success=" . urlencode($response['message']));
  } else {
    header("Location: upload_product.php?error=" . urlencode($response['message']));
  }
}
