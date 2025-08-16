<?php
session_start();
include('includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $pdo->beginTransaction();
    
    $product_id = intval($_POST['product_id']);
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    // Check if product exists and is in stock
    $check_product = "SELECT * FROM products WHERE id = :product_id";
    $check_stmt = $pdo->prepare($check_product);
    $check_stmt->execute(['product_id' => $product_id]);
    
    if ($check_stmt->rowCount() == 0) {
      throw new Exception("Product not found.");
    }
    
    $product = $check_stmt->fetch();
    
    // Check if stock column exists and if product is in stock
    $has_stock_column = false;
    $check_column = "SHOW COLUMNS FROM products LIKE 'stock'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $has_stock_column = $column_stmt->rowCount() > 0;
    
    if ($has_stock_column && isset($product['stock']) && $product['stock'] <= 0) {
      throw new Exception("Sorry, this product is out of stock.");
    }
    
    // Insert the order
    $query = "INSERT INTO orders (product_id, customer_name, phone, address, created_at)
              VALUES (:product_id, :name, :phone, :address, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
      'product_id' => $product_id,
      'name' => $name,
      'phone' => $phone,
      'address' => $address
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // Update stock if the column exists
    if ($has_stock_column) {
      $update_stock = "UPDATE products SET stock = stock - 1 WHERE id = :product_id AND stock > 0";
      $update_stmt = $pdo->prepare($update_stock);
      $update_stmt->execute(['product_id' => $product_id]);
    }
    
    $pdo->commit();
    
    // Redirect to transport page
    header("Location: transport.php?order_id=" . $order_id);
    exit();
  } catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage();
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
  }
}
?>
