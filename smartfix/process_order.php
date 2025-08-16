<?php
session_start();
include('includes/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $product_id = intval($_POST['product_id']);
        $customer_name = trim($_POST['customer_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $quantity = intval($_POST['quantity'] ?? 1);

        // Validate input
        if (empty($customer_name) || empty($phone) || empty($address)) {
            throw new Exception("All fields are required.");
        }

        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception("Invalid product or quantity.");
        }

        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        // Check stock availability
        if (isset($product['stock']) && $product['stock'] < $quantity) {
            throw new Exception("Insufficient stock available. Only " . $product['stock'] . " items in stock.");
        }

        // Calculate total
        $total_amount = $product['price'] * $quantity;

        // Start transaction
        $pdo->beginTransaction();

        // Create order
        $order_query = "INSERT INTO orders (user_id, shipping_name, shipping_phone, shipping_address, 
                       shipping_city, shipping_province, payment_method, total_amount, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'cash_on_delivery', ?, 'processing', NOW())";
        
        $user_id = $_SESSION['user_id'] ?? null;
        $order_stmt = $pdo->prepare($order_query);
        $order_stmt->execute([
            $user_id,
            $customer_name,
            $phone,
            $address,
            'Not specified',
            'Not specified',
            $total_amount
        ]);

        $order_id = $pdo->lastInsertId();

        // Generate tracking number
        $tracking_number = 'SF-ORD-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);

        // Update order with tracking number
        $update_query = "UPDATE orders SET tracking_number = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$tracking_number, $order_id]);

        // Add order item
        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $item_stmt = $pdo->prepare($item_query);
        $item_stmt->execute([$order_id, $product_id, $quantity, $product['price']]);

        // Update product stock (only if stock column exists)
        if (isset($product['stock'])) {
            $stock_query = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stock_stmt = $pdo->prepare($stock_query);
            $stock_stmt->execute([$quantity, $product_id]);
        }

        // Add initial tracking entry - check if timestamp column exists
        try {
            $check_tracking_columns = "SHOW COLUMNS FROM order_tracking LIKE 'timestamp'";
            $tracking_column_stmt = $pdo->prepare($check_tracking_columns);
            $tracking_column_stmt->execute();
            $timestamp_column_exists = $tracking_column_stmt->rowCount() > 0;
            
            if ($timestamp_column_exists) {
                $tracking_query = "INSERT INTO order_tracking (order_id, status, description, location, timestamp) 
                                  VALUES (?, 'Order Placed', 'Your order has been received and is being processed.', 'SmartFix Warehouse', NOW())";
            } else {
                // Use created_at if timestamp doesn't exist
                $check_created_at = "SHOW COLUMNS FROM order_tracking LIKE 'created_at'";
                $created_at_stmt = $pdo->prepare($check_created_at);
                $created_at_stmt->execute();
                $created_at_exists = $created_at_stmt->rowCount() > 0;
                
                if ($created_at_exists) {
                    $tracking_query = "INSERT INTO order_tracking (order_id, status, description, location, created_at) 
                                      VALUES (?, 'Order Placed', 'Your order has been received and is being processed.', 'SmartFix Warehouse', NOW())";
                } else {
                    $tracking_query = "INSERT INTO order_tracking (order_id, status, description, location) 
                                      VALUES (?, 'Order Placed', 'Your order has been received and is being processed.', 'SmartFix Warehouse')";
                }
            }
            
            $tracking_stmt = $pdo->prepare($tracking_query);
            $tracking_stmt->execute([$order_id]);
        } catch (PDOException $e) {
            // If order_tracking table doesn't exist, create it
            try {
                $create_tracking_table = "CREATE TABLE IF NOT EXISTS order_tracking (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    description TEXT NOT NULL,
                    location VARCHAR(100),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_order (order_id)
                )";
                $pdo->exec($create_tracking_table);
                
                // Now insert the tracking record
                $tracking_query = "INSERT INTO order_tracking (order_id, status, description, location) 
                                  VALUES (?, 'Order Placed', 'Your order has been received and is being processed.', 'SmartFix Warehouse')";
                $tracking_stmt = $pdo->prepare($tracking_query);
                $tracking_stmt->execute([$order_id]);
            } catch (PDOException $e2) {
                // Continue without tracking if table creation fails
            }
        }

        // Create notification for admin
        try {
            $notification_query = "INSERT INTO notifications (type, message, is_read, created_at) 
                                  VALUES ('new_order', ?, 0, NOW())";
            $notification_stmt = $pdo->prepare($notification_query);
            $notification_stmt->execute(["New order ({$tracking_number}) from {$customer_name} - {$phone}"]);
        } catch (PDOException $e) {
            // Notifications table might not exist, continue without it
        }

        // Commit transaction
        $pdo->commit();

        // Set success message
        $_SESSION['success_message'] = "Your order has been placed successfully! Order ID: {$tracking_number}";

        // Redirect to order confirmation page (which will show transport selection)
        header("Location: shop/order_confirmation.php?id={$order_id}");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: order.php?product_id=" . intval($_POST['product_id']));
        exit();
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        
        // Check if tables need to be created
        if ($e->getCode() == '42S02') {
            $_SESSION['error_message'] = "Database tables need to be set up. Please contact administrator.";
        } else {
            $_SESSION['error_message'] = "Database error occurred. Please try again.";
        }
        
        header("Location: order.php?product_id=" . intval($_POST['product_id']));
        exit();
    }
} else {
    header("Location: shop.php");
    exit();
}
?>
