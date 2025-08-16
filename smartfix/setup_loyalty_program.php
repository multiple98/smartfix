<?php
/**
 * Loyalty Program Setup
 * Creates comprehensive loyalty and rewards system
 */

include('includes/db.php');

echo "<h2>Setting up Loyalty & Rewards Program...</h2>";

try {
    // Create loyalty points table
    $sql_loyalty_points = "
    CREATE TABLE IF NOT EXISTS loyalty_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        points INT NOT NULL,
        transaction_type ENUM('earned', 'redeemed', 'expired', 'bonus') NOT NULL,
        source_type ENUM('purchase', 'service', 'referral', 'review', 'signup', 'birthday', 'admin') NOT NULL,
        source_id INT NULL,
        description TEXT NOT NULL,
        expiry_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_transaction_type (transaction_type),
        INDEX idx_source_type (source_type),
        INDEX idx_expiry_date (expiry_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_loyalty_points);
    echo "✅ Loyalty points table created successfully<br>";

    // Create loyalty tiers table
    $sql_loyalty_tiers = "
    CREATE TABLE IF NOT EXISTS loyalty_tiers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        min_points INT NOT NULL,
        max_points INT NULL,
        discount_percentage DECIMAL(5,2) DEFAULT 0,
        points_multiplier DECIMAL(3,2) DEFAULT 1.00,
        benefits JSON NULL,
        color_code VARCHAR(7) DEFAULT '#007bff',
        icon VARCHAR(50) DEFAULT 'fas fa-star',
        description TEXT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_loyalty_tiers);
    echo "✅ Loyalty tiers table created successfully<br>";

    // Insert default loyalty tiers
    $tiers = [
        [
            'name' => 'Bronze',
            'min_points' => 0,
            'max_points' => 999,
            'discount_percentage' => 0,
            'points_multiplier' => 1.00,
            'benefits' => json_encode([
                'Free account setup',
                'Basic customer support',
                'Standard service booking'
            ]),
            'color_code' => '#CD7F32',
            'icon' => 'fas fa-medal',
            'description' => 'Welcome to SmartFix! Start earning points with every service and purchase.'
        ],
        [
            'name' => 'Silver',
            'min_points' => 1000,
            'max_points' => 4999,
            'discount_percentage' => 5.00,
            'points_multiplier' => 1.25,
            'benefits' => json_encode([
                '5% discount on all services',
                '25% bonus points on purchases',
                'Priority customer support',
                'Free service reminders'
            ]),
            'color_code' => '#C0C0C0',
            'icon' => 'fas fa-award',
            'description' => 'Enjoy enhanced benefits and priority support as a Silver member.'
        ],
        [
            'name' => 'Gold',
            'min_points' => 5000,
            'max_points' => 14999,
            'discount_percentage' => 10.00,
            'points_multiplier' => 1.50,
            'benefits' => json_encode([
                '10% discount on all services',
                '50% bonus points on purchases',
                'Premium customer support',
                'Free emergency service calls',
                'Extended warranty on repairs',
                'Birthday bonus points'
            ]),
            'color_code' => '#FFD700',
            'icon' => 'fas fa-crown',
            'description' => 'Premium benefits including emergency services and extended warranties.'
        ],
        [
            'name' => 'Platinum',
            'min_points' => 15000,
            'max_points' => null,
            'discount_percentage' => 15.00,
            'points_multiplier' => 2.00,
            'benefits' => json_encode([
                '15% discount on all services',
                '100% bonus points on purchases',
                'VIP customer support',
                'Free annual maintenance check',
                'Exclusive access to new services',
                'Personal account manager',
                'Free pickup and delivery'
            ]),
            'color_code' => '#E5E4E2',
            'icon' => 'fas fa-gem',
            'description' => 'Ultimate VIP experience with exclusive benefits and personal service.'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO loyalty_tiers 
        (name, min_points, max_points, discount_percentage, points_multiplier, benefits, color_code, icon, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($tiers as $tier) {
        $stmt->execute([
            $tier['name'],
            $tier['min_points'],
            $tier['max_points'],
            $tier['discount_percentage'],
            $tier['points_multiplier'],
            $tier['benefits'],
            $tier['color_code'],
            $tier['icon'],
            $tier['description']
        ]);
    }
    echo "✅ Default loyalty tiers inserted<br>";

    // Create rewards catalog table
    $sql_rewards = "
    CREATE TABLE IF NOT EXISTS rewards_catalog (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        points_required INT NOT NULL,
        reward_type ENUM('discount', 'service', 'product', 'cashback', 'upgrade') NOT NULL,
        reward_value DECIMAL(10,2) NULL,
        terms_conditions TEXT NULL,
        image_url VARCHAR(500) NULL,
        stock_quantity INT DEFAULT -1,
        min_tier_required INT DEFAULT 1,
        expiry_days INT DEFAULT 30,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (min_tier_required) REFERENCES loyalty_tiers(id),
        INDEX idx_points_required (points_required),
        INDEX idx_reward_type (reward_type),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_rewards);
    echo "✅ Rewards catalog table created successfully<br>";

    // Insert sample rewards
    $rewards = [
        [
            'name' => '$5 Service Discount',
            'description' => 'Get $5 off your next service booking',
            'points_required' => 500,
            'reward_type' => 'discount',
            'reward_value' => 5.00,
            'terms_conditions' => 'Valid for 30 days. Cannot be combined with other offers. Minimum service value $25.',
            'min_tier_required' => 1,
            'expiry_days' => 30
        ],
        [
            'name' => '$10 Service Discount',
            'description' => 'Get $10 off your next service booking',
            'points_required' => 1000,
            'reward_type' => 'discount',
            'reward_value' => 10.00,
            'terms_conditions' => 'Valid for 30 days. Cannot be combined with other offers. Minimum service value $50.',
            'min_tier_required' => 2,
            'expiry_days' => 30
        ],
        [
            'name' => 'Free Basic Diagnostic',
            'description' => 'Complimentary basic diagnostic service',
            'points_required' => 750,
            'reward_type' => 'service',
            'reward_value' => 25.00,
            'terms_conditions' => 'Valid for 60 days. Includes basic system check and report.',
            'min_tier_required' => 1,
            'expiry_days' => 60
        ],
        [
            'name' => '20% Off Next Purchase',
            'description' => '20% discount on your next product purchase',
            'points_required' => 1500,
            'reward_type' => 'discount',
            'reward_value' => 20.00,
            'terms_conditions' => 'Valid for 45 days. Applies to products only, not services.',
            'min_tier_required' => 2,
            'expiry_days' => 45
        ],
        [
            'name' => 'Priority Service Upgrade',
            'description' => 'Skip the queue with priority service booking',
            'points_required' => 2000,
            'reward_type' => 'upgrade',
            'reward_value' => 0,
            'terms_conditions' => 'Valid for 90 days. One-time use for priority scheduling.',
            'min_tier_required' => 3,
            'expiry_days' => 90
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO rewards_catalog 
        (name, description, points_required, reward_type, reward_value, terms_conditions, min_tier_required, expiry_days) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($rewards as $reward) {
        $stmt->execute([
            $reward['name'],
            $reward['description'],
            $reward['points_required'],
            $reward['reward_type'],
            $reward['reward_value'],
            $reward['terms_conditions'],
            $reward['min_tier_required'],
            $reward['expiry_days']
        ]);
    }
    echo "✅ Sample rewards inserted<br>";

    // Create redeemed rewards table
    $sql_redeemed_rewards = "
    CREATE TABLE IF NOT EXISTS redeemed_rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reward_id INT NOT NULL,
        points_used INT NOT NULL,
        redemption_code VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('active', 'used', 'expired') DEFAULT 'active',
        used_at TIMESTAMP NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reward_id) REFERENCES rewards_catalog(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_redemption_code (redemption_code),
        INDEX idx_status (status),
        INDEX idx_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_redeemed_rewards);
    echo "✅ Redeemed rewards table created successfully<br>";

    // Create referral system table
    $sql_referrals = "
    CREATE TABLE IF NOT EXISTS referrals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        referrer_id INT NOT NULL,
        referred_email VARCHAR(255) NOT NULL,
        referred_user_id INT NULL,
        referral_code VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('pending', 'completed', 'expired') DEFAULT 'pending',
        referrer_points_awarded INT DEFAULT 0,
        referred_points_awarded INT DEFAULT 0,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_referrer_id (referrer_id),
        INDEX idx_referral_code (referral_code),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_referrals);
    echo "✅ Referrals table created successfully<br>";

    // Update users table to include loyalty information
    $sql_update_users = "
    ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS total_points INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS available_points INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS current_tier_id INT DEFAULT 1,
    ADD COLUMN IF NOT EXISTS tier_updated_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) UNIQUE NULL,
    ADD FOREIGN KEY (current_tier_id) REFERENCES loyalty_tiers(id)";
    
    $pdo->exec($sql_update_users);
    echo "✅ Users table updated with loyalty columns<br>";

    // Generate referral codes for existing users
    $users_stmt = $pdo->query("SELECT id FROM users WHERE referral_code IS NULL");
    $users = $users_stmt->fetchAll();
    
    $update_referral_stmt = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    foreach ($users as $user) {
        $referral_code = 'SF' . strtoupper(substr(uniqid(), -6));
        $update_referral_stmt->execute([$referral_code, $user['id']]);
    }
    echo "✅ Referral codes generated for existing users<br>";

    // Create loyalty program settings table
    $sql_loyalty_settings = "
    CREATE TABLE IF NOT EXISTS loyalty_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NOT NULL,
        description TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_loyalty_settings);
    echo "✅ Loyalty settings table created successfully<br>";

    // Insert default loyalty settings
    $settings = [
        ['points_per_dollar_spent', '10', 'Points earned per dollar spent on services/products'],
        ['referral_points_referrer', '500', 'Points awarded to referrer when referral completes first purchase'],
        ['referral_points_referred', '250', 'Points awarded to new user when they complete first purchase'],
        ['review_points', '50', 'Points awarded for leaving a verified review'],
        ['signup_bonus_points', '100', 'Welcome bonus points for new users'],
        ['birthday_bonus_points', '200', 'Birthday bonus points'],
        ['points_expiry_months', '24', 'Number of months before points expire'],
        ['min_redemption_points', '100', 'Minimum points required for any redemption'],
        ['tier_evaluation_frequency', '30', 'Days between tier evaluation checks']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO loyalty_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "✅ Default loyalty settings inserted<br>";

    echo "<br><h3>✅ Loyalty & Rewards Program Setup Complete!</h3>";
    echo "<p>Features added:</p>";
    echo "<ul>";
    echo "<li>✅ Points earning and redemption system</li>";
    echo "<li>✅ 4-tier loyalty program (Bronze, Silver, Gold, Platinum)</li>";
    echo "<li>✅ Comprehensive rewards catalog</li>";
    echo "<li>✅ Referral system with unique codes</li>";
    echo "<li>✅ Automatic tier upgrades based on points</li>";
    echo "<li>✅ Points expiry management</li>";
    echo "<li>✅ Configurable program settings</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>