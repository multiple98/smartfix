<?php
session_start();
include('includes/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $service_request_id = !empty($_POST['service_request_id']) ? (int)$_POST['service_request_id'] : null;
        $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $technician_id = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
        $rating = (int)$_POST['rating'];
        $title = trim($_POST['title']);
        $comment = trim($_POST['comment']);
        $pros = trim($_POST['pros'] ?? '');
        $cons = trim($_POST['cons'] ?? '');
        $would_recommend = isset($_POST['would_recommend']) ? 1 : 0;
        $category_ratings = $_POST['category_ratings'] ?? [];
        
        // Validation
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Invalid rating value');
        }
        
        if (empty($title) || empty($comment)) {
            throw new Exception('Title and comment are required');
        }
        
        if (!$service_request_id && !$product_id && !$technician_id) {
            throw new Exception('Invalid review target');
        }
        
        // Check if user has already reviewed this item
        $check_query = "SELECT id FROM reviews WHERE user_id = ?";
        $check_params = [$user_id];
        
        if ($service_request_id) {
            $check_query .= " AND service_request_id = ?";
            $check_params[] = $service_request_id;
        } elseif ($product_id) {
            $check_query .= " AND product_id = ?";
            $check_params[] = $product_id;
        } elseif ($technician_id) {
            $check_query .= " AND technician_id = ?";
            $check_params[] = $technician_id;
        }
        
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute($check_params);
        
        if ($check_stmt->fetch()) {
            throw new Exception('You have already reviewed this item');
        }
        
        // Verify purchase/service for verified badge
        $verified_purchase = false;
        if ($service_request_id) {
            $verify_stmt = $pdo->prepare("SELECT id FROM service_requests WHERE id = ? AND user_id = ? AND status = 'completed'");
            $verify_stmt->execute([$service_request_id, $user_id]);
            $verified_purchase = (bool)$verify_stmt->fetch();
        } elseif ($product_id) {
            $verify_stmt = $pdo->prepare("
                SELECT oi.id FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE oi.product_id = ? AND o.user_id = ? AND o.status = 'completed'
            ");
            $verify_stmt->execute([$product_id, $user_id]);
            $verified_purchase = (bool)$verify_stmt->fetch();
        }
        
        // Handle image uploads
        $uploaded_images = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = 'uploads/reviews/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            $max_images = 5;
            
            for ($i = 0; $i < min(count($_FILES['images']['name']), $max_images); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['images']['type'][$i];
                    $file_size = $_FILES['images']['size'][$i];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        continue; // Skip invalid file types
                    }
                    
                    if ($file_size > $max_file_size) {
                        continue; // Skip files that are too large
                    }
                    
                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $file_name = 'review_' . uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $file_path)) {
                        $uploaded_images[] = $file_name;
                    }
                }
            }
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert main review
        $insert_query = "
            INSERT INTO reviews (
                user_id, service_request_id, product_id, technician_id, 
                rating, title, comment, pros, cons, would_recommend, 
                verified_purchase, images, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
        ";
        
        $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;
        
        $stmt = $pdo->prepare($insert_query);
        $stmt->execute([
            $user_id, $service_request_id, $product_id, $technician_id,
            $rating, $title, $comment, $pros, $cons, $would_recommend,
            $verified_purchase, $images_json
        ]);
        
        $review_id = $pdo->lastInsertId();
        
        // Insert category ratings
        if (!empty($category_ratings)) {
            $category_stmt = $pdo->prepare("
                INSERT INTO review_category_ratings (review_id, category_id, rating) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($category_ratings as $category_id => $category_rating) {
                if ($category_rating >= 1 && $category_rating <= 5) {
                    $category_stmt->execute([$review_id, $category_id, $category_rating]);
                }
            }
        }
        
        // Update aggregate ratings
        if ($technician_id) {
            // Update technician ratings
            $update_tech_query = "
                UPDATE technicians SET 
                    rating = (SELECT AVG(rating) FROM reviews WHERE technician_id = ? AND status = 'approved'),
                    review_count = (SELECT COUNT(*) FROM reviews WHERE technician_id = ? AND status = 'approved'),
                    last_reviewed_at = NOW()
                WHERE id = ?
            ";
            $pdo->prepare($update_tech_query)->execute([$technician_id, $technician_id, $technician_id]);
            
            // Update detailed category ratings for technician
            $categories = ['service_quality_rating', 'timeliness_rating', 'communication_rating', 'professionalism_rating'];
            $category_ids = [1, 2, 3, 4]; // Assuming these are the category IDs
            
            for ($i = 0; $i < count($categories); $i++) {
                $avg_query = "
                    SELECT AVG(rcr.rating) as avg_rating
                    FROM review_category_ratings rcr
                    JOIN reviews r ON rcr.review_id = r.id
                    WHERE r.technician_id = ? AND rcr.category_id = ? AND r.status = 'approved'
                ";
                $avg_stmt = $pdo->prepare($avg_query);
                $avg_stmt->execute([$technician_id, $category_ids[$i]]);
                $avg_result = $avg_stmt->fetch();
                
                if ($avg_result && $avg_result['avg_rating']) {
                    $update_category_query = "UPDATE technicians SET {$categories[$i]} = ? WHERE id = ?";
                    $pdo->prepare($update_category_query)->execute([$avg_result['avg_rating'], $technician_id]);
                }
            }
        }
        
        if ($product_id) {
            // Update product ratings
            $update_product_query = "
                UPDATE products SET 
                    average_rating = (SELECT AVG(rating) FROM reviews WHERE product_id = ? AND status = 'approved'),
                    review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'approved'),
                    last_reviewed_at = NOW()
                WHERE id = ?
            ";
            $pdo->prepare($update_product_query)->execute([$product_id, $product_id, $product_id]);
        }
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = 'Review submitted successfully!';
        
        // Redirect back to the referring page or dashboard
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'user/dashboard.php';
        header("Location: $redirect_url?review_success=1");
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        
        // Clean up uploaded images on error
        if (!empty($uploaded_images)) {
            foreach ($uploaded_images as $image) {
                $file_path = 'uploads/reviews/' . $image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
        
        $response['message'] = $e->getMessage();
        
        // Redirect back with error
        $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'user/dashboard.php';
        header("Location: $redirect_url?review_error=" . urlencode($e->getMessage()));
        exit;
    }
}

// If not POST request, redirect to dashboard
header('Location: user/dashboard.php');
exit;
?>