<?php
session_start();
include('includes/db.php');

if (!isset($_GET['order_id'])) {
  echo "Order ID missing.";
  exit;
}

try {
  $order_id = intval($_GET['order_id']);
  $query = "SELECT o.*, p.name AS product_name, p.price FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.id = :order_id";
  
  $stmt = $pdo->prepare($query);
  $stmt->execute(['order_id' => $order_id]);
  
  if ($stmt->rowCount() == 0) {
    echo "Order not found.";
    exit;
  }
  
  $order = $stmt->fetch();
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Transport Options</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f0f0;
      padding: 30px;
    }
    .transport-box {
      background: white;
      max-width: 600px;
      margin: auto;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .option {
      margin: 15px 0;
      padding: 10px;
      background: #e9f7ef;
      border-left: 6px solid #28a745;
      border-radius: 6px;
    }
  </style>
</head>
<body>

<div class="transport-box">
  <h2>🚚 Suggested Transport for Order #<?= $order_id ?></h2>
  <p><strong>Product:</strong> <?= htmlspecialchars($order['product_name']) ?> (K<?= number_format($order['price'], 2) ?>)</p>
  <p><strong>Delivery To:</strong> <?= htmlspecialchars($order['address']) ?></p>

  <div class="option">
    ✅ <strong>Bike Courier:</strong> Same-day delivery in town (~K50)
  </div>
  <div class="option">
    ✅ <strong>Mini Van:</strong> Suitable for bulk or spare parts (~K100)
  </div>
  <div class="option">
    ✅ <strong>Logistics Partner:</strong> 1-2 days delivery outside town (~K150)
  </div>
  <div class="option">
    ✅ <strong>Buses transport:</strong> 1-2 days delivery outside town (~K150)
  </div>
  <p style="margin-top: 20px;">You will be contacted soon to confirm transport.</p>
  
  <div style="margin-top: 30px; text-align: center;">
    <p style="color: #28a745; font-weight: bold;">✅ Your order has been placed successfully!</p>
    <a href="shop.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #007BFF; color: white; text-decoration: none; border-radius: 5px;">Continue Shopping</a>
  </div>
</div>

</body>
</html>
