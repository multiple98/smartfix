<?php
/**
 * Enhanced Reviews & Rating System Setup
 * Creates comprehensive review tables and initial data
 */

include('includes/db.php');

echo "<h2>Setting up Enhanced Reviews & Rating System...</h2>";

try {
    // Create reviews table
    $sql_reviews = "
    CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_request_id INT NULL,
        product_id INT NULL,
        technician_id INT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        title VARCHAR(255) NOT NULL,
        comment TEXT NOT NULL,
        pros TEXT NULL,
        cons TEXT NULL,
        would_recommend BOOLEAN DEFAULT TRUE,
        verified_purchase BOOLEAN DEFAULT FALSE,
        helpful_count INT DEFAULT 0,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_response TEXT NULL,
        images JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE SET NULL,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
        INDEX idx_rating (rating),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_reviews);
    echo "✅ Reviews table created successfully<br>";

    // Create review helpfulness tracking
    $sql_review_votes = "
    CREATE TABLE IF NOT EXISTS review_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        user_id INT NOT NULL,
        vote_type ENUM('helpful', 'not_helpful') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_vote (review_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_review_votes);
    echo "✅ Review votes table created successfully<br>";

    // Create review categories for better organization
    $sql_review_categories = "
    CREATE TABLE IF NOT EXISTS review_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NULL,
        icon VARCHAR(50) NULL,
        sort_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_review_categories);
    echo "✅ Review categories table created successfully<br>";

    // Insert default review categories
    $categories = [
        ['Service Quality', 'Overall quality of service provided', 'fas fa-star', 1],
        ['Timeliness', 'Punctuality and time management', 'fas fa-clock', 2],
        ['Communication', 'Communication skills and responsiveness', 'fas fa-comments', 3],
        ['Professionalism', 'Professional behavior and expertise', 'fas fa-user-tie', 4],
        ['Value for Money', 'Price vs quality ratio', 'fas fa-dollar-sign', 5],
        ['Problem Resolution', 'Effectiveness in solving issues', 'fas fa-tools', 6]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO review_categories (name, description, icon, sort_order) VALUES (?, ?, ?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "✅ Default review categories inserted<br>";

    // Create review category ratings (detailed ratings per category)
    $sql_review_ratings = "
    CREATE TABLE IF NOT EXISTS review_category_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        category_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES review_categories(id) ON DELETE CASCADE,
        UNIQUE KEY unique_review_category (review_id, category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_review_ratings);
    echo "✅ Review category ratings table created successfully<br>";

    // Update products table to include review stats
    $sql_update_products = "
    ALTER TABLE products 
    ADD COLUMN IF NOT EXISTS average_rating DECIMAL(3,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS last_reviewed_at TIMESTAMP NULL";
    
    $pdo->exec($sql_update_products);
    echo "✅ Products table updated with review columns<br>";

    // Update technicians table to include detailed review stats
    $sql_update_technicians = "
    ALTER TABLE technicians 
    ADD COLUMN IF NOT EXISTS review_count INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS last_reviewed_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS service_quality_rating DECIMAL(3,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS timeliness_rating DECIMAL(3,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS communication_rating DECIMAL(3,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS professionalism_rating DECIMAL(3,2) DEFAULT 0";
    
    $pdo->exec($sql_update_technicians);
    echo "✅ Technicians table updated with detailed review columns<br>";

    // Create sample reviews for demonstration
    $sample_reviews = [
        [
            'user_id' => 1,
            'technician_id' => 1,
            'rating' => 5,
            'title' => 'Excellent Service!',
            'comment' => 'The technician was very professional and fixed my laptop quickly. Highly recommended!',
            'pros' => 'Fast service, professional attitude, fair pricing',
            'cons' => 'None really, maybe could have explained the issue in more detail',
            'would_recommend' => 1,
            'verified_purchase' => 1,
            'status' => 'approved'
        ],
        [
            'user_id' => 1,
            'technician_id' => 2,
            'rating' => 4,
            'title' => 'Good Experience',
            'comment' => 'Service was good overall. The technician arrived on time and was knowledgeable.',
            'pros' => 'Punctual, knowledgeable, clean work',
            'cons' => 'Slightly expensive, took longer than expected',
            'would_recommend' => 1,
            'verified_purchase' => 1,
            'status' => 'approved'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, technician_id, rating, title, comment, pros, cons, would_recommend, verified_purchase, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($sample_reviews as $review) {
        $stmt->execute([
            $review['user_id'],
            $review['technician_id'],
            $review['rating'],
            $review['title'],
            $review['comment'],
            $review['pros'],
            $review['cons'],
            $review['would_recommend'],
            $review['verified_purchase'],
            $review['status']
        ]);
    }
    echo "✅ Sample reviews inserted<br>";

    echo "<br><h3>✅ Enhanced Reviews & Rating System Setup Complete!</h3>";
    echo "<p>Features added:</p>";
    echo "<ul>";
    echo "<li>✅ Comprehensive reviews table with pros/cons</li>";
    echo "<li>✅ Review voting system (helpful/not helpful)</li>";
    echo "<li>✅ Review categories for detailed ratings</li>";
    echo "<li>✅ Admin moderation system</li>";
    echo "<li>✅ Image support for reviews</li>";
    echo "<li>✅ Verified purchase tracking</li>";
    echo "<li>✅ Updated product and technician rating stats</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>