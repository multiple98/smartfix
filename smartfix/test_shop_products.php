<?php
// Quick test for shop products
echo "SmartFix Shop Products Test\n";
echo "===========================\n\n";

try {
    // Test connection
    include_once 'includes/db.php';
    echo "✓ Database connected\n";
    
    // Check products table
    $tables = $pdo->query("SHOW TABLES LIKE 'products'")->fetchAll();
    if (count($tables) > 0) {
        echo "✓ Products table exists\n";
        
        // Count products
        $total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $active = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
        
        echo "✓ Total products: $total\n";
        echo "✓ Active products: $active\n";
        
        if ($active > 0) {
            // Show some sample products
            echo "\nSample Products:\n";
            echo "----------------\n";
            $products = $pdo->query("SELECT name, price, category FROM products WHERE status = 'active' LIMIT 5")->fetchAll();
            
            foreach ($products as $product) {
                echo "- {$product['name']} (\${$product['price']}) [{$product['category']}]\n";
            }
            
            echo "\n✅ SUCCESS: Shop should display products correctly!\n";
            echo "Visit: http://localhost/smartfix/shop.php\n";
        } else {
            echo "\n❌ No active products found. Run fix_shop_and_database.php to add sample products.\n";
        }
    } else {
        echo "❌ Products table missing. Run fix_shop_and_database.php to create it.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Run fix_shop_and_database.php to fix database issues.\n";
}
?>