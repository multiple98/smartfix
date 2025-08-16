<?php
/**
 * Monitoring Manager
 * System monitoring and health checks
 */

class MonitoringManager {
    private static $metrics_dir = "logs/metrics/";
    
    /**
     * Track performance metric
     */
    public static function trackPerformance($operation, $duration, $context = []) {
        self::logMetric("performance", [
            "operation" => $operation,
            "duration" => $duration,
            "memory_usage" => memory_get_usage(true),
            "memory_peak" => memory_get_peak_usage(true),
            "context" => $context
        ]);
    }
    
    /**
     * Track user action
     */
    public static function trackUserAction($action, $user_id = null, $context = []) {
        self::logMetric("user_action", [
            "action" => $action,
            "user_id" => $user_id ?? $_SESSION["user_id"] ?? null,
            "context" => $context
        ]);
    }
    
    /**
     * Check system health
     */
    public static function checkSystemHealth() {
        $health = [
            "database" => self::checkDatabase(),
            "cache" => self::checkCache(),
            "storage" => self::checkStorage(),
            "memory" => self::checkMemory()
        ];
        
        $overall_status = "healthy";
        foreach ($health as $component => $status) {
            if ($status["status"] !== "healthy") {
                $overall_status = "unhealthy";
                break;
            }
        }
        
        return [
            "overall_status" => $overall_status,
            "components" => $health,
            "timestamp" => date("c")
        ];
    }
    
    /**
     * Check database health
     */
    private static function checkDatabase() {
        try {
            global $pdo;
            $start = microtime(true);
            $pdo->query("SELECT 1");
            $duration = (microtime(true) - $start) * 1000;
            
            return [
                "status" => "healthy",
                "response_time" => round($duration, 2) . "ms"
            ];
        } catch (Exception $e) {
            return [
                "status" => "unhealthy",
                "error" => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check cache health
     */
    private static function checkCache() {
        try {
            $test_key = "health_check_" . time();
            $test_value = "test";
            
            CacheManager::set($test_key, $test_value, 60);
            $retrieved = CacheManager::get($test_key);
            CacheManager::delete($test_key);
            
            if ($retrieved === $test_value) {
                return ["status" => "healthy"];
            } else {
                return ["status" => "unhealthy", "error" => "Cache read/write failed"];
            }
        } catch (Exception $e) {
            return ["status" => "unhealthy", "error" => $e->getMessage()];
        }
    }
    
    /**
     * Check storage health
     */
    private static function checkStorage() {
        $upload_dir = "uploads/";
        $cache_dir = "cache/";
        $logs_dir = "logs/";
        
        $checks = [
            "uploads_writable" => is_writable($upload_dir),
            "cache_writable" => is_writable($cache_dir),
            "logs_writable" => is_writable($logs_dir),
            "disk_space" => disk_free_space(".") > 100 * 1024 * 1024 // 100MB
        ];
        
        $all_healthy = array_reduce($checks, function($carry, $check) {
            return $carry && $check;
        }, true);
        
        return [
            "status" => $all_healthy ? "healthy" : "unhealthy",
            "checks" => $checks
        ];
    }
    
    /**
     * Check memory usage
     */
    private static function checkMemory() {
        $memory_usage = memory_get_usage(true);
        $memory_limit = ini_get("memory_limit");
        $memory_limit_bytes = self::parseMemoryLimit($memory_limit);
        
        $usage_percentage = ($memory_usage / $memory_limit_bytes) * 100;
        
        return [
            "status" => $usage_percentage < 80 ? "healthy" : "warning",
            "usage" => self::formatBytes($memory_usage),
            "limit" => $memory_limit,
            "percentage" => round($usage_percentage, 2)
        ];
    }
    
    /**
     * Log metric
     */
    private static function logMetric($type, $data) {
        if (!is_dir(self::$metrics_dir)) {
            mkdir(self::$metrics_dir, 0755, true);
        }
        
        $metric = [
            "timestamp" => date("c"),
            "type" => $type,
            "data" => $data
        ];
        
        $file = self::$metrics_dir . $type . "_" . date("Y-m-d") . ".log";
        file_put_contents($file, json_encode($metric) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Parse memory limit string
     */
    private static function parseMemoryLimit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $limit = (int) $limit;
        
        switch($last) {
            case "g": $limit *= 1024;
            case "m": $limit *= 1024;
            case "k": $limit *= 1024;
        }
        
        return $limit;
    }
    
    /**
     * Format bytes
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ["B", "KB", "MB", "GB", "TB"];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . " " . $units[$i];
    }
}
?>