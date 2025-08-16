<?php
include('includes/db.php');

echo "<h2>Fixing Reviews Table Structure...</h2>";

try {
    // Drop existing reviews table if it exists
    $pdo->exec("DROP TABLE IF EXISTS review_votes");
    $pdo->exec("DROP TABLE IF EXISTS review_category_ratings");
    $pdo->exec("DROP TABLE IF EXISTS reviews");
    echo "✅ Dropped existing reviews tables<br>";
    
    // Create reviews table with correct structure
    $sql_reviews = "
    CREATE TABLE reviews (
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
        INDEX idx_rating (rating),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_reviews);
    echo "✅ Reviews table created successfully<br>";

    // Create review votes table
    $sql_review_votes = "
    CREATE TABLE review_votes (
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

    // Create review category ratings table
    $sql_review_ratings = "
    CREATE TABLE review_category_ratings (
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

    // Insert sample reviews
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

    echo "<br><h3>✅ Reviews Table Fixed Successfully!</h3>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>