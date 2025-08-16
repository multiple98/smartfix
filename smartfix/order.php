<?php
session_start();
include('includes/db.php');

// Handle error messages
$error_message = '';
$success_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (!isset($_GET['product_id'])) {
    echo "Product not found.";
    exit;
}

try {
    $product_id = intval($_GET['product_id']);
    $query = "SELECT * FROM products WHERE id = :product_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['product_id' => $product_id]);
    
    if ($stmt->rowCount() == 0) {
        echo "Product not found.";
        exit;
    }
    
    $product = $stmt->fetch();
    
    // Check if product is in stock
    $in_stock = true;
    if (isset($product['stock']) && $product['stock'] <= 0) {
        $in_stock = false;
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Your Order - <?= htmlspecialchars($product['name']) ?> | SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --body-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--body-bg);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: var(--dark-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 32px;
        }
        
        nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        nav a {
            color: var(--dark-color);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 25px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        nav a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Order Container */
        .order-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }
        
        /* Product Preview Card */
        .product-preview {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        
        .product-image {
            width: 100%;
            height: 300px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .product-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            z-index: 1;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .product-image:hover img {
            transform: scale(1.05);
        }
        
        .no-image {
            color: var(--secondary-color);
            font-size: 64px;
            opacity: 0.5;
        }
        
        .product-info h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--success-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price::before {
            content: 'K';
            font-size: 1.5rem;
            opacity: 0.7;
        }
        
        .stock-info {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .in-stock {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
        }
        
        .out-of-stock {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .product-description {
            background: var(--light-color);
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
        }
        
        .product-description h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        /* Order Form Card */
        .order-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }
        
        .form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-color);
        }
        
        .form-header h3 {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .form-header i {
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1rem;
        }
        
        .form-group label i {
            color: var(--primary-color);
            width: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
            transform: translateY(-2px);
        }
        
        .form-control:hover {
            border-color: var(--primary-color);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            justify-content: center;
        }
        
        .quantity-btn {
            width: 50px;
            height: 50px;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: var(--transition);
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .quantity-input {
            width: 100px;
            text-align: center;
            padding: 15px;
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        /* Order Summary */
        .order-summary {
            background: linear-gradient(135deg, var(--light-color), #e9ecef);
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            border: 2px solid var(--primary-color);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 15px;
            border-top: 2px solid var(--primary-color);
            font-weight: bold;
            font-size: 1.3rem;
            color: var(--primary-color);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            min-height: 55px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color), #5a6268);
            color: white;
            box-shadow: 0 5px 15px rgba(108,117,125,0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108,117,125,0.4);
        }
        
        .btn:disabled {
            background: var(--secondary-color);
            cursor: not-allowed;
            opacity: 0.6;
            transform: none;
        }
        
        .form-actions {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
            margin-top: 40px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }
        
        .alert i {
            font-size: 1.5rem;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
        }
        
        /* Out of Stock Message */
        .out-of-stock-message {
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border-radius: 20px;
            border: 3px solid #dc3545;
        }
        
        .out-of-stock-message i {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 25px;
            display: block;
        }
        
        .out-of-stock-message h3 {
            color: #721c24;
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .out-of-stock-message p {
            color: #721c24;
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.8;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .order-container {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .product-preview {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .product-preview,
            .order-form {
                padding: 25px;
            }
            
            .form-actions {
                grid-template-columns: 1fr;
            }
            
            nav {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            nav a {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            header {
                padding: 1rem;
                flex-direction: column;
                gap: 15px;
            }
            
            .logo {
                font-size: 24px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .product-preview,
            .order-form {
                padding: 20px;
            }
        }
        
        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Form Validation Styles */
        .form-control.invalid {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
        }
        
        .form-control.valid {
            border-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
        }
        
        .field-error {
            color: var(--danger-color);
            font-size: 0.9rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <i class="fas fa-tools"></i>
            <span>SmartFix</span>
        </div>
        <nav>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="services.php">
                <i class="fas fa-tools"></i>
                <span>Services</span>
            </a>
            <a href="shop.php">
                <i class="fas fa-shopping-cart"></i>
                <span>Shop</span>
            </a>
            <a href="about.php">
                <i class="fas fa-info-circle"></i>
                <span>About</span>
            </a>
            <a href="contact.php">
                <i class="fas fa-phone"></i>
                <span>Contact</span>
            </a>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-shopping-cart"></i>
                Place Your Order
            </h1>
            <p>Complete your purchase details below and we'll process your order immediately</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>

        <div class="order-container">
            <!-- Product Preview -->
            <div class="product-preview">
                <div class="product-image">
                    <?php 
                    $image_path = 'uploads/no-image.jpg';
                    if (!empty($product['image'])) {
                        if (file_exists('uploads/' . $product['image'])) {
                            $image_path = 'uploads/' . $product['image'];
                        } elseif (file_exists($product['image'])) {
                            $image_path = $product['image'];
                        }
                    }
                    
                    if (file_exists($image_path)): ?>
                        <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h2><?= htmlspecialchars($product['name']) ?></h2>
                    <div class="price"><?= number_format($product['price'], 2) ?></div>
                    
                    <?php if ($in_stock): ?>
                        <div class="stock-info in-stock">
                            <i class="fas fa-check-circle"></i>
                            <span>In Stock</span>
                            <?php if (isset($product['stock'])): ?>
                                <span>(<?= $product['stock'] ?> available)</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="stock-info out-of-stock">
                            <i class="fas fa-times-circle"></i>
                            <span>Out of Stock</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description">
                            <h4><i class="fas fa-info-circle"></i> Product Description</h4>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Form -->
            <div class="order-form">
                <div class="form-header">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Order Details</h3>
                </div>
                
                <?php if ($in_stock): ?>
                    <form action="process_order.php" method="POST" id="orderForm" novalidate>
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        
                        <div class="form-group">
                            <label for="customer_name">
                                <i class="fas fa-user"></i>
                                <span>Full Name *</span>
                            </label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" 
                                   placeholder="Enter your full name" required>
                            <div class="field-error" id="name-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                <span>Phone Number *</span>
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   placeholder="e.g., +260 977 123456" required>
                            <div class="field-error" id="phone-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Delivery Address *</span>
                            </label>
                            <textarea id="address" name="address" class="form-control" rows="4" 
                                      placeholder="Enter your complete delivery address including city and province" required></textarea>
                            <div class="field-error" id="address-error"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">
                                <i class="fas fa-shopping-basket"></i>
                                <span>Quantity</span>
                            </label>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="decreaseQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" 
                                       max="<?= isset($product['stock']) ? $product['stock'] : 999 ?>" 
                                       class="quantity-input" onchange="updateTotal()">
                                <button type="button" class="quantity-btn" onclick="increaseQuantity()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="order-summary">
                            <div class="summary-row">
                                <span><i class="fas fa-tag"></i> Price per item:</span>
                                <span>K<?= number_format($product['price'], 2) ?></span>
                            </div>
                            <div class="summary-row">
                                <span><i class="fas fa-shopping-basket"></i> Quantity:</span>
                                <span id="display-quantity">1</span>
                            </div>
                            <div class="summary-row">
                                <span><i class="fas fa-calculator"></i> Total Amount:</span>
                                <span id="total-price">K<?= number_format($product['price'], 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="shop.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back to Shop</span>
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-credit-card"></i>
                                <span>Place Order</span>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="out-of-stock-message">
                        <i class="fas fa-ban"></i>
                        <h3>Product Out of Stock</h3>
                        <p>Sorry, this product is currently out of stock. Please check back later or browse our other products.</p>
                        <a href="shop.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Browse Other Products</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const pricePerItem = <?= $product['price'] ?>;
        const maxStock = <?= isset($product['stock']) ? $product['stock'] : 999 ?>;
        
        // Form validation
        const form = document.getElementById('orderForm');
        const submitBtn = document.getElementById('submitBtn');
        
        function validateField(field, errorElement, validationFn, errorMessage) {
            const value = field.value.trim();
            const isValid = validationFn(value);
            
            if (isValid) {
                field.classList.remove('invalid');
                field.classList.add('valid');
                errorElement.textContent = '';
                return true;
            } else {
                field.classList.remove('valid');
                field.classList.add('invalid');
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMessage}`;
                return false;
            }
        }
        
        function validateName(name) {
            return name.length >= 2 && /^[a-zA-Z\s]+$/.test(name);
        }
        
        function validatePhone(phone) {
            return phone.length >= 10 && /^[\+]?[0-9\s\-\(\)]+$/.test(phone);
        }
        
        function validateAddress(address) {
            return address.length >= 10;
        }
        
        // Real-time validation
        document.getElementById('customer_name').addEventListener('blur', function() {
            validateField(this, document.getElementById('name-error'), validateName, 'Please enter a valid full name (letters only, minimum 2 characters)');
        });
        
        document.getElementById('phone').addEventListener('blur', function() {
            validateField(this, document.getElementById('phone-error'), validatePhone, 'Please enter a valid phone number (minimum 10 digits)');
        });
        
        document.getElementById('address').addEventListener('blur', function() {
            validateField(this, document.getElementById('address-error'), validateAddress, 'Please enter a complete delivery address (minimum 10 characters)');
        });
        
        // Form submission
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const nameField = document.getElementById('customer_name');
                const phoneField = document.getElementById('phone');
                const addressField = document.getElementById('address');
                
                const nameValid = validateField(nameField, document.getElementById('name-error'), validateName, 'Please enter a valid full name');
                const phoneValid = validateField(phoneField, document.getElementById('phone-error'), validatePhone, 'Please enter a valid phone number');
                const addressValid = validateField(addressField, document.getElementById('address-error'), validateAddress, 'Please enter a complete delivery address');
                
                if (nameValid && phoneValid && addressValid) {
                    // Show loading state
                    submitBtn.innerHTML = '<div class="loading"></div> <span>Processing Order...</span>';
                    submitBtn.disabled = true;
                    
                    // Submit form
                    this.submit();
                } else {
                    // Scroll to first error
                    const firstError = document.querySelector('.form-control.invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        }
        
        function updateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const total = quantity * pricePerItem;
            document.getElementById('total-price').textContent = 'K' + total.toFixed(2);
            document.getElementById('display-quantity').textContent = quantity;
        }
        
        function increaseQuantity() {
            const input = document.getElementById('quantity');
            const current = parseInt(input.value) || 1;
            if (current < maxStock) {
                input.value = current + 1;
                updateTotal();
            }
        }
        
        function decreaseQuantity() {
            const input = document.getElementById('quantity');
            const current = parseInt(input.value) || 1;
            if (current > 1) {
                input.value = current - 1;
                updateTotal();
            }
        }
        
        // Initialize
        updateTotal();
        
        // Add smooth scrolling for navigation
        document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add loading animation to navigation links
        document.querySelectorAll('nav a:not([href^="#"])').forEach(link => {
            link.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.innerHTML += ' <div class="loading" style="width: 15px; height: 15px;"></div>';
            });
        });
    </script>
</body>
</html>