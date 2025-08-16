<?php
session_start();
include 'includes/db.php';

// Set up a test session for checkout
$_SESSION['user_id'] = 1; // Test user ID
$_SESSION['cart'] = [
    1 => 2, // Product ID 1, quantity 2
    2 => 1  // Product ID 2, quantity 1
];

echo "<h1>🛒 Checkout System Test</h1>";

try {
    // 1. Check if user exists
    echo "<h2>1. Checking User</h2>";
    $user_check = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_check->execute([1]);
    $user = $user_check->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✓ Test user found: " . htmlspecialchars($user['name'] ?? $user['username'] ?? 'User #1') . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No test user found. Creating one...</p>";
        $create_user = $pdo->prepare("INSERT INTO users (username, email, password, name, phone) VALUES (?, ?, ?, ?, ?)");
        $create_user->execute(['testuser', 'test@example.com', password_hash('test123', PASSWORD_DEFAULT), 'Test User', '+260-97-000-0000']);
        echo "<p style='color: green;'>✓ Test user created</p>";
    }
    
    // 2. Check if products exist
    echo "<h2>2. Checking Products</h2>";
    $product_check = $pdo->query("SELECT COUNT(*) FROM products");
    $product_count = $product_check->fetchColumn();
    
    if ($product_count > 0) {
        echo "<p style='color: green;'>✓ Found $product_count products in database</p>";
        
        // Show some sample products
        $sample_products = $pdo->query("SELECT * FROM products LIMIT 3")->fetchAll();
        foreach ($sample_products as $product) {
            echo "<p>- " . htmlspecialchars($product['name']) . " (ZMW " . number_format($product['price'], 2) . ")</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ No products found. Adding sample products...</p>";
        $sample_products = [
            ['Smartphone Repair Kit', 'Complete kit for smartphone repairs', 150.00],
            ['Laptop Screen', 'Replacement laptop screen 15.6 inch', 350.00],
            ['Phone Battery', 'Universal phone battery pack', 80.00]
        ];
        
        $insert_product = $pdo->prepare("INSERT INTO products (name, description, price, category, created_at) VALUES (?, ?, ?, 'Electronics', NOW())");
        foreach ($sample_products as $product) {
            $insert_product->execute($product);
        }
        echo "<p style='color: green;'>✓ Added " . count($sample_products) . " sample products</p>";
    }
    
    // 3. Check transport providers
    echo "<h2>3. Checking Transport Providers</h2>";
    $transport_check = $pdo->query("SELECT COUNT(*) FROM transport_providers WHERE status = 'active'");
    $transport_count = $transport_check->fetchColumn();
    
    if ($transport_count > 0) {
        echo "<p style='color: green;'>✓ Found $transport_count active transport providers</p>";
        
        $providers = $pdo->query("SELECT * FROM transport_providers WHERE status = 'active' LIMIT 3")->fetchAll();
        foreach ($providers as $provider) {
            echo "<p>- " . htmlspecialchars($provider['name']) . " (ZMW " . number_format($provider['cost_per_km'], 2) . "/km, " . $provider['estimated_days'] . " days)</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ No active transport providers found</p>";
        echo "<p><a href='fix_checkout_system.php'>Run the checkout system fix</a> to add sample transport providers.</p>";
    }
    
    // 4. Test cart calculation
    echo "<h2>4. Testing Cart Calculation</h2>";
    $total = 0;
    $cart_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);
        $product = $product_stmt->fetch();
        
        if ($product) {
            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;
            $cart_items[] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
            echo "<p>- " . htmlspecialchars($product['name']) . " x $quantity = ZMW " . number_format($subtotal, 2) . "</p>";
        }
    }
    
    echo "<p><strong>Total: ZMW " . number_format($total, 2) . "</strong></p>";
    
    // 5. Test database tables
    echo "<h2>5. Checking Required Tables</h2>";
    $required_tables = ['orders', 'order_items', 'order_tracking', 'transport_providers', 'notifications'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $check_table = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check_table->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' missing</p>";
            $missing_tables[] = $table;
        }
    }
    
    if (count($missing_tables) > 0) {
        echo "<p style='color: red;'>Missing tables: " . implode(', ', $missing_tables) . "</p>";
        echo "<p><a href='fix_checkout_system.php'>Run the checkout system fix</a> to create missing tables.</p>";
    }
    
    // 6. Provide test links
    echo "<h2>6. Test Links</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
    echo "<h3>Ready to test:</h3>";
    echo "<ul>";
    echo "<li><a href='shop/cart.php'>View Cart</a> (with test items)</li>";
    echo "<li><a href='shop/checkout.php'>Proceed to Checkout</a></li>";
    echo "<li><a href='admin/transport_providers.php'>Manage Transport Providers</a></li>";
    echo "<li><a href='fix_checkout_system.php'>Fix Database Issues</a></li>";
    echo "</ul>";
    echo "</div>";
    
    // 7. Show current session info
    echo "<h2>7. Current Session</h2>";
    echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
    echo "<p><strong>Cart Items:</strong> " . count($_SESSION['cart'] ?? []) . "</p>";
    echo "<p><strong>Cart Contents:</strong></p>";
    echo "<pre>" . print_r($_SESSION['cart'] ?? [], true) . "</pre>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection.</p>";
    echo "</div>";
}
?>