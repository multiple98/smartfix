<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('includes/db.php'); // Make sure this file connects to your DB

// Check for cart messages
$cart_message = '';
$cart_message_type = '';

if (isset($_SESSION['cart_message'])) {
    $cart_message = $_SESSION['cart_message'];
    $cart_message_type = $_SESSION['cart_message_type'];
    unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>SmartFix Shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --bg-color: #f5f5f5;
      --text-color: #333;
      --card-bg: #fff;
      --header-bg: #004080;
      --header-text: #fff;
      --accent-color: #007BFF;
      --accent-hover: #0056b3;
      --shadow-color: rgba(0,0,0,0.1);
    }
    
    .dark-mode {
      --bg-color: #121212;
      --text-color: #f5f5f5;
      --card-bg: #1e1e1e;
      --header-bg: #002040;
      --header-text: #f5f5f5;
      --accent-color: #0d6efd;
      --accent-hover: #0b5ed7;
      --shadow-color: rgba(0,0,0,0.3);
    }
    
    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg-color);
      color: var(--text-color);
      padding: 0;
      margin: 0;
      transition: background-color 0.3s ease;
    }
    
    .content-wrapper {
      padding: 30px;
    }
    
    h1 {
      text-align: center;
      margin-bottom: 20px;
    }
    
    /* Slider/Banner Styles */
    .slider-container {
      position: relative;
      width: 100%;
      height: 400px;
      overflow: hidden;
      margin-bottom: 40px;
    }
    
    .slider {
      display: flex;
      width: 300%;
      height: 100%;
      transition: transform 0.8s ease;
    }
    
    .slide {
      width: 33.33%;
      height: 100%;
      position: relative;
      background-size: cover;
      background-position: center;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }
    
    .slide-content {
      background-color: rgba(0, 0, 0, 0.5);
      padding: 30px;
      border-radius: 10px;
      max-width: 80%;
    }
    
    .slide h2 {
      font-size: 2.5rem;
      margin-bottom: 15px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .slide p {
      font-size: 1.2rem;
      margin-bottom: 20px;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .slide-btn {
      display: inline-block;
      padding: 12px 25px;
      background-color: #007BFF;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }
    
    .slide-btn:hover {
      background-color: #0056b3;
      transform: scale(1.05);
    }
    
    .slider-nav {
      position: absolute;
      bottom: 20px;
      left: 0;
      right: 0;
      display: flex;
      justify-content: center;
      gap: 10px;
    }
    .slider-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: rgba(255, 255, 255, 0.5);
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .slider-dot.active {
      background-color: white;
      transform: scale(1.2);
    }
    
    .slider-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 50px;
      height: 50px;
      background-color: rgba(0, 0, 0, 0.3);
      color: white;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.5rem;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 10;
    }
    
    .slider-arrow:hover {
      background-color: rgba(0, 0, 0, 0.6);
    }
    
    .slider-arrow.prev {
      left: 20px;
    }
    
    .slider-arrow.next {
      right: 20px;
    }
    
    /* Featured Products Section */
    .featured-section {
      background-color: white;
      padding: 40px 0;
      margin-bottom: 40px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .featured-title {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .featured-title h2 {
      font-size: 2rem;
      color: #333;
      margin-bottom: 10px;
    }
    
    .featured-title p {
      color: #666;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .featured-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 30px;
    }
    
    /* Shop Grid */
    .shop-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 25px;
      max-width: 1200px;
      margin: auto;
    }
    .shop-item {
      background: var(--card-bg);
      border-radius: 12px;
      box-shadow: 0 4px 12px var(--shadow-color);
      padding: 20px;
      text-align: center;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    
    .shop-item img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 8px;
    }
    
    .shop-item h3 {
      margin-top: 15px;
      font-size: 20px;
      height: 50px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      box-orient: vertical;
    }
    
    .shop-item p {
      margin: 10px 0;
      color: var(--text-color);
      flex-grow: 1;
    }
    
    .shop-item button {
      background-color: var(--accent-color);
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .shop-item button:hover {
      background-color: var(--accent-hover);
    }
    
    /* Animation for new items */
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    .new-badge {
      animation: pulse 2s infinite;
    }
    
    /* Updated Products Window Styles */
    .updated-products-window {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 30px;
      margin-bottom: 40px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      border: 1px solid #e9ecef;
    }
    
    .window-header {
      text-align: center;
      margin-bottom: 25px;
    }
    
    .window-header h2 {
      color: #333;
      font-size: 1.8rem;
      margin-bottom: 8px;
      position: relative;
      display: inline-block;
    }
    
    .window-header h2:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background: linear-gradient(90deg, #007BFF, #00c6ff);
      border-radius: 3px;
    }
    
    .window-header p {
      color: #6c757d;
      font-size: 1rem;
    }
    
    .window-display {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }
    
    .window-product {
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
    }
    
    .window-product:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .product-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      z-index: 10;
    }
    
    .badge {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .badge.new {
      background-color: #28a745;
      color: white;
      animation: pulse 2s infinite;
    }
    
    .badge.updated {
      background-color: #007BFF;
      color: white;
    }
    
    .window-product-image {
      height: 160px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background-color: #f8f9fa;
    }
    
    .window-product-image img {
      max-width: 100%;
      max-height: 150px;
      object-fit: contain;
      transition: transform 0.3s ease;
    }
    
    .window-product:hover .window-product-image img {
      transform: scale(1.08);
    }
    
    .no-image {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #adb5bd;
      font-size: 2rem;
    }
    
    .window-product-info {
      padding: 15px;
    }
    
    .window-product-info h3 {
      font-size: 1rem;
      margin-bottom: 8px;
      color: #333;
      height: 40px;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      box-orient: vertical;
    }
    
    .window-product-info .price {
      font-weight: bold;
      color: #007BFF;
      margin-bottom: 12px;
    }
    
    .btn-view {
      display: inline-block;
      background-color: #007BFF;
      color: white;
      padding: 8px 15px;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.85rem;
      transition: background-color 0.3s ease;
      text-align: center;
      width: 100%;
    }
    
    .btn-view:hover {
      background-color: #0056b3;
    }
    
    .no-products {
      grid-column: 1 / -1;
      text-align: center;
      padding: 30px;
      color: #6c757d;
      font-style: italic;
    }
    
    .error {
      grid-column: 1 / -1;
      text-align: center;
      padding: 20px;
      color: #dc3545;
      background-color: rgba(220, 53, 69, 0.1);
      border-radius: 4px;
    }
    
    @media (max-width: 768px) {
      .window-display {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      }
      
      .window-product-image {
        height: 140px;
      }
    }
    /* Quick View Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1050;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .modal.show {
      display: block;
      opacity: 1;
    }
    
    .modal-content {
      background-color: var(--card-bg);
      margin: 10% auto;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 5px 15px var(--shadow-color);
      width: 80%;
      max-width: 800px;
      position: relative;
      transform: translateY(-20px);
      transition: transform 0.3s ease;
    }
    
    .modal.show .modal-content {
      transform: translateY(0);
    }
    
    .close-modal {
      position: absolute;
      top: 15px;
      right: 15px;
      font-size: 24px;
      cursor: pointer;
      color: var(--text-color);
      transition: color 0.3s ease;
    }
    
    .close-modal:hover {
      color: var(--accent-color);
    }
    
    .product-quick-view {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
    }
    
    .product-quick-view-image {
      flex: 1;
      min-width: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .product-quick-view-image img {
      max-width: 100%;
      max-height: 300px;
      object-fit: contain;
      border-radius: 8px;
    }
    
    .product-quick-view-details {
      flex: 1;
      min-width: 300px;
    }
    
    .product-quick-view-details h2 {
      margin-top: 0;
      color: var(--text-color);
      margin-bottom: 15px;
    }
    
    .product-quick-view-price {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--accent-color);
      margin-bottom: 15px;
    }
    
    .product-quick-view-description {
      color: var(--text-color);
      margin-bottom: 20px;
      line-height: 1.6;
    }
    
    .product-quick-view-actions {
      display: flex;
      gap: 10px;
    }
    
    /* Rating and Review System */
    .rating {
      display: inline-flex;
      margin-bottom: 10px;
    }
    
    .rating-star {
      color: #ffc107;
      font-size: 1rem;
      margin-right: 2px;
    }
    
    .rating-count {
      color: var(--text-color);
      font-size: 0.8rem;
      margin-left: 5px;
      opacity: 0.7;
    }
    
    .reviews-section {
      margin-top: 20px;
      border-top: 1px solid var(--shadow-color);
      padding-top: 20px;
    }
    
    .reviews-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .reviews-title {
      font-size: 1.2rem;
      color: var(--text-color);
      margin: 0;
    }
    
    .write-review-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 4px;
      padding: 5px 10px;
      font-size: 0.9rem;
      cursor: pointer;
    }
    
    .write-review-btn:hover {
      background-color: var(--accent-hover);
    }
    
    .review-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }
    
    .review-item {
      background-color: var(--bg-color);
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 2px 8px var(--shadow-color);
    }
    
    .review-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    
    .reviewer-name {
      font-weight: bold;
      color: var(--text-color);
    }
    
    .review-date {
      font-size: 0.8rem;
      color: var(--text-color);
      opacity: 0.7;
    }
    
    .review-rating {
      margin-bottom: 10px;
    }
    
    .review-content {
      color: var(--text-color);
      line-height: 1.5;
    }
    
    .review-form {
      background-color: var(--bg-color);
      border-radius: 8px;
      padding: 15px;
      margin-top: 15px;
      box-shadow: 0 2px 8px var(--shadow-color);
      display: none;
    }
    
    .review-form.show {
      display: block;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      color: var(--text-color);
    }
    
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid var(--shadow-color);
      border-radius: 4px;
      background-color: var(--card-bg);
      color: var(--text-color);
    }
    
    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    .star-rating {
      display: flex;
      flex-direction: row-reverse;
      justify-content: flex-end;
    }
    
    .star-rating input {
      display: none;
    }
    
    .star-rating label {
      cursor: pointer;
      color: #ccc;
      font-size: 1.5rem;
      padding: 0 2px;
      transition: color 0.3s ease;
    }
    
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
      color: #ffc107;
    }
    
    .submit-review {
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 4px;
      padding: 8px 15px;
      cursor: pointer;
    }
    
    .submit-review:hover {
      background-color: var(--accent-hover);
    }
    
    .quick-view-btn {
      position: absolute;
      top: 10px;
      left: 10px;
      background-color: rgba(0,0,0,0.7);
      color: white;
      border: none;
      border-radius: 4px;
      padding: 5px 10px;
      font-size: 0.8rem;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 5;
    }
    
    .shop-item:hover .quick-view-btn {
      opacity: 1;
    }
    
    /* Compare Products Feature */
    .compare-container {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background-color: var(--card-bg);
      box-shadow: 0 -5px 15px var(--shadow-color);
      padding: 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 900;
      transform: translateY(100%);
      transition: transform 0.3s ease;
    }
    
    .compare-container.show {
      transform: translateY(0);
    }
    
    .compare-items {
      display: flex;
      gap: 15px;
      flex-grow: 1;
      overflow-x: auto;
      padding-bottom: 10px;
    }
    
    .compare-item {
      background-color: var(--bg-color);
      border-radius: 8px;
      padding: 10px;
      min-width: 150px;
      position: relative;
      box-shadow: 0 2px 8px var(--shadow-color);
    }
    
    .compare-item img {
      width: 100%;
      height: 80px;
      object-fit: contain;
      margin-bottom: 8px;
    }
    
    .compare-item-name {
      font-size: 0.9rem;
      font-weight: bold;
      margin-bottom: 5px;
      color: var(--text-color);
    }
    
    .compare-item-price {
      font-size: 0.85rem;
      color: var(--accent-color);
    }
    
    .remove-compare {
      position: absolute;
      top: 5px;
      right: 5px;
      background-color: rgba(0,0,0,0.1);
      color: var(--text-color);
      border: none;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      cursor: pointer;
    }
    
    .compare-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .compare-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 5px;
      padding: 10px 15px;
      cursor: pointer;
      font-weight: bold;
    }
    
    .compare-btn:hover {
      background-color: var(--accent-hover);
    }
    
    .clear-compare {
      background-color: transparent;
      color: var(--text-color);
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
    }
    
    .add-to-compare {
      position: absolute;
      bottom: 10px;
      right: 10px;
      background-color: rgba(0,0,0,0.7);
      color: white;
      border: none;
      border-radius: 4px;
      padding: 5px 10px;
      font-size: 0.8rem;
      cursor: pointer;
      opacity: 0;
      transition: opacity 0.3s ease;
      z-index: 5;
    }
    
    .shop-item:hover .add-to-compare {
      opacity: 1;
    }
    
    /* Wishlist Feature */
    .wishlist-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      font-size: 1.2rem;
      cursor: pointer;
      z-index: 5;
      transition: all 0.3s ease;
      filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));
    }
    
    .wishlist-btn:hover, .wishlist-btn.active {
      color: #ff6b6b;
      transform: scale(1.2);
    }
    
    .wishlist-container {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: var(--card-bg);
      border-radius: 10px;
      box-shadow: 0 5px 15px var(--shadow-color);
      width: 300px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.5s ease, padding 0.5s ease;
      z-index: 1000;
      padding: 0;
    }
    
    .wishlist-container.show {
      max-height: 400px;
      padding: 15px;
      overflow-y: auto;
    }
    
    .wishlist-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--shadow-color);
    }
    
    .wishlist-header h3 {
      margin: 0;
      color: var(--text-color);
    }
    
    .close-wishlist {
      background: none;
      border: none;
      color: var(--text-color);
      font-size: 1.2rem;
      cursor: pointer;
    }
    
    .wishlist-items {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    
    .wishlist-item {
      display: flex;
      gap: 10px;
      padding: 10px;
      background-color: var(--bg-color);
      border-radius: 5px;
      position: relative;
    }
    
    .wishlist-item img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 5px;
    }
    
    .wishlist-item-info {
      flex-grow: 1;
    }
    
    .wishlist-item-name {
      font-weight: bold;
      margin-bottom: 5px;
      color: var(--text-color);
    }
    
    .wishlist-item-price {
      color: var(--accent-color);
      font-size: 0.9rem;
    }
    
    .remove-wishlist {
      position: absolute;
      top: 5px;
      right: 5px;
      background: none;
      border: none;
      color: var(--text-color);
      font-size: 0.8rem;
      cursor: pointer;
      opacity: 0.7;
    }
    
    .remove-wishlist:hover {
      opacity: 1;
      color: #ff6b6b;
    }
    
    .wishlist-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
    }
    
    .clear-wishlist {
      background: none;
      border: none;
      color: var(--text-color);
      cursor: pointer;
      font-size: 0.9rem;
    }
    
    .wishlist-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      cursor: pointer;
      z-index: 999;
      box-shadow: 0 2px 10px var(--shadow-color);
      transition: all 0.3s ease;
    }
    
    .wishlist-toggle:hover {
      transform: scale(1.1);
      background-color: var(--accent-hover);
    }
    
    .wishlist-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: #ff6b6b;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
      font-weight: bold;
    }
    
    /* Dark Mode Toggle */
    .dark-mode-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: var(--accent-color);
      color: white;
      border: none;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.5rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      z-index: 1000;
      transition: all 0.3s ease;
    }
    
    .dark-mode-toggle:hover {
      transform: scale(1.1);
      background-color: var(--accent-hover);
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header style="background: var(--header-bg); color: var(--header-text); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div class="logo" style="font-size: 24px; font-weight: bold;">
      <i class="fas fa-tools"></i> SmartFix Shop
    </div>
    <nav style="display: flex; flex-wrap: wrap; align-items: center; gap: 20px;">
      <a href="index.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-home"></i> Home</a>
      <a href="services.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-tools"></i> Services</a>
      <a href="shop.php" style="color: #ffcc00; text-decoration: none; font-weight: 500; transition: 0.3s;"><i class="fas fa-shopping-cart"></i> Shop</a>
      <a href="about.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-info-circle"></i> About</a>
      <a href="contact.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-phone"></i> Contact</a>
      
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="user/dashboard.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-user"></i> Dashboard</a>
        <a href="logout.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-sign-out-alt"></i> Logout</a>
      <?php else: ?>
        <a href="login.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'"><i class="fas fa-sign-in-alt"></i> Login</a>
      <?php endif; ?>
      
      <a href="shop/cart.php" style="color: var(--header-text); text-decoration: none; font-weight: 500; transition: 0.3s; position: relative;" onmouseover="this.style.color='#ffcc00'" onmouseout="this.style.color='var(--header-text)'">
        <i class="fas fa-shopping-cart"></i> Cart
        <?php if ($cart_count > 0): ?>
          <span style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 12px; display: flex; align-items: center; justify-content: center;"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>
    </nav>
  </header>

  <!-- Cart Message -->
  <?php if ($cart_message): ?>
    <div style="background: <?php echo $cart_message_type == 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $cart_message_type == 'success' ? '#155724' : '#721c24'; ?>; padding: 15px; text-align: center; margin: 0; border-bottom: 1px solid <?php echo $cart_message_type == 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
      <i class="fas fa-<?php echo $cart_message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
      <?php echo htmlspecialchars($cart_message); ?>
    </div>
  <?php endif; ?>

  <div class="content-wrapper">
    <!-- Dark Mode Toggle Button -->
    <button class="dark-mode-toggle" id="darkModeToggle">
      <i class="fas fa-moon"></i>
    </button>

<!-- Slider/Banner Section -->
<div class="slider-container">
  <div class="slider" id="slider">
    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1603302576837-37561b2e2302?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1168&q=80');">
      <div class="slide-content">
        <h2>New Arrivals</h2>
        <p>Check out our latest smartphone accessories and repair kits!</p>
        <button class="slide-btn" onclick="scrollToProducts()">Shop Now</button>
      </div>
    </div>
    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1588508065123-287b28e013da?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');">
      <div class="slide-content">
        <h2>Special Offer</h2>
        <p>Get 15% off on all computer parts this week only!</p>
        <button class="slide-btn" onclick="filterCategory('Computer')">View Deals</button>
      </div>
    </div>
    <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1601406984081-44d85ce92f90?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');">
      <div class="slide-content">
        <h2>Spare Parts</h2>
        <p>Quality spare parts for all your repair needs</p>
        <button class="slide-btn" onclick="filterCategory('Spare')">Browse Parts</button>
      </div>
    </div>
  </div>
  
  <div class="slider-nav">
    <div class="slider-dot active" onclick="goToSlide(0)"></div>
    <div class="slider-dot" onclick="goToSlide(1)"></div>
    <div class="slider-dot" onclick="goToSlide(2)"></div>
  </div>
  
  <div class="slider-arrow prev" onclick="prevSlide()">
    <i class="fas fa-chevron-left"></i>
  </div>
  <div class="slider-arrow next" onclick="nextSlide()">
    <i class="fas fa-chevron-right"></i>
  </div>
</div>


  <!-- Advanced Filter and Sort Section -->
  <div class="filter-sort-container" style="margin-bottom: 30px; background-color: var(--card-bg); border-radius: 10px; padding: 20px; box-shadow: 0 4px 12px var(--shadow-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 15px;">
      <h3 style="margin: 0; color: var(--text-color);">Filter & Sort Products</h3>
      <button id="toggleFilters" style="background: none; border: none; color: var(--accent-color); cursor: pointer; font-size: 0.9rem;">
        <i class="fas fa-sliders-h"></i> Show Filters
      </button>
    </div>
    
    <div id="filterOptions" style="display: none; padding-top: 15px; border-top: 1px solid var(--shadow-color);">
      <form id="filterForm" method="get" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <!-- Price Range -->
        <div class="filter-group">
          <label for="priceRange" style="display: block; margin-bottom: 5px; color: var(--text-color);">Price Range</label>
          <div style="display: flex; align-items: center; gap: 10px;">
            <input type="number" id="minPrice" name="min_price" placeholder="Min" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: var(--bg-color); color: var(--text-color);">
            <span style="color: var(--text-color);">to</span>
            <input type="number" id="maxPrice" name="max_price" placeholder="Max" style="width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: var(--bg-color); color: var(--text-color);">
          </div>
        </div>
        
        <!-- Category Filter -->
        <div class="filter-group">
          <label for="category" style="display: block; margin-bottom: 5px; color: var(--text-color);">Category</label>
          <select id="category" name="category" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: var(--bg-color); color: var(--text-color);">
            <option value="">All Categories</option>
            <?php
            // Get unique categories
            try {
              $category_query = "SELECT DISTINCT category FROM products WHERE status = 'active' AND is_deleted = 0 ORDER BY category";
              $category_stmt = $pdo->prepare($category_query);
              $category_stmt->execute();
              $categories = $category_stmt->fetchAll(PDO::FETCH_COLUMN);
              
              foreach ($categories as $cat) {
                $selected = (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : '';
                echo "<option value=\"" . htmlspecialchars($cat) . "\" $selected>" . htmlspecialchars($cat) . "</option>";
              }
            } catch (PDOException $e) {
              // Fallback options if query fails
              $fallback_categories = ['Phone', 'Computer', 'Spare', 'Accessory'];
              foreach ($fallback_categories as $cat) {
                $selected = (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : '';
                echo "<option value=\"" . htmlspecialchars($cat) . "\" $selected>" . htmlspecialchars($cat) . "</option>";
              }
            }
            ?>
          </select>
        </div>
        
        <!-- Availability Filter -->
        <div class="filter-group">
          <label style="display: block; margin-bottom: 5px; color: var(--text-color);">Availability</label>
          <div style="display: flex; gap: 15px;">
            <label style="display: flex; align-items: center; gap: 5px; color: var(--text-color);">
              <input type="radio" name="availability" value="all" checked> All
            </label>
            <label style="display: flex; align-items: center; gap: 5px; color: var(--text-color);">
              <input type="radio" name="availability" value="in_stock" <?php echo (isset($_GET['availability']) && $_GET['availability'] === 'in_stock') ? 'checked' : ''; ?>> In Stock
            </label>
          </div>
        </div>
        
        <!-- Sort Options -->
        <div class="filter-group">
          <label for="sortBy" style="display: block; margin-bottom: 5px; color: var(--text-color);">Sort By</label>
          <select id="sortBy" name="sort" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: var(--bg-color); color: var(--text-color);">
            <option value="newest" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'selected' : ''; ?>>Newest First</option>
            <option value="price_low" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_high" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="name_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_asc') ? 'selected' : ''; ?>>Name: A to Z</option>
            <option value="name_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] === 'name_desc') ? 'selected' : ''; ?>>Name: Z to A</option>
          </select>
        </div>
        
        <!-- Search -->
        <div class="filter-group" style="grid-column: 1 / -1;">
          <label for="searchTerm" style="display: block; margin-bottom: 5px; color: var(--text-color);">Search Products</label>
          <div style="display: flex; gap: 10px;">
            <input type="text" id="searchTerm" name="search" placeholder="Search by name or description..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="flex-grow: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; background-color: var(--bg-color); color: var(--text-color);">
            <button type="submit" style="background-color: var(--accent-color); color: white; border: none; border-radius: 4px; padding: 0 15px; cursor: pointer;">
              <i class="fas fa-search"></i> Search
            </button>
            <button type="button" id="resetFilters" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 0 15px; cursor: pointer;">
              <i class="fas fa-undo"></i> Reset
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Recently Updated Products Window -->
  <div class="updated-products-window">
    <div class="window-header">
      <h2>New & Updated Products</h2>
      <p>Check out our latest additions and freshly updated items</p>
    </div>
    
    <div class="window-display">
      <?php
      // Get recently updated products
      try {
        // Check if status, is_deleted and updated_at columns exist
        $check_column = "SHOW COLUMNS FROM products LIKE 'status'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $status_exists = $column_stmt->rowCount() > 0;
        
        $check_column = "SHOW COLUMNS FROM products LIKE 'is_deleted'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $is_deleted_exists = $column_stmt->rowCount() > 0;
        
        $check_column = "SHOW COLUMNS FROM products LIKE 'updated_at'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $updated_at_exists = $column_stmt->rowCount() > 0;
        
        // Check if is_new column exists
        $check_column = "SHOW COLUMNS FROM products LIKE 'is_new'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $is_new_exists = $column_stmt->rowCount() > 0;
        
        if ($status_exists && $is_deleted_exists && $updated_at_exists) {
          if ($is_new_exists) {
            // First try to get products marked as new
            $updated_query = "SELECT * FROM products 
                             WHERE status = 'active' AND is_deleted = 0 AND is_new = 1
                             ORDER BY updated_at DESC LIMIT 6";
            $updated_stmt = $pdo->prepare($updated_query);
            $updated_stmt->execute();
            $new_count = $updated_stmt->rowCount();
            
            // If we don't have enough new products, get recently updated ones to fill in
            if ($new_count < 6) {
              $updated_query = "SELECT * FROM products 
                               WHERE status = 'active' AND is_deleted = 0
                               ORDER BY is_new DESC, updated_at DESC LIMIT 6";
            }
          } else {
            // Get products updated in the last 30 days
            $updated_query = "SELECT * FROM products 
                             WHERE status = 'active' AND is_deleted = 0 
                             AND updated_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                             ORDER BY updated_at DESC LIMIT 6";
          }
        } else {
          // Fallback if columns don't exist
          $updated_query = "SELECT * FROM products ORDER BY id DESC LIMIT 6";
        }
        
        $updated_stmt = $pdo->prepare($updated_query);
        $updated_stmt->execute();
        $updated_products = $updated_stmt->fetchAll();
        
        if (count($updated_products) > 0) {
          foreach ($updated_products as $product) {
            ?>
            <div class="window-product">
              <div class="product-badge">
                <?php if (isset($product['is_new']) && $product['is_new'] == 1): ?>
                  <span class="badge new">NEW</span>
                <?php elseif (isset($product['updated_at']) && strtotime($product['updated_at']) > strtotime('-7 days')): ?>
                  <span class="badge new">NEW</span>
                <?php else: ?>
                  <span class="badge updated">UPDATED</span>
                <?php endif; ?>
              </div>
              <div class="window-product-image">
                <?php 
                $image_path = 'uploads/no-image.jpg';
                if (!empty($product['image'])) {
                    if (file_exists('uploads/' . $product['image'])) {
                        $image_path = 'uploads/' . $product['image'];
                    } elseif (file_exists($product['image'])) {
                        $image_path = $product['image'];
                    }
                }
                
                if (file_exists($image_path)): ?>
                  <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                  <div class="no-image">
                    <i class="fas fa-image"></i>
                  </div>
                <?php endif; ?>
              </div>
              <div class="window-product-info">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="price">K<?php echo number_format($product['price'], 2); ?></p>
                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-view">View Details</a>
              </div>
            </div>
            <?php
          }
        } else {
          echo '<div class="no-products">No recently updated products available.</div>';
        }
      } catch (PDOException $e) {
        echo '<div class="error">Error loading updated products.</div>';
      }
      ?>
    </div>
  </div>

  <!-- Featured Products Section -->
  <div class="featured-section">
    <div class="featured-title">
      <h2>Featured Products</h2>
      <p>Discover our most popular items and special offers</p>
    </div>
    
    <div class="featured-grid">
      <?php
      // Get 3 random active products to feature
      try {
        // Check if status and is_deleted columns exist
        $check_column = "SHOW COLUMNS FROM products LIKE 'status'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $status_exists = $column_stmt->rowCount() > 0;
        
        $check_column = "SHOW COLUMNS FROM products LIKE 'is_deleted'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $is_deleted_exists = $column_stmt->rowCount() > 0;
        
        $check_column = "SHOW COLUMNS FROM products LIKE 'is_featured'";
        $column_stmt = $pdo->prepare($check_column);
        $column_stmt->execute();
        $is_featured_exists = $column_stmt->rowCount() > 0;
        
        if ($status_exists && $is_deleted_exists && $is_featured_exists) {
            // First try to get products marked as featured
            $featured_query = "SELECT * FROM products WHERE status = 'active' AND is_deleted = 0 AND is_featured = 1 LIMIT 3";
            $featured_stmt = $pdo->prepare($featured_query);
            $featured_stmt->execute();
            $featured_count = $featured_stmt->rowCount();
            
            // If we don't have enough featured products, get some random ones to fill in
            if ($featured_count < 3) {
                $featured_query = "SELECT * FROM products WHERE status = 'active' AND is_deleted = 0 AND (is_featured = 1 OR 1=1) ORDER BY is_featured DESC, RAND() LIMIT 3";
            }
        } else {
            $featured_query = "SELECT * FROM products ORDER BY RAND() LIMIT 3";
        }
        
        $featured_stmt = $pdo->prepare($featured_query);
        $featured_stmt->execute();
        $featured_products = $featured_stmt->fetchAll();
        
        foreach ($featured_products as $product) {
          // Check if image exists
          if (!empty($product['image']) && file_exists($product['image'])) {
            $image_path = $product['image'];
          } else {
            // Use a data URI for a placeholder image if no-image.jpg doesn't exist
            $image_path = file_exists('uploads/no-image.jpg') ? 'uploads/no-image.jpg' : 
              'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSIgZmlsbD0iIzk5OSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
          }
          
          // Check if stock exists and is greater than 0
          $in_stock = true;
          $stock_text = "";
          
          if (array_key_exists('stock', $product)) {
            if ($product['stock'] <= 0) {
              $in_stock = false;
              $stock_text = "<span style='color: #dc3545;'>Out of Stock</span>";
            } else {
              $stock_text = "<span style='color: #28a745;'>In Stock</span>";
            }
          }
          
          echo "<div class='shop-item' style='position: relative; transform: scale(1); transition: transform 0.3s ease, box-shadow 0.3s ease;' onmouseover='this.style.transform=\"scale(1.03)\"; this.style.boxShadow=\"0 8px 25px rgba(0,0,0,0.15)\"' onmouseout='this.style.transform=\"scale(1)\"; this.style.boxShadow=\"0 4px 12px rgba(0,0,0,0.1)\"'>
          <button class='quick-view-btn' onclick='openQuickView(\"" . htmlspecialchars($product['name']) . "\", \"" . htmlspecialchars($product['description']) . "\", \"" . number_format($product['price'], 2) . "\", \"" . $image_path . "\", " . $product['id'] . ")'>Quick View</button>
          <button class='add-to-compare' onclick='addToCompare(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", \"" . number_format($product['price'], 2) . "\", \"" . $image_path . "\")'>Compare</button>
          <button class='wishlist-btn' id='wishlist-" . $product['id'] . "' onclick='toggleWishlist(" . $product['id'] . ", \"" . htmlspecialchars($product['name']) . "\", \"" . number_format($product['price'], 2) . "\", \"" . $image_path . "\")'><i class='far fa-heart'></i></button>";
          
          // Add a "Featured" badge with animation for products marked as featured
          if (isset($product['is_featured']) && $product['is_featured'] == 1) {
            echo "<div style='position: absolute; top: 10px; right: 10px; background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 3px; font-weight: bold; z-index: 1; animation: pulse 2s infinite;'>Featured</div>";
          }
          
          // Add category badge
          echo "<div style='position: absolute; top: 10px; left: 10px; background-color: #007BFF; color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; z-index: 1;'>" . htmlspecialchars($product['category']) . "</div>";
          
          echo "<img src='{$image_path}' alt='" . htmlspecialchars($product['name']) . "' style='transition: transform 0.3s ease;' onmouseover='this.style.transform=\"scale(1.05)\"' onmouseout='this.style.transform=\"scale(1)\"'>
                <h3>" . htmlspecialchars($product['name']) . "</h3>
                <p>" . htmlspecialchars(substr($product['description'], 0, 80)) . "...</p>
                <div style='display: flex; justify-content: space-between; align-items: center; margin: 15px 0;'>
                  <span style='font-size: 1.2rem; font-weight: bold; color: #007BFF;'>K" . number_format($product['price'], 2) . "</span>
                  <span>" . $stock_text . "</span>
                </div>";
          
          if ($in_stock) {
            echo "
            <div style='display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;'>
              <form method='POST' action='add_to_cart.php' style='margin: 0;'>
                <input type='hidden' name='product_id' value='{$product['id']}'>
                <input type='hidden' name='quantity' value='1'>
                <input type='hidden' name='redirect' value='" . $_SERVER['REQUEST_URI'] . "'>
                <button type='submit' style='background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.3s ease;' onmouseover='this.style.background=\"#218838\"' onmouseout='this.style.background=\"#28a745\"'>
                  <i class='fas fa-cart-plus' style='margin-right: 5px;'></i> Add to Cart
                </button>
              </form>
              <a href='order.php?product_id={$product['id']}' style='text-decoration: none;'>
                <button style='background: linear-gradient(135deg, #007BFF, #0056b3); color: white; border: none; padding: 8px 12px; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.3s ease;' onmouseover='this.style.background=\"linear-gradient(135deg, #0056b3, #003d80)\"' onmouseout='this.style.background=\"linear-gradient(135deg, #007BFF, #0056b3)\"'>
                  <i class='fas fa-shopping-cart' style='margin-right: 5px;'></i> Buy Now
                </button>
              </a>
            </div>";
          } else {
            echo "<button disabled style='background-color: #6c757d; cursor: not-allowed; width: 100%;'><i class='fas fa-ban' style='margin-right: 5px;'></i> Out of Stock</button>";
          }
          
          echo "</div>";
        }
      } catch (PDOException $e) {
        echo "<p style='text-align: center;'>Error loading featured products.</p>";
      }
      ?>
    </div>
  </div>

  <h1>SmartFix Online Shop</h1>
  
  <!-- Special Offers Banner -->
  <div style="background: linear-gradient(135deg, #ff7e5f, #feb47b); border-radius: 10px; padding: 20px; margin-bottom: 30px; color: white; text-align: center; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -15px; right: -15px; background-color: #dc3545; color: white; padding: 5px 15px; transform: rotate(45deg); width: 150px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">Limited Time</div>
    <h3 style="margin-bottom: 10px; font-size: 1.5rem;">🔥 Special Offer: 10% OFF All Phone Accessories! 🔥</h3>
    <p style="margin-bottom: 15px;">Use code <strong>SMART10</strong> at checkout. Offer valid until June 30, 2024.</p>
    <button onclick="filterCategory('Phone')" style="background-color: white; color: #ff7e5f; border: none; padding: 8px 20px; border-radius: 5px; font-weight: bold; cursor: pointer;">Shop Now</button>
  </div>
  
  <form method="GET" id="categoryForm" style="text-align: center; margin-bottom: 30px;">
    <label for="category">🔍 Filter by Category:</label>
    <select name="category" id="category" onchange="this.form.submit()">
      <option value="">All Products</option>
      <option value="Spare" <?php if(isset($_GET['category']) && strtolower($_GET['category']) == strtolower('Spare')) echo 'selected'; ?>>Spare Parts</option>
      <option value="Phone" <?php if(isset($_GET['category']) && strtolower($_GET['category']) == strtolower('Phone')) echo 'selected'; ?>>Phone</option>
      <option value="Computer" <?php if(isset($_GET['category']) && strtolower($_GET['category']) == strtolower('Computer')) echo 'selected'; ?>>Computer</option>
      <option value="Car" <?php if(isset($_GET['category']) && strtolower($_GET['category']) == strtolower('Car')) echo 'selected'; ?>>Car</option>
      <option value="Other" <?php if(isset($_GET['category']) && strtolower($_GET['category']) == strtolower('Other')) echo 'selected'; ?>>Other</option>
    </select>
  </form>

<div class="shop-grid">
  <?php
  // Get filter parameters
  $category = isset($_GET['category']) ? $_GET['category'] : '';
  $search = isset($_GET['search']) ? $_GET['search'] : '';
  $min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
  $max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
  $availability = isset($_GET['availability']) ? $_GET['availability'] : 'all';
  $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

  try {
    // Check if required columns exist
    $check_column = "SHOW COLUMNS FROM products LIKE 'created_at'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $created_at_exists = $column_stmt->rowCount() > 0;
    
    $check_column = "SHOW COLUMNS FROM products LIKE 'status'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $status_exists = $column_stmt->rowCount() > 0;
    
    $check_column = "SHOW COLUMNS FROM products LIKE 'is_deleted'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $is_deleted_exists = $column_stmt->rowCount() > 0;
    
    $check_column = "SHOW COLUMNS FROM products LIKE 'stock'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $stock_exists = $column_stmt->rowCount() > 0;
    
    // Build the query
    $params = [];
    $where_clauses = [];
    
    // Base conditions for active products
    if ($status_exists && $is_deleted_exists) {
      $where_clauses[] = "status = 'active' AND is_deleted = 0";
    }
    
    // Category filter
    if (!empty($category)) {
      $where_clauses[] = "LOWER(category) = LOWER(:category)";
      $params['category'] = $category;
    }
    
    // Search filter
    if (!empty($search)) {
      $where_clauses[] = "(name LIKE :search OR description LIKE :search)";
      $params['search'] = "%{$search}%";
    }
    
    // Price range filter
    if ($min_price !== null) {
      $where_clauses[] = "price >= :min_price";
      $params['min_price'] = $min_price;
    }
    
    if ($max_price !== null) {
      $where_clauses[] = "price <= :max_price";
      $params['max_price'] = $max_price;
    }
    
    // Availability filter
    if ($availability === 'in_stock' && $stock_exists) {
      $where_clauses[] = "stock > 0";
    }
    
    // Combine where clauses
    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
    
    // Determine sort order
    $order_by = "";
    switch ($sort) {
      case 'price_low':
        $order_by = "ORDER BY price ASC";
        break;
      case 'price_high':
        $order_by = "ORDER BY price DESC";
        break;
      case 'name_asc':
        $order_by = "ORDER BY name ASC";
        break;
      case 'name_desc':
        $order_by = "ORDER BY name DESC";
        break;
      case 'newest':
      default:
        if ($created_at_exists) {
          $order_by = "ORDER BY created_at DESC";
        } else {
          $order_by = "ORDER BY id DESC";
        }
        break;
    }
    
    // Build the final query
    $query = "SELECT * FROM products {$where_sql} {$order_by}";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
  } catch (PDOException $e) {
    echo "<p style='color: red; text-align: center;'>Error loading products: " . htmlspecialchars($e->getMessage()) . "</p>";
    $products = [];
  }
  
  // Initialize products array
  $products = [];
  
  // Only try to fetch if $stmt is defined (no error occurred)
  if (isset($stmt)) {
    $products = $stmt->fetchAll();
  }

  if (count($products) > 0) {
    foreach ($products as $product) {
      // Check if image exists
      if (!empty($product['image']) && file_exists($product['image'])) {
        $image_path = $product['image'];
      } else {
        // Use a data URI for a placeholder image if no-image.jpg doesn't exist
        $image_path = file_exists('uploads/no-image.jpg') ? 'uploads/no-image.jpg' : 
          'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSIgZmlsbD0iIzk5OSI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
      }
      
      // Check if stock exists and is greater than 0
      $in_stock = true;
      $stock_text = "";
      
      if (array_key_exists('stock', $product)) {
        if ($product['stock'] <= 0) {
          $in_stock = false;
          $stock_text = "<span style='color: #dc3545;'>Out of Stock</span>";
        } else {
          $stock_text = "<span style='color: #28a745;'>In Stock</span>";
        }
      }
      
      // Determine if this is a new product (either marked as new or added in the last 7 days)
      $is_new = false;
      if (isset($product['is_new']) && $product['is_new'] == 1) {
        $is_new = true;
      } elseif (isset($product['created_at'])) {
        $created_date = new DateTime($product['created_at']);
        $now = new DateTime();
        $interval = $created_date->diff($now);
        $is_new = $interval->days < 7;
      }
      
      echo "<div class='shop-item' style='position: relative; transition: transform 0.3s ease, box-shadow 0.3s ease;' onmouseover='this.style.transform=\"translateY(-5px)\"; this.style.boxShadow=\"0 8px 20px rgba(0,0,0,0.15)\"' onmouseout='this.style.transform=\"translateY(0)\"; this.style.boxShadow=\"0 4px 12px rgba(0,0,0,0.1)\"'>";
      
      // Add badges if applicable
      if ($is_new) {
        echo "<div style='position: absolute; top: 10px; left: 10px; background-color: #28a745; color: white; padding: 5px 10px; border-radius: 3px; font-weight: bold; z-index: 1;'>New</div>";
      }
      
      // Add featured badge if applicable
      if (isset($product['is_featured']) && $product['is_featured'] == 1) {
        echo "<div style='position: absolute; top: " . ($is_new ? "45px" : "10px") . "; left: 10px; background-color: #ffc107; color: #000; padding: 5px 10px; border-radius: 3px; font-weight: bold; z-index: 1; animation: pulse 2s infinite;'>Featured</div>";
      }
      
      // Add category badge
      echo "<div style='position: absolute; top: 10px; right: 10px; background-color: #007BFF; color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8rem; z-index: 1;'>" . htmlspecialchars($product['category']) . "</div>";
      
      echo "<img src='{$image_path}' alt='" . htmlspecialchars($product['name']) . "' style='transition: transform 0.3s ease;' onmouseover='this.style.transform=\"scale(1.05)\"' onmouseout='this.style.transform=\"scale(1)\"'>
              <h3>" . htmlspecialchars($product['name']) . "</h3>
              <p style='color: #666; height: 60px; overflow: hidden;'>" . htmlspecialchars($product['description']) . "</p>
              <div style='display: flex; justify-content: space-between; align-items: center; margin: 15px 0;'>
                <span style='font-size: 1.2rem; font-weight: bold; color: #007BFF;'>K" . number_format($product['price'], 2) . "</span>
                <span>" . $stock_text . "</span>
              </div>";
      
      if ($in_stock) {
        echo "
        <div style='display: flex; gap: 8px; flex-wrap: wrap;'>
          <form method='POST' action='add_to_cart.php' style='margin: 0; flex: 1;'>
            <input type='hidden' name='product_id' value='{$product['id']}'>
            <input type='hidden' name='quantity' value='1'>
            <input type='hidden' name='redirect' value='" . $_SERVER['REQUEST_URI'] . "'>
            <button type='submit' style='background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.3s ease; width: 100%;' onmouseover='this.style.background=\"#218838\"' onmouseout='this.style.background=\"#28a745\"'>
              <i class='fas fa-cart-plus' style='margin-right: 5px;'></i> Add to Cart
            </button>
          </form>
          <a href='order.php?product_id={$product['id']}' style='text-decoration: none; flex: 1;'>
            <button style='background: #007BFF; color: white; border: none; padding: 8px 12px; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.3s ease; width: 100%;' onmouseover='this.style.backgroundColor=\"#0056b3\"' onmouseout='this.style.backgroundColor=\"#007BFF\"'>
              <i class='fas fa-shopping-cart' style='margin-right: 5px;'></i> Buy Now
            </button>
          </a>
        </div>";
      } else {
        echo "<button disabled style='width: 100%; background-color: #6c757d; cursor: not-allowed;'><i class='fas fa-ban' style='margin-right: 5px;'></i> Out of Stock</button>";
      }
      
      echo "</div>";
    }
  } else {
    echo "<p style='text-align: center; grid-column: 1 / -1; margin: 30px 0;'>No products available.</p>";
  }
  ?>
</div>

<!-- Product Recommendations -->
<div style="background-color: #f8f9fa; padding: 40px 20px; margin-top: 50px;">
  <div style="max-width: 1200px; margin: 0 auto;">
    <h2 style="text-align: center; margin-bottom: 30px; color: #333; font-size: 1.8rem;">Recommended For You</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
      <!-- Recommendation 1 -->
      <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%;">
        <div style="height: 150px; background: linear-gradient(135deg, #6a11cb, #2575fc); display: flex; justify-content: center; align-items: center; color: white;">
          <i class="fas fa-mobile-alt" style="font-size: 3rem;"></i>
        </div>
        <div style="padding: 20px; flex-grow: 1;">
          <h3 style="margin-top: 0; margin-bottom: 10px;">Phone Screen Protectors</h3>
          <p style="color: #666; margin-bottom: 15px;">Protect your device with our premium tempered glass screen protectors.</p>
          <button onclick="filterCategory('Phone')" style="background-color: #007BFF; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; width: 100%;">View Products</button>
        </div>
      </div>
      
      <!-- Recommendation 2 -->
      <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%;">
        <div style="height: 150px; background: linear-gradient(135deg, #11998e, #38ef7d); display: flex; justify-content: center; align-items: center; color: white;">
          <i class="fas fa-laptop" style="font-size: 3rem;"></i>
        </div>
        <div style="padding: 20px; flex-grow: 1;">
          <h3 style="margin-top: 0; margin-bottom: 10px;">Laptop Accessories</h3>
          <p style="color: #666; margin-bottom: 15px;">Enhance your productivity with our range of laptop accessories.</p>
          <button onclick="filterCategory('Computer')" style="background-color: #007BFF; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; width: 100%;">View Products</button>
        </div>
      </div>
      
      <!-- Recommendation 3 -->
      <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; flex-direction: column; height: 100%;">
        <div style="height: 150px; background: linear-gradient(135deg, #ff416c, #ff4b2b); display: flex; justify-content: center; align-items: center; color: white;">
          <i class="fas fa-tools" style="font-size: 3rem;"></i>
        </div>
        <div style="padding: 20px; flex-grow: 1;">
          <h3 style="margin-top: 0; margin-bottom: 10px;">Repair Tools</h3>
          <p style="color: #666; margin-bottom: 15px;">Professional repair tools for DIY enthusiasts and technicians.</p>
          <button onclick="filterCategory('Spare')" style="background-color: #007BFF; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; width: 100%;">View Products</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Promotional Banner -->
<div style="background-color: #007BFF; color: white; padding: 40px 20px; text-align: center; margin-top: 50px;">
  <div style="max-width: 800px; margin: 0 auto;">
    <h2 style="font-size: 2rem; margin-bottom: 15px;">Need Repair Services?</h2>
    <p style="font-size: 1.1rem; margin-bottom: 25px;">SmartFix offers professional repair services for phones, computers, and more. Our expert technicians are ready to help!</p>
    <a href="index.php" style="display: inline-block; background-color: white; color: #007BFF; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: all 0.3s ease;">Book a Repair</a>
  </div>
</div>

<!-- Back to top button -->
<a href="#" id="back-to-top" style="position: fixed; bottom: 20px; left: 20px; background-color: var(--accent-color); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; text-decoration: none; opacity: 0; transition: opacity 0.3s ease;">
  <i class="fas fa-arrow-up"></i>
</a>

<!-- Quick View Modal -->
<div id="quickViewModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <div class="product-quick-view">
      <div class="product-quick-view-image">
        <img id="modalProductImage" src="" alt="Product Image">
      </div>
      <div class="product-quick-view-details">
        <h2 id="modalProductName"></h2>
        <div class="rating">
          <span class="rating-star"><i class="fas fa-star"></i></span>
          <span class="rating-star"><i class="fas fa-star"></i></span>
          <span class="rating-star"><i class="fas fa-star"></i></span>
          <span class="rating-star"><i class="fas fa-star"></i></span>
          <span class="rating-star"><i class="far fa-star"></i></span>
          <span class="rating-count">(4.0 - 24 reviews)</span>
        </div>
        <div class="product-quick-view-price" id="modalProductPrice"></div>
        <div class="product-quick-view-description" id="modalProductDescription"></div>
        <div class="product-quick-view-actions">
          <a id="modalBuyButton" href="#" class="btn-view">Buy Now</a>
          <button id="modalAddToWishlist" class="btn-view" style="background-color: #ff6b6b;">
            <i class="far fa-heart"></i> Add to Wishlist
          </button>
        </div>
        
        <div class="reviews-section">
          <div class="reviews-header">
            <h3 class="reviews-title">Customer Reviews</h3>
            <button id="writeReviewBtn" class="write-review-btn">Write a Review</button>
          </div>
          
          <div id="reviewForm" class="review-form">
            <div class="form-group">
              <label for="reviewerName">Your Name</label>
              <input type="text" id="reviewerName" placeholder="Enter your name">
            </div>
            <div class="form-group">
              <label>Rating</label>
              <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5">
                <label for="star5"><i class="fas fa-star"></i></label>
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4"><i class="fas fa-star"></i></label>
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3"><i class="fas fa-star"></i></label>
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2"><i class="fas fa-star"></i></label>
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1"><i class="fas fa-star"></i></label>
              </div>
            </div>
            <div class="form-group">
              <label for="reviewContent">Your Review</label>
              <textarea id="reviewContent" placeholder="Write your review here..."></textarea>
            </div>
            <button id="submitReview" class="submit-review">Submit Review</button>
          </div>
          
          <div class="review-list">
            <div class="review-item">
              <div class="review-header">
                <span class="reviewer-name">John Doe</span>
                <span class="review-date">May 15, 2024</span>
              </div>
              <div class="review-rating">
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="fas fa-star"></i></span>
              </div>
              <div class="review-content">
                Great product! Works exactly as described and arrived quickly. Would definitely recommend to others.
              </div>
            </div>
            
            <div class="review-item">
              <div class="review-header">
                <span class="reviewer-name">Jane Smith</span>
                <span class="review-date">April 28, 2024</span>
              </div>
              <div class="review-rating">
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="fas fa-star"></i></span>
                <span class="rating-star"><i class="far fa-star"></i></span>
                <span class="rating-star"><i class="far fa-star"></i></span>
              </div>
              <div class="review-content">
                The product is good but took longer than expected to arrive. Quality is decent for the price.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Compare Products Container -->
<div id="compareContainer" class="compare-container">
  <div class="compare-items" id="compareItems">
    <!-- Compare items will be added here dynamically -->
  </div>
  <div class="compare-actions">
    <button id="compareButton" class="compare-btn" disabled>Compare Products</button>
    <button id="clearCompare" class="clear-compare">Clear All</button>
  </div>
</div>

<!-- Wishlist Toggle Button -->
<button id="wishlistToggle" class="wishlist-toggle">
  <i class="fas fa-heart"></i>
  <span id="wishlistBadge" class="wishlist-badge" style="display: none;">0</span>
</button>

<!-- Wishlist Container -->
<div id="wishlistContainer" class="wishlist-container">
  <div class="wishlist-header">
    <h3>My Wishlist</h3>
    <button id="closeWishlist" class="close-wishlist">&times;</button>
  </div>
  <div id="wishlistItems" class="wishlist-items">
    <!-- Wishlist items will be added here dynamically -->
  </div>
  <div class="wishlist-actions">
    <button id="clearWishlist" class="clear-wishlist">Clear All</button>
  </div>
</div>

<script>
  // Slider functionality
  let currentSlide = 0;
  const slider = document.getElementById('slider');
  const dots = document.querySelectorAll('.slider-dot');
  const totalSlides = document.querySelectorAll('.slide').length;
  
  // Auto slide change
  let slideInterval = setInterval(nextSlide, 5000);
  
  function goToSlide(n) {
    currentSlide = n;
    updateSlider();
    resetInterval();
  }
  
  function prevSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    updateSlider();
    resetInterval();
  }
  
  function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    updateSlider();
    resetInterval();
  }
  
  function updateSlider() {
    slider.style.transform = `translateX(-${currentSlide * 33.33}%)`;
    
    // Update dots
    dots.forEach((dot, index) => {
      if (index === currentSlide) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }
  
  function resetInterval() {
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
  }
  
  // Filter by category from slider buttons
  function filterCategory(category) {
    document.getElementById('category').value = category;
    document.getElementById('categoryForm').submit();
  }
  
  // Scroll to products section
  function scrollToProducts() {
    const productsSection = document.querySelector('.shop-grid');
    productsSection.scrollIntoView({ behavior: 'smooth' });
  }
  
  // Back to top button functionality
  const backToTopButton = document.getElementById('back-to-top');
  
  if (backToTopButton) {
    window.addEventListener('scroll', () => {
      if (window.pageYOffset > 300) {
        backToTopButton.style.opacity = '1';
      } else {
        backToTopButton.style.opacity = '0';
      }
    });
    
    backToTopButton.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  
  // Compare Products Functionality
  const compareContainer = document.getElementById('compareContainer');
  const compareItems = document.getElementById('compareItems');
  const compareButton = document.getElementById('compareButton');
  const clearCompare = document.getElementById('clearCompare');
  let compareList = JSON.parse(localStorage.getItem('compareList')) || [];
  
  // Initialize compare container
  function initCompare() {
    if (compareList.length > 0) {
      updateCompareContainer();
      compareContainer.classList.add('show');
    }
  }
  
  // Add product to compare list
  function addToCompare(id, name, price, image) {
    // Check if product is already in compare list
    if (compareList.some(item => item.id === id)) {
      alert('This product is already in your compare list.');
      return;
    }
    
    // Limit to 4 products
    if (compareList.length >= 4) {
      alert('You can compare up to 4 products at a time. Please remove a product before adding a new one.');
      return;
    }
    
    // Add product to compare list
    compareList.push({ id, name, price, image });
    localStorage.setItem('compareList', JSON.stringify(compareList));
    
    // Update compare container
    updateCompareContainer();
    
    // Show compare container if it's not already visible
    if (!compareContainer.classList.contains('show')) {
      compareContainer.classList.add('show');
    }
  }
  
  // Remove product from compare list
  function removeFromCompare(id) {
    compareList = compareList.filter(item => item.id !== id);
    localStorage.setItem('compareList', JSON.stringify(compareList));
    
    // Update compare container
    updateCompareContainer();
    
    // Hide compare container if there are no items
    if (compareList.length === 0) {
      compareContainer.classList.remove('show');
    }
  }
  
  // Update compare container
  function updateCompareContainer() {
    // Clear compare items
    compareItems.innerHTML = '';
    
    // Add compare items
    compareList.forEach(item => {
      const compareItem = document.createElement('div');
      compareItem.className = 'compare-item';
      compareItem.innerHTML = `
        <button class="remove-compare" onclick="removeFromCompare(${item.id})">×</button>
        <img src="${item.image}" alt="${item.name}">
        <div class="compare-item-name">${item.name}</div>
        <div class="compare-item-price">K${item.price}</div>
      `;
      compareItems.appendChild(compareItem);
    });
    
    // Enable/disable compare button
    if (compareList.length >= 2) {
      compareButton.disabled = false;
    } else {
      compareButton.disabled = true;
    }
  }
  
  // Clear all products from compare list
  clearCompare.addEventListener('click', () => {
    compareList = [];
    localStorage.setItem('compareList', JSON.stringify(compareList));
    compareContainer.classList.remove('show');
  });
  
  // Compare products
  compareButton.addEventListener('click', () => {
    // Create a URL with product IDs
    const ids = compareList.map(item => item.id).join(',');
    // Redirect to compare page (you would need to create this page)
    window.location.href = `compare.php?ids=${ids}`;
  });
  
  // Wishlist Functionality
  const wishlistToggle = document.getElementById('wishlistToggle');
  const wishlistContainer = document.getElementById('wishlistContainer');
  const closeWishlist = document.getElementById('closeWishlist');
  const wishlistItems = document.getElementById('wishlistItems');
  const clearWishlist = document.getElementById('clearWishlist');
  const wishlistBadge = document.getElementById('wishlistBadge');
  let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
  
  // Initialize wishlist
  function initWishlist() {
    updateWishlistBadge();
    updateWishlistButtons();
    
    // Toggle wishlist container
    wishlistToggle.addEventListener('click', () => {
      wishlistContainer.classList.toggle('show');
      updateWishlistItems();
    });
    
    // Close wishlist
    closeWishlist.addEventListener('click', () => {
      wishlistContainer.classList.remove('show');
    });
    
    // Clear wishlist
    clearWishlist.addEventListener('click', () => {
      wishlist = [];
      localStorage.setItem('wishlist', JSON.stringify(wishlist));
      updateWishlistItems();
      updateWishlistBadge();
      updateWishlistButtons();
    });
    
    // Close wishlist when clicking outside
    document.addEventListener('click', (e) => {
      if (!wishlistContainer.contains(e.target) && e.target !== wishlistToggle && !wishlistToggle.contains(e.target)) {
        wishlistContainer.classList.remove('show');
      }
    });
  }
  
  // Toggle item in wishlist
  function toggleWishlist(id, name, price, image) {
    const index = wishlist.findIndex(item => item.id === id);
    const button = document.getElementById(`wishlist-${id}`);
    
    if (index === -1) {
      // Add to wishlist
      wishlist.push({ id, name, price, image });
      if (button) {
        button.innerHTML = '<i class="fas fa-heart"></i>';
        button.classList.add('active');
      }
    } else {
      // Remove from wishlist
      wishlist.splice(index, 1);
      if (button) {
        button.innerHTML = '<i class="far fa-heart"></i>';
        button.classList.remove('active');
      }
    }
    
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateWishlistBadge();
    
    // Update wishlist items if container is visible
    if (wishlistContainer.classList.contains('show')) {
      updateWishlistItems();
    }
  }
  
  // Update wishlist badge
  function updateWishlistBadge() {
    if (wishlist.length > 0) {
      wishlistBadge.textContent = wishlist.length;
      wishlistBadge.style.display = 'flex';
    } else {
      wishlistBadge.style.display = 'none';
    }
  }
  
  // Update wishlist buttons
  function updateWishlistButtons() {
    wishlist.forEach(item => {
      const button = document.getElementById(`wishlist-${item.id}`);
      if (button) {
        button.innerHTML = '<i class="fas fa-heart"></i>';
        button.classList.add('active');
      }
    });
  }
  
  // Update wishlist items
  function updateWishlistItems() {
    wishlistItems.innerHTML = '';
    
    if (wishlist.length === 0) {
      wishlistItems.innerHTML = '<p style="text-align: center; color: var(--text-color);">Your wishlist is empty</p>';
      return;
    }
    
    wishlist.forEach(item => {
      const wishlistItem = document.createElement('div');
      wishlistItem.className = 'wishlist-item';
      wishlistItem.innerHTML = `
        <img src="${item.image}" alt="${item.name}">
        <div class="wishlist-item-info">
          <div class="wishlist-item-name">${item.name}</div>
          <div class="wishlist-item-price">K${item.price}</div>
          <a href="order.php?product_id=${item.id}" style="color: var(--accent-color); font-size: 0.8rem; text-decoration: none;">Buy Now</a>
        </div>
        <button class="remove-wishlist" onclick="toggleWishlist(${item.id}, '${item.name}', '${item.price}', '${item.image}')">×</button>
      `;
      wishlistItems.appendChild(wishlistItem);
    });
  }
  
  // Initialize compare container on page load
  document.addEventListener('DOMContentLoaded', () => {
    initCompare();
    initWishlist();
    
    // Review form toggle
    const writeReviewBtn = document.getElementById('writeReviewBtn');
    const reviewForm = document.getElementById('reviewForm');
    const submitReview = document.getElementById('submitReview');
    
    if (writeReviewBtn && reviewForm) {
      writeReviewBtn.addEventListener('click', () => {
        reviewForm.classList.toggle('show');
      });
    }
    
    if (submitReview) {
      submitReview.addEventListener('click', () => {
        const reviewerName = document.getElementById('reviewerName').value;
        const reviewContent = document.getElementById('reviewContent').value;
        const rating = document.querySelector('input[name="rating"]:checked');
        
        if (!reviewerName || !reviewContent || !rating) {
          alert('Please fill in all fields and select a rating');
          return;
        }
        
        // Here you would typically send this data to the server
        // For now, we'll just add it to the UI
        const reviewList = document.querySelector('.review-list');
        const newReview = document.createElement('div');
        newReview.className = 'review-item';
        
        const today = new Date();
        const formattedDate = today.toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        });
        
        const stars = Array(5).fill(0).map((_, i) => {
          return i < rating.value ? 
            '<span class="rating-star"><i class="fas fa-star"></i></span>' : 
            '<span class="rating-star"><i class="far fa-star"></i></span>';
        }).join('');
        
        newReview.innerHTML = `
          <div class="review-header">
            <span class="reviewer-name">${reviewerName}</span>
            <span class="review-date">${formattedDate}</span>
          </div>
          <div class="review-rating">
            ${stars}
          </div>
          <div class="review-content">
            ${reviewContent}
          </div>
        `;
        
        reviewList.insertBefore(newReview, reviewList.firstChild);
        
        // Reset form
        document.getElementById('reviewerName').value = '';
        document.getElementById('reviewContent').value = '';
        document.querySelectorAll('input[name="rating"]').forEach(input => input.checked = false);
        reviewForm.classList.remove('show');
        
        alert('Thank you for your review!');
      });
    }
    
    // Filter toggle functionality
    const toggleFilters = document.getElementById('toggleFilters');
    const filterOptions = document.getElementById('filterOptions');
    const resetFilters = document.getElementById('resetFilters');
    
    if (toggleFilters && filterOptions) {
      toggleFilters.addEventListener('click', () => {
        const isHidden = filterOptions.style.display === 'none';
        filterOptions.style.display = isHidden ? 'block' : 'none';
        toggleFilters.innerHTML = isHidden ? 
          '<i class="fas fa-times"></i> Hide Filters' : 
          '<i class="fas fa-sliders-h"></i> Show Filters';
      });
    }
    
    // Check if there are any filter parameters in the URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.toString() && filterOptions) {
      filterOptions.style.display = 'block';
      if (toggleFilters) {
        toggleFilters.innerHTML = '<i class="fas fa-times"></i> Hide Filters';
      }
    }
    
    // Reset filters
    if (resetFilters) {
      resetFilters.addEventListener('click', () => {
        window.location.href = 'shop.php';
      });
    }
  });
  
  // Quick View Modal Functionality
  const modal = document.getElementById('quickViewModal');
  const closeModal = document.querySelector('.close-modal');
  
  function openQuickView(name, description, price, imageSrc, productId) {
    document.getElementById('modalProductName').textContent = name;
    document.getElementById('modalProductDescription').textContent = description;
    document.getElementById('modalProductPrice').textContent = 'K' + price;
    document.getElementById('modalProductImage').src = imageSrc;
    document.getElementById('modalBuyButton').href = 'order.php?product_id=' + productId;
    
    // Set up wishlist button
    const modalAddToWishlist = document.getElementById('modalAddToWishlist');
    if (modalAddToWishlist) {
      const isInWishlist = wishlist.some(item => item.id === productId);
      if (isInWishlist) {
        modalAddToWishlist.innerHTML = '<i class="fas fa-heart"></i> Remove from Wishlist';
      } else {
        modalAddToWishlist.innerHTML = '<i class="far fa-heart"></i> Add to Wishlist';
      }
      
      modalAddToWishlist.onclick = function() {
        toggleWishlist(productId, name, price, imageSrc);
        const isNowInWishlist = wishlist.some(item => item.id === productId);
        if (isNowInWishlist) {
          modalAddToWishlist.innerHTML = '<i class="fas fa-heart"></i> Remove from Wishlist';
        } else {
          modalAddToWishlist.innerHTML = '<i class="far fa-heart"></i> Add to Wishlist';
        }
      };
    }
    
    // Reset review form
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
      reviewForm.classList.remove('show');
      document.getElementById('reviewerName').value = '';
      document.getElementById('reviewContent').value = '';
      const ratingInputs = document.querySelectorAll('input[name="rating"]');
      ratingInputs.forEach(input => input.checked = false);
    }
    
    modal.classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
  }
  
  closeModal.addEventListener('click', () => {
    modal.classList.remove('show');
    document.body.style.overflow = ''; // Re-enable scrolling
  });
  
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  });
  
  // Dark Mode Toggle Functionality
  const darkModeToggle = document.getElementById('darkModeToggle');
  const icon = darkModeToggle.querySelector('i');
  
  // Check for saved theme preference or use preferred color scheme
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.body.classList.add('dark-mode');
    icon.classList.replace('fa-moon', 'fa-sun');
  }
  
  // Toggle dark mode
  darkModeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    
    // Update icon
    if (document.body.classList.contains('dark-mode')) {
      icon.classList.replace('fa-moon', 'fa-sun');
      localStorage.setItem('theme', 'dark');
    } else {
      icon.classList.replace('fa-sun', 'fa-moon');
      localStorage.setItem('theme', 'light');
    }
  });
</script>
  </div>
</body>
</html>
