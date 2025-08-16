<?php
session_start();
include('../includes/db.php');

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $product_id = intval($_POST['product_id']);
        
        switch ($action) {
            case 'add':
                $quantity = intval($_POST['quantity']) ?: 1;
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                $message = "Item added to cart successfully!";
                $message_type = "success";
                break;
                
            case 'update':
                $quantity = intval($_POST['quantity']);
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                    $message = "Cart updated successfully!";
                    $message_type = "success";
                } else {
                    unset($_SESSION['cart'][$product_id]);
                    $message = "Item removed from cart!";
                    $message_type = "info";
                }
                break;
                
            case 'remove':
                unset($_SESSION['cart'][$product_id]);
                $message = "Item removed from cart!";
                $message_type = "info";
                break;
                
            case 'clear':
                $_SESSION['cart'] = [];
                $message = "Cart cleared!";
                $message_type = "info";
                break;
        }
    }
}

// Get cart items
$cart_items = [];
$total_amount = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $product['quantity'] = $quantity;
                $product['subtotal'] = $product['price'] * $quantity;
                $cart_items[] = $product;
                $total_amount += $product['subtotal'];
            }
        } catch (PDOException $e) {
            // Skip this product if there's an error
            continue;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        header {
            background: #004080;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-weight: 500;
            transition: 0.3s;
        }
        
        nav a:hover {
            color: #ffcc00;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            text-align: center;
            margin: 30px 0;
        }
        
        .page-title h1 {
            font-size: 32px;
            color: #004080;
            margin-bottom: 10px;
        }
        
        .page-title p {
            color: #666;
            font-size: 16px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .cart-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        
        .cart-header h2 {
            margin: 0;
            color: #004080;
        }
        
        .cart-count {
            background: #007BFF;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .cart-items {
            margin-bottom: 30px;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #f1f1f1;
            gap: 20px;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 24px;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-size: 18px;
            font-weight: 600;
            color: #004080;
            margin-bottom: 8px;
        }
        
        .item-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .item-price {
            font-size: 16px;
            font-weight: bold;
            color: #007BFF;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: #007BFF;
            color: white;
            border-color: #007BFF;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .item-subtotal {
            font-size: 18px;
            font-weight: bold;
            color: #007BFF;
            min-width: 120px;
            text-align: right;
        }
        
        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 100px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007BFF;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-outline {
            background: transparent;
            color: #007BFF;
            border: 1px solid #007BFF;
        }
        
        .btn-outline:hover {
            background: #007BFF;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .cart-summary {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .summary-label {
            font-weight: 500;
        }
        
        .summary-value {
            font-weight: 600;
            color: #007BFF;
        }
        
        .summary-total {
            font-size: 20px;
            padding-top: 15px;
            border-top: 2px solid #007BFF;
            margin-top: 20px;
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-cart i {
            font-size: 80px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-cart h3 {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-details {
                text-align: center;
            }
            
            .item-subtotal, .item-actions {
                min-width: auto;
                text-align: center;
            }
            
            .cart-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-tools"></i> SmartFix
        </div>
        <nav>
            <a href="../index.php"><i class="fas fa-home"></i> Home</a>
            <a href="../services.php"><i class="fas fa-tools"></i> Services</a>
            <a href="../shop.php"><i class="fas fa-shopping-cart"></i> Shop</a>
            <a href="../about.php"><i class="fas fa-info-circle"></i> About</a>
            <a href="../contact.php"><i class="fas fa-phone"></i> Contact</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="../user/dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <div class="page-title">
            <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="cart-container">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some items to your cart to get started.</p>
                    <a href="../shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-header">
                    <h2>Cart Items</h2>
                    <div class="cart-count">
                        <?php echo count($cart_items); ?> item<?php echo count($cart_items) != 1 ? 's' : ''; ?>
                    </div>
                </div>

                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <?php if ($item['image'] && file_exists('../uploads/' . $item['image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                <div class="item-price">K<?php echo number_format($item['price'], 2); ?> each</div>
                                
                                <form method="POST" class="quantity-controls">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    
                                    <button type="button" class="quantity-btn" onclick="decreaseQuantity(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock'] ?? 999; ?>" 
                                           class="quantity-input" id="qty_<?php echo $item['id']; ?>"
                                           onchange="updateQuantity(<?php echo $item['id']; ?>)">
                                    
                                    <button type="button" class="quantity-btn" onclick="increaseQuantity(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <div class="item-subtotal">
                                K<?php echo number_format($item['subtotal'], 2); ?>
                            </div>
                            
                            <div class="item-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('Remove this item from cart?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal:</span>
                        <span class="summary-value">K<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Shipping:</span>
                        <span class="summary-value">Calculated at checkout</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value">K<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                </div>

                <div class="cart-actions">
                    <a href="../shop.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-secondary" 
                                onclick="return confirm('Clear all items from cart?')">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <a href="../login.php?redirect=shop/checkout.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login to Checkout
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function increaseQuantity(productId) {
            const input = document.getElementById(`qty_${productId}`);
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value);
            if (current < max) {
                input.value = current + 1;
                updateQuantity(productId);
            }
        }

        function decreaseQuantity(productId) {
            const input = document.getElementById(`qty_${productId}`);
            const current = parseInt(input.value);
            if (current > 1) {
                input.value = current - 1;
                updateQuantity(productId);
            }
        }

        function updateQuantity(productId) {
            const input = document.getElementById(`qty_${productId}`);
            const form = input.closest('form');
            
            // Auto-submit form after a short delay
            setTimeout(() => {
                form.submit();
            }, 500);
        }
    </script>
</body>
</html>