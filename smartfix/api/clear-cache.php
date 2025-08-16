<?php
require_once "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        CacheManager::clear();
        ApiResponse::success(null, "Cache cleared successfully");
    } catch (Exception $e) {
        ApiResponse::error("Failed to clear cache: " . $e->getMessage());
    }
} else {
    ApiResponse::error("Method not allowed", 405);
}
?>