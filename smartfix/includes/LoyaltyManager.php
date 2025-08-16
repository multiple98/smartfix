<?php
/**
 * Loyalty Program Manager
 * Handles all loyalty program operations
 */

class LoyaltyManager {
    private $pdo;
    private $settings;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadSettings();
    }
    
    /**
     * Load loyalty program settings
     */
    private function loadSettings() {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM loyalty_settings");
        $this->settings = [];
        while ($row = $stmt->fetch()) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    /**
     * Award points to a user
     */
    public function awardPoints($user_id, $points, $source_type, $source_id = null, $description = '') {
        try {
            $this->pdo->beginTransaction();
            
            // Calculate expiry date
            $expiry_months = $this->settings['points_expiry_months'] ?? 24;
            $expiry_date = date('Y-m-d', strtotime("+{$expiry_months} months"));
            
            // Insert points transaction
            $stmt = $this->pdo->prepare("
                INSERT INTO loyalty_points (user_id, points, transaction_type, source_type, source_id, description, expiry_date) 
                VALUES (?, ?, 'earned', ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $points, $source_type, $source_id, $description, $expiry_date]);
            
            // Update user's point totals
            $this->updateUserPoints($user_id);
            
            // Check for tier upgrade
            $this->checkTierUpgrade($user_id);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error awarding points: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Redeem points for a reward
     */
    public function redeemReward($user_id, $reward_id) {
        try {
            $this->pdo->beginTransaction();
            
            // Get reward details
            $reward_stmt = $this->pdo->prepare("SELECT * FROM rewards_catalog WHERE id = ? AND is_active = 1");
            $reward_stmt->execute([$reward_id]);
            $reward = $reward_stmt->fetch();
            
            if (!$reward) {
                throw new Exception('Reward not found or inactive');
            }
            
            // Check user's available points
            $user_stmt = $this->pdo->prepare("SELECT available_points, current_tier_id FROM users WHERE id = ?");
            $user_stmt->execute([$user_id]);
            $user = $user_stmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            if ($user['available_points'] < $reward['points_required']) {
                throw new Exception('Insufficient points');
            }
            
            // Check tier requirement
            if ($user['current_tier_id'] < $reward['min_tier_required']) {
                throw new Exception('Tier requirement not met');
            }
            
            // Check stock (if applicable)
            if ($reward['stock_quantity'] >= 0) {
                if ($reward['stock_quantity'] <= 0) {
                    throw new Exception('Reward out of stock');
                }
                
                // Decrease stock
                $update_stock_stmt = $this->pdo->prepare("UPDATE rewards_catalog SET stock_quantity = stock_quantity - 1 WHERE id = ?");
                $update_stock_stmt->execute([$reward_id]);
            }
            
            // Generate redemption code
            $redemption_code = $this->generateRedemptionCode();
            
            // Calculate expiry date
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$reward['expiry_days']} days"));
            
            // Create redeemed reward record
            $redeem_stmt = $this->pdo->prepare("
                INSERT INTO redeemed_rewards (user_id, reward_id, points_used, redemption_code, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $redeem_stmt->execute([$user_id, $reward_id, $reward['points_required'], $redemption_code, $expires_at]);
            
            // Deduct points
            $deduct_stmt = $this->pdo->prepare("
                INSERT INTO loyalty_points (user_id, points, transaction_type, source_type, source_id, description) 
                VALUES (?, ?, 'redeemed', 'reward', ?, ?)
            ");
            $deduct_stmt->execute([
                $user_id, 
                -$reward['points_required'], 
                $reward_id, 
                "Redeemed: {$reward['name']}"
            ]);
            
            // Update user's point totals
            $this->updateUserPoints($user_id);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'redemption_code' => $redemption_code,
                'expires_at' => $expires_at,
                'reward' => $reward
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process purchase points
     */
    public function processPurchasePoints($user_id, $amount, $order_id = null) {
        $points_per_dollar = $this->settings['points_per_dollar_spent'] ?? 10;
        $base_points = floor($amount * $points_per_dollar);
        
        // Get user's tier multiplier
        $user_stmt = $this->pdo->prepare("
            SELECT u.current_tier_id, lt.points_multiplier 
            FROM users u 
            JOIN loyalty_tiers lt ON u.current_tier_id = lt.id 
            WHERE u.id = ?
        ");
        $user_stmt->execute([$user_id]);
        $user_tier = $user_stmt->fetch();
        
        $multiplier = $user_tier['points_multiplier'] ?? 1.0;
        $final_points = floor($base_points * $multiplier);
        
        $description = "Purchase points for $" . number_format($amount, 2);
        if ($multiplier > 1.0) {
            $bonus_points = $final_points - $base_points;
            $description .= " (includes {$bonus_points} tier bonus points)";
        }
        
        return $this->awardPoints($user_id, $final_points, 'purchase', $order_id, $description);
    }
    
    /**
     * Process referral completion
     */
    public function processReferralCompletion($referral_code) {
        try {
            $this->pdo->beginTransaction();
            
            // Get referral details
            $referral_stmt = $this->pdo->prepare("
                SELECT * FROM referrals 
                WHERE referral_code = ? AND status = 'pending'
            ");
            $referral_stmt->execute([$referral_code]);
            $referral = $referral_stmt->fetch();
            
            if (!$referral) {
                throw new Exception('Referral not found or already completed');
            }
            
            $referrer_points = $this->settings['referral_points_referrer'] ?? 500;
            $referred_points = $this->settings['referral_points_referred'] ?? 250;
            
            // Award points to referrer
            $this->awardPoints(
                $referral['referrer_id'], 
                $referrer_points, 
                'referral', 
                $referral['id'], 
                'Referral bonus - friend joined SmartFix'
            );
            
            // Award points to referred user (if they have an account)
            if ($referral['referred_user_id']) {
                $this->awardPoints(
                    $referral['referred_user_id'], 
                    $referred_points, 
                    'referral', 
                    $referral['id'], 
                    'Welcome bonus - referred by friend'
                );
            }
            
            // Update referral status
            $update_stmt = $this->pdo->prepare("
                UPDATE referrals SET 
                    status = 'completed', 
                    referrer_points_awarded = ?, 
                    referred_points_awarded = ?, 
                    completed_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->execute([$referrer_points, $referred_points, $referral['id']]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error processing referral completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a referral
     */
    public function createReferral($referrer_id, $referred_email) {
        try {
            // Check if email is already referred by this user
            $check_stmt = $this->pdo->prepare("
                SELECT id FROM referrals 
                WHERE referrer_id = ? AND referred_email = ?
            ");
            $check_stmt->execute([$referrer_id, $referred_email]);
            
            if ($check_stmt->fetch()) {
                throw new Exception('You have already referred this email address');
            }
            
            // Generate unique referral code
            $referral_code = $this->generateReferralCode();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO referrals (referrer_id, referred_email, referral_code) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$referrer_id, $referred_email, $referral_code]);
            
            return [
                'success' => true,
                'referral_code' => $referral_code
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user's loyalty dashboard data
     */
    public function getUserLoyaltyData($user_id) {
        // Get user's current status
        $user_stmt = $this->pdo->prepare("
            SELECT u.total_points, u.available_points, u.current_tier_id, u.referral_code,
                   lt.name as tier_name, lt.color_code, lt.icon, lt.discount_percentage,
                   lt.points_multiplier, lt.benefits, lt.description
            FROM users u
            JOIN loyalty_tiers lt ON u.current_tier_id = lt.id
            WHERE u.id = ?
        ");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch();
        
        // Get next tier info
        $next_tier_stmt = $this->pdo->prepare("
            SELECT * FROM loyalty_tiers 
            WHERE min_points > ? 
            ORDER BY min_points ASC 
            LIMIT 1
        ");
        $next_tier_stmt->execute([$user_data['total_points']]);
        $next_tier = $next_tier_stmt->fetch();
        
        // Get recent transactions
        $transactions_stmt = $this->pdo->prepare("
            SELECT * FROM loyalty_points 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $transactions_stmt->execute([$user_id]);
        $recent_transactions = $transactions_stmt->fetchAll();
        
        // Get available rewards
        $rewards_stmt = $this->pdo->prepare("
            SELECT * FROM rewards_catalog 
            WHERE is_active = 1 AND min_tier_required <= ? 
            ORDER BY points_required ASC
        ");
        $rewards_stmt->execute([$user_data['current_tier_id']]);
        $available_rewards = $rewards_stmt->fetchAll();
        
        // Get redeemed rewards
        $redeemed_stmt = $this->pdo->prepare("
            SELECT rr.*, rc.name as reward_name, rc.description as reward_description
            FROM redeemed_rewards rr
            JOIN rewards_catalog rc ON rr.reward_id = rc.id
            WHERE rr.user_id = ?
            ORDER BY rr.created_at DESC
            LIMIT 5
        ");
        $redeemed_stmt->execute([$user_id]);
        $redeemed_rewards = $redeemed_stmt->fetchAll();
        
        // Get referral stats
        $referral_stats_stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_referrals,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals,
                SUM(referrer_points_awarded) as total_referral_points
            FROM referrals 
            WHERE referrer_id = ?
        ");
        $referral_stats_stmt->execute([$user_id]);
        $referral_stats = $referral_stats_stmt->fetch();
        
        return [
            'user' => $user_data,
            'next_tier' => $next_tier,
            'recent_transactions' => $recent_transactions,
            'available_rewards' => $available_rewards,
            'redeemed_rewards' => $redeemed_rewards,
            'referral_stats' => $referral_stats
        ];
    }
    
    /**
     * Update user's point totals
     */
    private function updateUserPoints($user_id) {
        // Calculate total points earned
        $total_stmt = $this->pdo->prepare("
            SELECT SUM(CASE WHEN transaction_type = 'earned' THEN points ELSE 0 END) as total_earned,
                   SUM(CASE WHEN transaction_type = 'redeemed' THEN ABS(points) ELSE 0 END) as total_redeemed
            FROM loyalty_points 
            WHERE user_id = ?
        ");
        $total_stmt->execute([$user_id]);
        $totals = $total_stmt->fetch();
        
        $total_points = $totals['total_earned'] ?? 0;
        
        // Calculate available points (excluding expired)
        $available_stmt = $this->pdo->prepare("
            SELECT SUM(points) as available_points
            FROM loyalty_points 
            WHERE user_id = ? 
            AND (expiry_date IS NULL OR expiry_date > CURDATE())
        ");
        $available_stmt->execute([$user_id]);
        $available_result = $available_stmt->fetch();
        $available_points = max(0, $available_result['available_points'] ?? 0);
        
        // Update user record
        $update_stmt = $this->pdo->prepare("
            UPDATE users SET 
                total_points = ?, 
                available_points = ? 
            WHERE id = ?
        ");
        $update_stmt->execute([$total_points, $available_points, $user_id]);
    }
    
    /**
     * Check and update user's tier
     */
    private function checkTierUpgrade($user_id) {
        $user_stmt = $this->pdo->prepare("SELECT total_points, current_tier_id FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();
        
        if (!$user) return;
        
        // Find appropriate tier
        $tier_stmt = $this->pdo->prepare("
            SELECT id FROM loyalty_tiers 
            WHERE min_points <= ? AND (max_points IS NULL OR max_points >= ?)
            ORDER BY min_points DESC 
            LIMIT 1
        ");
        $tier_stmt->execute([$user['total_points'], $user['total_points']]);
        $new_tier = $tier_stmt->fetch();
        
        if ($new_tier && $new_tier['id'] != $user['current_tier_id']) {
            $update_stmt = $this->pdo->prepare("
                UPDATE users SET 
                    current_tier_id = ?, 
                    tier_updated_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->execute([$new_tier['id'], $user_id]);
            
            // Award tier upgrade bonus (optional)
            if ($new_tier['id'] > $user['current_tier_id']) {
                $tier_name_stmt = $this->pdo->prepare("SELECT name FROM loyalty_tiers WHERE id = ?");
                $tier_name_stmt->execute([$new_tier['id']]);
                $tier_name = $tier_name_stmt->fetchColumn();
                
                $bonus_points = 100 * $new_tier['id']; // Bonus based on tier level
                $this->awardPoints(
                    $user_id, 
                    $bonus_points, 
                    'bonus', 
                    null, 
                    "Tier upgrade bonus - Welcome to {$tier_name}!"
                );
            }
        }
    }
    
    /**
     * Generate unique redemption code
     */
    private function generateRedemptionCode() {
        do {
            $code = 'RW' . strtoupper(substr(uniqid(), -8));
            $stmt = $this->pdo->prepare("SELECT id FROM redeemed_rewards WHERE redemption_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        
        return $code;
    }
    
    /**
     * Generate unique referral code
     */
    private function generateReferralCode() {
        do {
            $code = 'REF' . strtoupper(substr(uniqid(), -7));
            $stmt = $this->pdo->prepare("SELECT id FROM referrals WHERE referral_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        
        return $code;
    }
    
    /**
     * Expire old points
     */
    public function expireOldPoints() {
        try {
            $this->pdo->beginTransaction();
            
            // Find expired points
            $expired_stmt = $this->pdo->prepare("
                SELECT user_id, SUM(points) as expired_points
                FROM loyalty_points 
                WHERE transaction_type = 'earned' 
                AND expiry_date < CURDATE() 
                AND points > 0
                GROUP BY user_id
            ");
            $expired_stmt->execute();
            $expired_users = $expired_stmt->fetchAll();
            
            foreach ($expired_users as $user) {
                // Create expiry transaction
                $expire_stmt = $this->pdo->prepare("
                    INSERT INTO loyalty_points (user_id, points, transaction_type, source_type, description) 
                    VALUES (?, ?, 'expired', 'admin', 'Points expired')
                ");
                $expire_stmt->execute([$user['user_id'], -$user['expired_points']]);
                
                // Update user totals
                $this->updateUserPoints($user['user_id']);
            }
            
            $this->pdo->commit();
            return count($expired_users);
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Error expiring points: " . $e->getMessage());
            return false;
        }
    }
}
?>