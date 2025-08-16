<?php
session_start();
include('includes/db.php');
include('components/review_system.php');

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: shop.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}

$reviewSystem = new ReviewSystem($pdo);
$review_stats = $reviewSystem->getReviewStats(null, $product_id, null);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }

        .header {
            background: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            color: #007bff;
            font-size: 28px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #007bff;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .product-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .product-image {
            text-align: center;
        }

        .product-image img {
            max-width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .product-info h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 15px;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stars {
            display: flex;
            gap: 2px;
        }

        .stars i {
            color: #ffc107;
            font-size: 18px;
        }

        .rating-text {
            color: #666;
            font-size: 14px;
        }

        .product-price {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 20px;
        }

        .product-description {
            color: #666;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .product-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .product-specs {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .product-specs h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .spec-label {
            font-weight: 600;
            color: #333;
        }

        .spec-value {
            color: #666;
        }

        .reviews-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .review-summary {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 40px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
        }

        .rating-overview {
            text-align: center;
            min-width: 200px;
        }

        .overall-rating {
            font-size: 48px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .rating-breakdown {
            flex: 1;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .rating-label {
            min-width: 60px;
            font-size: 14px;
            color: #666;
        }

        .rating-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: #ffc107;
            border-radius: 4px;
        }

        .rating-count {
            min-width: 40px;
            font-size: 14px;
            color: #666;
            text-align: right;
        }

        .write-review-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 30px;
        }

        .write-review-btn:hover {
            background: #1e7e34;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .review-summary {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1><i class="fas fa-tools"></i> SmartFix</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="services.php">Services</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="user/dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="product-section">
            <div class="product-grid">
                <div class="product-image">
                    <?php if ($product['image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <img src="uploads/no-image.jpg" alt="No image available">
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-rating">
                        <div class="stars">
                            <?php 
                            $rating = $review_stats['average_rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="<?php echo $i <= $rating ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo number_format($rating, 1); ?> 
                            (<?php echo $review_stats['total_reviews'] ?? 0; ?> reviews)
                        </span>
                    </div>
                    
                    <div class="product-price">
                        $<?php echo number_format($product['price'], 2); ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <div class="product-actions">
                        <form action="add_to_cart.php" method="POST" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </form>
                        
                        <a href="shop.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Shop
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="product-specs">
                <h3><i class="fas fa-info-circle"></i> Product Specifications</h3>
                <div class="specs-grid">
                    <div class="spec-item">
                        <span class="spec-label">Category:</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['category'] ?? 'General'); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Stock:</span>
                        <span class="spec-value">
                            <?php echo $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock'; ?>
                        </span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">SKU:</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['id']); ?></span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Added:</span>
                        <span class="spec-value"><?php echo date('M j, Y', strtotime($product['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <?php if ($review_stats && $review_stats['total_reviews'] > 0): ?>
            <div class="review-summary">
                <div class="rating-overview">
                    <div class="overall-rating"><?php echo number_format($review_stats['average_rating'], 1); ?></div>
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= $review_stats['average_rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <p><?php echo $review_stats['total_reviews']; ?> reviews</p>
                </div>
                
                <div class="rating-breakdown">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <?php 
                        $count = $review_stats[$star . '_star'] ?? 0;
                        $percentage = $review_stats['total_reviews'] > 0 ? ($count / $review_stats['total_reviews']) * 100 : 0;
                        ?>
                        <div class="rating-row">
                            <span class="rating-label"><?php echo $star; ?> star</span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <span class="rating-count"><?php echo $count; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="write-review-btn" onclick="toggleReviewForm()">
                    <i class="fas fa-edit"></i> Write a Review
                </button>
                
                <div id="review-form" style="display: none;">
                    <?php echo $reviewSystem->displayReviewForm(null, $product_id, null); ?>
                </div>
            <?php else: ?>
                <p style="margin-bottom: 30px;">
                    <a href="login.php" style="color: #007bff; text-decoration: none;">Login</a> to write a review
                </p>
            <?php endif; ?>
            
            <?php echo $reviewSystem->displayReviews(null, $product_id, null, 20); ?>
        </div>
    </div>

    <script>
    function toggleReviewForm() {
        const form = document.getElementById('review-form');
        const btn = document.querySelector('.write-review-btn');
        
        if (form.style.display === 'none') {
            form.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-times"></i> Cancel Review';
            form.scrollIntoView({ behavior: 'smooth' });
        } else {
            form.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-edit"></i> Write a Review';
        }
    }
    </script>
</body>
</html>