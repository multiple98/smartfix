<?php
include('includes/db.php');

echo "<h2>Fixing Loyalty Program Tables...</h2>";

try {
    // Create redeemed rewards table with correct timestamp handling
    $sql_redeemed_rewards = "
    CREATE TABLE IF NOT EXISTS redeemed_rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        reward_id INT NOT NULL,
        points_used INT NOT NULL,
        redemption_code VARCHAR(20) UNIQUE NOT NULL,
        status ENUM('active', 'used', 'expired') DEFAULT 'active',
        used_at TIMESTAMP NULL,
        expires_at DATETIME NOT NULL,
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

    // Create referrals table
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

    // Update users table to include loyalty information (with error handling)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN total_points INT DEFAULT 0");
        echo "✅ Added total_points column to users table<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ total_points column already exists<br>";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN available_points INT DEFAULT 0");
        echo "✅ Added available_points column to users table<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ available_points column already exists<br>";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN current_tier_id INT DEFAULT 1");
        echo "✅ Added current_tier_id column to users table<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ current_tier_id column already exists<br>";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN tier_updated_at TIMESTAMP NULL");
        echo "✅ Added tier_updated_at column to users table<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ tier_updated_at column already exists<br>";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN referral_code VARCHAR(20) UNIQUE NULL");
        echo "✅ Added referral_code column to users table<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ referral_code column already exists<br>";
        } else {
            throw $e;
        }
    }

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

    echo "<br><h3>✅ Loyalty Program Tables Fixed Successfully!</h3>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>