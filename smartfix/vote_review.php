<?php
session_start();
include('includes/db.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please login to vote on reviews';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $user_id = $_SESSION['user_id'];
        $review_id = (int)$input['review_id'];
        $vote_type = $input['vote_type'];
        
        // Validate vote type
        if (!in_array($vote_type, ['helpful', 'not_helpful'])) {
            throw new Exception('Invalid vote type');
        }
        
        // Check if review exists
        $review_stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND status = 'approved'");
        $review_stmt->execute([$review_id]);
        if (!$review_stmt->fetch()) {
            throw new Exception('Review not found');
        }
        
        // Check if user has already voted on this review
        $existing_vote_stmt = $pdo->prepare("SELECT vote_type FROM review_votes WHERE review_id = ? AND user_id = ?");
        $existing_vote_stmt->execute([$review_id, $user_id]);
        $existing_vote = $existing_vote_stmt->fetch();
        
        if ($existing_vote) {
            if ($existing_vote['vote_type'] === $vote_type) {
                // User is trying to vote the same way again - remove the vote
                $delete_stmt = $pdo->prepare("DELETE FROM review_votes WHERE review_id = ? AND user_id = ?");
                $delete_stmt->execute([$review_id, $user_id]);
                $response['message'] = 'Vote removed';
            } else {
                // User is changing their vote
                $update_stmt = $pdo->prepare("UPDATE review_votes SET vote_type = ? WHERE review_id = ? AND user_id = ?");
                $update_stmt->execute([$vote_type, $review_id, $user_id]);
                $response['message'] = 'Vote updated';
            }
        } else {
            // New vote
            $insert_stmt = $pdo->prepare("INSERT INTO review_votes (review_id, user_id, vote_type) VALUES (?, ?, ?)");
            $insert_stmt->execute([$review_id, $user_id, $vote_type]);
            $response['message'] = 'Vote recorded';
        }
        
        // Update helpful count in reviews table
        $update_helpful_stmt = $pdo->prepare("
            UPDATE reviews SET 
                helpful_count = (SELECT COUNT(*) FROM review_votes WHERE review_id = ? AND vote_type = 'helpful')
            WHERE id = ?
        ");
        $update_helpful_stmt->execute([$review_id, $review_id]);
        
        $response['success'] = true;
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
}

echo json_encode($response);
?>