<?php
session_start();
include('../includes/db.php');
include('../includes/LoyaltyManager.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=user/loyalty_dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$loyalty = new LoyaltyManager($pdo);
$loyalty_data = $loyalty->getUserLoyaltyData($user_id);

// Handle referral form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refer_email'])) {
    $refer_email = trim($_POST['refer_email']);
    $referral_result = $loyalty->createReferral($user_id, $refer_email);
    
    if ($referral_result['success']) {
        $success_message = "Referral sent successfully! Your friend will receive an invitation.";
    } else {
        $error_message = $referral_result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Program - SmartFix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .loyalty-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .loyalty-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .tier-card {
            background: linear-gradient(135deg, <?php echo $loyalty_data['user']['color_code']; ?>, <?php echo $loyalty_data['user']['color_code']; ?>dd);
            color: white;
        }

        .tier-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: rotate(45deg);
        }

        .tier-info {
            position: relative;
            z-index: 2;
        }

        .tier-badge {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .tier-icon {
            font-size: 48px;
            opacity: 0.9;
        }

        .tier-details h2 {
            font-size: 32px;
            margin-bottom: 5px;
        }

        .tier-details p {
            opacity: 0.9;
            font-size: 16px;
        }

        .points-card {
            text-align: center;
        }

        .points-display {
            margin-bottom: 30px;
        }

        .points-number {
            font-size: 48px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .points-label {
            color: #666;
            font-size: 18px;
        }

        .progress-section {
            margin-top: 30px;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        .benefits-list {
            list-style: none;
            margin-top: 20px;
        }

        .benefits-list li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.9);
        }

        .benefits-list i {
            color: rgba(255,255,255,0.7);
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h3 {
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .reward-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }

        .reward-card:hover {
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,123,255,0.15);
        }

        .reward-card.affordable {
            border-color: #28a745;
            background: #f8fff9;
        }

        .reward-card.expensive {
            opacity: 0.6;
        }

        .reward-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .reward-points {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .reward-card.affordable .reward-points {
            background: #28a745;
        }

        .reward-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .reward-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .redeem-btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .redeem-btn:hover:not(:disabled) {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .redeem-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .redeem-btn.affordable {
            background: #28a745;
        }

        .redeem-btn.affordable:hover {
            background: #1e7e34;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .transactions-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .points-earned {
            color: #28a745;
            font-weight: bold;
        }

        .points-redeemed {
            color: #dc3545;
            font-weight: bold;
        }

        .referral-section {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .referral-form {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .referral-form input {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
        }

        .referral-form button {
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .referral-form button:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
        }

        .referral-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .loyalty-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .referral-form {
                flex-direction: column;
            }
            
            .referral-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-crown"></i> Loyalty Program</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="loyalty-grid">
            <!-- Current Tier Card -->
            <div class="loyalty-card tier-card">
                <div class="tier-info">
                    <div class="tier-badge">
                        <i class="<?php echo $loyalty_data['user']['icon']; ?> tier-icon"></i>
                        <div class="tier-details">
                            <h2><?php echo $loyalty_data['user']['tier_name']; ?> Member</h2>
                            <p><?php echo $loyalty_data['user']['discount_percentage']; ?>% Discount • <?php echo number_format($loyalty_data['user']['points_multiplier'], 2); ?>x Points</p>
                        </div>
                    </div>
                    
                    <p style="margin-bottom: 20px; opacity: 0.9;">
                        <?php echo $loyalty_data['user']['description']; ?>
                    </p>
                    
                    <ul class="benefits-list">
                        <?php 
                        $benefits = json_decode($loyalty_data['user']['benefits'], true);
                        foreach ($benefits as $benefit): 
                        ?>
                            <li><i class="fas fa-check"></i> <?php echo $benefit; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Points Card -->
            <div class="loyalty-card points-card">
                <div class="points-display">
                    <div class="points-number"><?php echo number_format($loyalty_data['user']['available_points']); ?></div>
                    <div class="points-label">Available Points</div>
                </div>
                
                <div style="text-align: left;">
                    <p><strong>Total Earned:</strong> <?php echo number_format($loyalty_data['user']['total_points']); ?> points</p>
                </div>

                <?php if ($loyalty_data['next_tier']): ?>
                <div class="progress-section">
                    <h4>Progress to <?php echo $loyalty_data['next_tier']['name']; ?></h4>
                    <?php 
                    $current_points = $loyalty_data['user']['total_points'];
                    $next_tier_points = $loyalty_data['next_tier']['min_points'];
                    $progress = min(100, ($current_points / $next_tier_points) * 100);
                    $points_needed = $next_tier_points - $current_points;
                    ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span><?php echo number_format($current_points); ?> points</span>
                        <span><?php echo number_format($points_needed); ?> points to go</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Referral Program -->
        <div class="referral-section">
            <h3><i class="fas fa-users"></i> Refer Friends & Earn Points</h3>
            <p>Invite your friends to SmartFix and earn <?php echo $loyalty_data['referral_stats']['total_referral_points'] ?? 500; ?> points for each successful referral!</p>
            
            <form method="POST" class="referral-form">
                <input type="email" name="refer_email" placeholder="Enter your friend's email address" required>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Send Invitation
                </button>
            </form>
            
            <div class="referral-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $loyalty_data['referral_stats']['total_referrals'] ?? 0; ?></div>
                    <div class="stat-label">Total Referrals</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $loyalty_data['referral_stats']['completed_referrals'] ?? 0; ?></div>
                    <div class="stat-label">Successful</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($loyalty_data['referral_stats']['total_referral_points'] ?? 0); ?></div>
                    <div class="stat-label">Points Earned</div>
                </div>
            </div>
            
            <p style="margin-top: 20px; opacity: 0.9;">
                <strong>Your Referral Code:</strong> <?php echo $loyalty_data['user']['referral_code']; ?>
            </p>
        </div>

        <!-- Available Rewards -->
        <div class="section">
            <h3><i class="fas fa-gift"></i> Available Rewards</h3>
            <div class="rewards-grid">
                <?php foreach ($loyalty_data['available_rewards'] as $reward): ?>
                    <?php 
                    $affordable = $loyalty_data['user']['available_points'] >= $reward['points_required'];
                    $card_class = $affordable ? 'affordable' : ($loyalty_data['user']['available_points'] < $reward['points_required'] ? 'expensive' : '');
                    ?>
                    <div class="reward-card <?php echo $card_class; ?>">
                        <div class="reward-header">
                            <div class="reward-points"><?php echo number_format($reward['points_required']); ?> pts</div>
                        </div>
                        <h4 class="reward-title"><?php echo htmlspecialchars($reward['name']); ?></h4>
                        <p class="reward-description"><?php echo htmlspecialchars($reward['description']); ?></p>
                        <button class="redeem-btn <?php echo $affordable ? 'affordable' : ''; ?>" 
                                <?php echo !$affordable ? 'disabled' : ''; ?>
                                onclick="redeemReward(<?php echo $reward['id']; ?>)">
                            <?php if ($affordable): ?>
                                <i class="fas fa-gift"></i> Redeem Now
                            <?php else: ?>
                                <i class="fas fa-lock"></i> Need <?php echo number_format($reward['points_required'] - $loyalty_data['user']['available_points']); ?> More Points
                            <?php endif; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="section">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Points</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loyalty_data['recent_transactions'] as $transaction): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                            <td class="<?php echo $transaction['points'] > 0 ? 'points-earned' : 'points-redeemed'; ?>">
                                <?php echo $transaction['points'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['points']); ?>
                            </td>
                            <td>
                                <span style="text-transform: capitalize;">
                                    <?php echo $transaction['transaction_type']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function redeemReward(rewardId) {
        if (confirm('Are you sure you want to redeem this reward?')) {
            fetch('redeem_reward.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reward_id: rewardId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reward redeemed successfully! Your redemption code is: ' + data.redemption_code);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error redeeming reward');
            });
        }
    }
    </script>
</body>
</html>