<?php
session_start();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0) {
        $response['message'] = 'Invalid product ID.';
    } else if ($quantity <= 0) {
        $response['message'] = 'Invalid quantity.';
    } else {
        // Add to cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
        
        $response['success'] = true;
        $response['message'] = 'Item added to cart successfully!';
        $response['cart_count'] = array_sum($_SESSION['cart']);
    }
}

// Return JSON response for AJAX calls
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For non-AJAX calls, redirect back
if ($response['success']) {
    $_SESSION['cart_message'] = $response['message'];
    $_SESSION['cart_message_type'] = 'success';
} else {
    $_SESSION['cart_message'] = $response['message'];
    $_SESSION['cart_message_type'] = 'danger';
}

$redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'shop.php';
header("Location: $redirect");
exit;
?>