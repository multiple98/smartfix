<?php
/**
 * Enhanced Review System Components
 * Includes review forms, display, and management functions
 */

class ReviewSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Display review form
     */
    public function displayReviewForm($service_request_id = null, $product_id = null, $technician_id = null) {
        if (!isset($_SESSION['user_id'])) {
            return '<p>Please <a href="login.php">login</a> to leave a review.</p>';
        }
        
        $form_id = 'reviewForm_' . ($service_request_id ?? $product_id ?? $technician_id);
        
        ob_start();
        ?>
        <div class="review-form-container">
            <h3><i class="fas fa-star"></i> Leave a Review</h3>
            <form id="<?php echo $form_id; ?>" class="review-form" method="POST" action="submit_review.php" enctype="multipart/form-data">
                <input type="hidden" name="service_request_id" value="<?php echo $service_request_id; ?>">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <input type="hidden" name="technician_id" value="<?php echo $technician_id; ?>">
                
                <!-- Overall Rating -->
                <div class="form-group">
                    <label>Overall Rating *</label>
                    <div class="star-rating" data-rating="0">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="overall_rating" required>
                </div>
                
                <!-- Category Ratings -->
                <div class="category-ratings">
                    <h4>Rate by Category</h4>
                    <?php
                    $stmt = $this->pdo->query("SELECT * FROM review_categories WHERE is_active = 1 ORDER BY sort_order");
                    $categories = $stmt->fetchAll();
                    foreach ($categories as $category):
                    ?>
                    <div class="category-rating">
                        <label><?php echo htmlspecialchars($category['name']); ?></label>
                        <div class="star-rating category-stars" data-category="<?php echo $category['id']; ?>" data-rating="0">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="category_ratings[<?php echo $category['id']; ?>]" class="category-rating-input">
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Review Title -->
                <div class="form-group">
                    <label for="review_title">Review Title *</label>
                    <input type="text" id="review_title" name="title" required maxlength="255" 
                           placeholder="Summarize your experience">
                </div>
                
                <!-- Review Comment -->
                <div class="form-group">
                    <label for="review_comment">Your Review *</label>
                    <textarea id="review_comment" name="comment" required rows="4" 
                              placeholder="Share your detailed experience..."></textarea>
                </div>
                
                <!-- Pros and Cons -->
                <div class="pros-cons-container">
                    <div class="form-group">
                        <label for="review_pros">What did you like? (Pros)</label>
                        <textarea id="review_pros" name="pros" rows="2" 
                                  placeholder="What were the positive aspects?"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="review_cons">What could be improved? (Cons)</label>
                        <textarea id="review_cons" name="cons" rows="2" 
                                  placeholder="What could have been better?"></textarea>
                    </div>
                </div>
                
                <!-- Recommendation -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="would_recommend" value="1" checked>
                        <span class="checkmark"></span>
                        I would recommend this service/product to others
                    </label>
                </div>
                
                <!-- Photo Upload -->
                <div class="form-group">
                    <label for="review_images">Add Photos (Optional)</label>
                    <input type="file" id="review_images" name="images[]" multiple accept="image/*" 
                           class="file-input">
                    <div class="file-upload-info">
                        <i class="fas fa-camera"></i>
                        <span>Upload up to 5 photos (Max 5MB each)</span>
                    </div>
                    <div id="image-preview" class="image-preview-container"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Review
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.review-form-container').style.display='none'">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <style>
        .review-form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .review-form h3 {
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin: 10px 0;
            cursor: pointer;
        }
        
        .star-rating i {
            font-size: 24px;
            color: #ddd;
            transition: color 0.2s;
        }
        
        .star-rating i:hover,
        .star-rating i.active {
            color: #ffc107;
        }
        
        .category-ratings {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .category-rating {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }
        
        .category-rating label {
            font-weight: 500;
            margin: 0;
        }
        
        .category-stars i {
            font-size: 18px;
        }
        
        .pros-cons-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        
        .file-upload-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .image-preview-container {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e1e5e9;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255,0,0,0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
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
        
        @media (max-width: 768px) {
            .pros-cons-container {
                grid-template-columns: 1fr;
            }
            
            .category-rating {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Star rating functionality
            document.querySelectorAll('.star-rating').forEach(function(rating) {
                const stars = rating.querySelectorAll('i');
                const isCategory = rating.classList.contains('category-stars');
                
                stars.forEach(function(star, index) {
                    star.addEventListener('click', function() {
                        const ratingValue = index + 1;
                        rating.dataset.rating = ratingValue;
                        
                        // Update visual state
                        stars.forEach(function(s, i) {
                            if (i < ratingValue) {
                                s.classList.remove('far');
                                s.classList.add('fas', 'active');
                            } else {
                                s.classList.remove('fas', 'active');
                                s.classList.add('far');
                            }
                        });
                        
                        // Update hidden input
                        if (isCategory) {
                            const categoryId = rating.dataset.category;
                            const input = rating.parentElement.querySelector('.category-rating-input');
                            if (input) input.value = ratingValue;
                        } else {
                            document.getElementById('overall_rating').value = ratingValue;
                        }
                    });
                    
                    star.addEventListener('mouseenter', function() {
                        const hoverValue = index + 1;
                        stars.forEach(function(s, i) {
                            if (i < hoverValue) {
                                s.style.color = '#ffc107';
                            } else {
                                s.style.color = '#ddd';
                            }
                        });
                    });
                });
                
                rating.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(rating.dataset.rating);
                    stars.forEach(function(s, i) {
                        if (i < currentRating) {
                            s.style.color = '#ffc107';
                        } else {
                            s.style.color = '#ddd';
                        }
                    });
                });
            });
            
            // Image preview functionality
            const imageInput = document.getElementById('review_images');
            const previewContainer = document.getElementById('image-preview');
            
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    previewContainer.innerHTML = '';
                    const files = Array.from(e.target.files).slice(0, 5); // Limit to 5 images
                    
                    files.forEach(function(file, index) {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const preview = document.createElement('div');
                                preview.className = 'image-preview';
                                preview.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                                    <button type="button" class="remove-image" onclick="removeImage(this, ${index})">×</button>
                                `;
                                previewContainer.appendChild(preview);
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                });
            }
        });
        
        function removeImage(button, index) {
            button.parentElement.remove();
            // Note: This is a simplified version. In production, you'd want to update the file input
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Display reviews for a specific item
     */
    public function displayReviews($service_request_id = null, $product_id = null, $technician_id = null, $limit = 10) {
        $where_conditions = [];
        $params = [];
        
        if ($service_request_id) {
            $where_conditions[] = "r.service_request_id = ?";
            $params[] = $service_request_id;
        }
        if ($product_id) {
            $where_conditions[] = "r.product_id = ?";
            $params[] = $product_id;
        }
        if ($technician_id) {
            $where_conditions[] = "r.technician_id = ?";
            $params[] = $technician_id;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $query = "
            SELECT r.*, u.name as user_name, u.email as user_email,
                   t.name as technician_name,
                   (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = r.id AND rv.vote_type = 'helpful') as helpful_votes,
                   (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = r.id AND rv.vote_type = 'not_helpful') as not_helpful_votes
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN technicians t ON r.technician_id = t.id
            {$where_clause}
            AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT ?
        ";
        
        $params[] = $limit;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll();
        
        if (empty($reviews)) {
            return '<div class="no-reviews"><i class="fas fa-star"></i><p>No reviews yet. Be the first to leave a review!</p></div>';
        }
        
        ob_start();
        ?>
        <div class="reviews-container">
            <h3><i class="fas fa-comments"></i> Customer Reviews (<?php echo count($reviews); ?>)</h3>
            
            <?php foreach ($reviews as $review): ?>
            <div class="review-item" data-review-id="<?php echo $review['id']; ?>">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar">
                            <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                        </div>
                        <div class="reviewer-details">
                            <h4><?php echo htmlspecialchars($review['user_name']); ?></h4>
                            <div class="review-meta">
                                <div class="star-display">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                <?php if ($review['verified_purchase']): ?>
                                    <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($review['would_recommend']): ?>
                        <div class="recommendation-badge">
                            <i class="fas fa-thumbs-up"></i> Recommends
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="review-content">
                    <h5 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h5>
                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    
                    <?php if ($review['pros'] || $review['cons']): ?>
                    <div class="pros-cons">
                        <?php if ($review['pros']): ?>
                        <div class="pros">
                            <h6><i class="fas fa-plus-circle"></i> Pros</h6>
                            <p><?php echo nl2br(htmlspecialchars($review['pros'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($review['cons']): ?>
                        <div class="cons">
                            <h6><i class="fas fa-minus-circle"></i> Cons</h6>
                            <p><?php echo nl2br(htmlspecialchars($review['cons'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="review-actions">
                    <div class="helpfulness">
                        <span>Was this helpful?</span>
                        <button class="helpful-btn" onclick="voteReview(<?php echo $review['id']; ?>, 'helpful')">
                            <i class="fas fa-thumbs-up"></i> Yes (<?php echo $review['helpful_votes']; ?>)
                        </button>
                        <button class="not-helpful-btn" onclick="voteReview(<?php echo $review['id']; ?>, 'not_helpful')">
                            <i class="fas fa-thumbs-down"></i> No (<?php echo $review['not_helpful_votes']; ?>)
                        </button>
                    </div>
                </div>
                
                <?php if ($review['admin_response']): ?>
                <div class="admin-response">
                    <div class="response-header">
                        <i class="fas fa-reply"></i>
                        <strong>SmartFix Team Response</strong>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($review['admin_response'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .reviews-container {
            margin: 30px 0;
        }
        
        .reviews-container h3 {
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
        }
        
        .review-item {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .reviewer-info {
            display: flex;
            gap: 15px;
        }
        
        .reviewer-avatar {
            width: 50px;
            height: 50px;
            background: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .reviewer-details h4 {
            margin: 0 0 8px 0;
            color: #333;
        }
        
        .review-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
            color: #666;
        }
        
        .star-display i {
            color: #ffc107;
            margin-right: 2px;
        }
        
        .verified-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .recommendation-badge {
            background: #e8f5e8;
            color: #28a745;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .review-title {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .review-comment {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .pros-cons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .pros h6, .cons h6 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .pros h6 {
            color: #28a745;
        }
        
        .cons h6 {
            color: #dc3545;
        }
        
        .pros p, .cons p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .review-actions {
            border-top: 1px solid #e1e5e9;
            padding-top: 15px;
            margin-top: 20px;
        }
        
        .helpfulness {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14px;
        }
        
        .helpful-btn, .not-helpful-btn {
            background: none;
            border: 1px solid #e1e5e9;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }
        
        .helpful-btn:hover {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .not-helpful-btn:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        
        .admin-response {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-top: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .response-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #1976d2;
            font-weight: 600;
        }
        
        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-reviews i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .review-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .pros-cons {
                grid-template-columns: 1fr;
            }
            
            .helpfulness {
                flex-wrap: wrap;
            }
        }
        </style>
        
        <script>
        function voteReview(reviewId, voteType) {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                alert('Please login to vote on reviews.');
                return;
            }
            
            fetch('vote_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    review_id: reviewId,
                    vote_type: voteType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Simple reload for now
                } else {
                    alert(data.message || 'Error voting on review');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error voting on review');
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get review statistics
     */
    public function getReviewStats($service_request_id = null, $product_id = null, $technician_id = null) {
        $where_conditions = [];
        $params = [];
        
        if ($service_request_id) {
            $where_conditions[] = "service_request_id = ?";
            $params[] = $service_request_id;
        }
        if ($product_id) {
            $where_conditions[] = "product_id = ?";
            $params[] = $product_id;
        }
        if ($technician_id) {
            $where_conditions[] = "technician_id = ?";
            $params[] = $technician_id;
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        $query = "
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star,
                SUM(CASE WHEN would_recommend = 1 THEN 1 ELSE 0 END) as recommend_count
            FROM reviews 
            {$where_clause}
            AND status = 'approved'
        ";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
?>