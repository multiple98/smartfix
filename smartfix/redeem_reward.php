<?php
session_start();
include('includes/db.php');
include('includes/LoyaltyManager.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please login to redeem rewards';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $_SESSION['user_id'];
        $reward_id = (int)$input['reward_id'];
        
        $loyalty = new LoyaltyManager($pdo);
        $result = $loyalty->redeemReward($user_id, $reward_id);
        
        if ($result['success']) {
            $response['success'] = true;
            $response['redemption_code'] = $result['redemption_code'];
            $response['expires_at'] = $result['expires_at'];
            $response['message'] = 'Reward redeemed successfully!';
        } else {
            $response['message'] = $result['message'];
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error processing redemption: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>