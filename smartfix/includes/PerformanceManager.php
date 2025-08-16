<?php
class PerformanceManager {
    private $pdo;
    private $cacheDir;
    private $cacheEnabled = true;
    private $queryCache = [];
    
    public function __construct($pdo, $cacheDir = null) {
        $this->pdo = $pdo;
        $this->cacheDir = $cacheDir ?? dirname(__DIR__) . '/cache';
        $this->ensureCacheDirectory();
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Cache query results
     */
    public function cacheQuery($key, $query, $params = [], $ttl = 300) {
        if (!$this->cacheEnabled) {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $cacheKey = md5($key . serialize($params));
        $cacheFile = $this->cacheDir . '/query_' . $cacheKey . '.cache';
        
        // Check if cache exists and is valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            $cached = file_get_contents($cacheFile);
            return json_decode($cached, true);
        }
        
        // Execute query and cache result
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        file_put_contents($cacheFile, json_encode($result), LOCK_EX);
        
        return $result;
    }
    
    /**
     * Clear cache by pattern
     */
    public function clearCache($pattern = '*') {
        $files = glob($this->cacheDir . '/query_' . $pattern . '.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Get optimized dashboard data
     */
    public function getDashboardData($userId, $userEmail) {
        try {
            // First check if service_updates table exists
            $tableExists = false;
            try {
                $checkTable = $this->pdo->query("SHOW TABLES LIKE 'service_updates'");
                $tableExists = $checkTable->rowCount() > 0;
            } catch (PDOException $e) {
                $tableExists = false;
            }
            
            if ($tableExists) {
                // Use optimized query with service_updates
                $query = "
                    SELECT 
                        sr.*,
                        COALESCE(latest_update.status, sr.status) as current_status,
                        latest_update.update_text as latest_update,
                        latest_update.created_at as update_date
                    FROM service_requests sr
                    LEFT JOIN (
                        SELECT 
                            su1.service_request_id,
                            su1.status,
                            su1.update_text,
                            su1.created_at
                        FROM service_updates su1
                        INNER JOIN (
                            SELECT 
                                service_request_id,
                                MAX(created_at) as max_created_at
                            FROM service_updates
                            GROUP BY service_request_id
                        ) su2 ON su1.service_request_id = su2.service_request_id 
                             AND su1.created_at = su2.max_created_at
                    ) latest_update ON sr.id = latest_update.service_request_id
                    WHERE (sr.email = ? OR sr.contact = ?)
                    ORDER BY sr.request_date DESC
                ";
            } else {
                // Fallback query without service_updates table
                $query = "
                    SELECT 
                        sr.*,
                        sr.status as current_status,
                        NULL as latest_update,
                        NULL as update_date
                    FROM service_requests sr
                    WHERE (sr.email = ? OR sr.contact = ?)
                    ORDER BY sr.request_date DESC
                ";
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userEmail, $userEmail]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format results for dashboard display
            $serviceRequests = [];
            
            foreach ($results as $row) {
                $serviceRequests[] = [
                    'id' => $row['id'],
                    'reference_number' => $row['reference_number'] ?? 'N/A',
                    'service_type' => $row['service_type'] ?? 'Unknown',
                    'current_status' => $row['current_status'] ?? 'pending',
                    'request_date' => $row['request_date'] ?? date('Y-m-d H:i:s'),
                    'latest_update' => $row['latest_update'] ?? null,
                    'update_date' => $row['update_date'] ?? null
                ];
            }
            
            return $serviceRequests;
            
        } catch (PDOException $e) {
            // If there's still an error, return empty array and log the error
            error_log("PerformanceManager getDashboardData error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get database statistics
     */
    public function getDbStats() {
        return $this->cacheQuery('db_stats', "
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM technicians) as total_technicians,
                (SELECT COUNT(*) FROM service_requests) as total_requests,
                (SELECT COUNT(*) FROM service_requests WHERE status = 'completed') as completed_requests,
                (SELECT COUNT(*) FROM products) as total_products,
                (SELECT COUNT(*) FROM orders) as total_orders
        ", [], 600); // Cache for 10 minutes
    }
    
    /**
     * Optimize database queries by adding indexes
     */
    public function optimizeDatabase() {
        $optimizations = [];
        
        try {
            // Add indexes for commonly queried columns
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
                "CREATE INDEX IF NOT EXISTS idx_users_user_type ON users(user_type)",
                "CREATE INDEX IF NOT EXISTS idx_service_requests_email ON service_requests(email)",
                "CREATE INDEX IF NOT EXISTS idx_service_requests_status ON service_requests(status)",
                "CREATE INDEX IF NOT EXISTS idx_service_requests_date ON service_requests(request_date)",
                "CREATE INDEX IF NOT EXISTS idx_service_updates_request_id ON service_updates(service_request_id)",
                "CREATE INDEX IF NOT EXISTS idx_service_updates_created ON service_updates(created_at)",
                "CREATE INDEX IF NOT EXISTS idx_products_category ON products(category)",
                "CREATE INDEX IF NOT EXISTS idx_products_created ON products(created_at)",
                "CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id)",
                "CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status)",
                "CREATE INDEX IF NOT EXISTS idx_technicians_specialization ON technicians(specialization)",
                "CREATE INDEX IF NOT EXISTS idx_messages_sender ON messages(sender_id)",
                "CREATE INDEX IF NOT EXISTS idx_messages_recipient ON messages(recipient_id)",
            ];
            
            foreach ($indexes as $index) {
                try {
                    $this->pdo->exec($index);
                    $optimizations[] = "✅ " . explode(' ', $index)[5] ?? 'Index created';
                } catch (PDOException $e) {
                    $optimizations[] = "⚠️ Index creation failed: " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $optimizations[] = "❌ Optimization failed: " . $e->getMessage();
        }
        
        return $optimizations;
    }
    
    /**
     * Compress and minify CSS
     */
    public function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove trailing semicolon before closing brace
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Compress images (basic optimization)
     */
    public function optimizeImage($source, $destination, $quality = 85) {
        $imageInfo = getimagesize($source);
        
        if (!$imageInfo) {
            return false;
        }
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Resize if image is too large
        $maxWidth = 1200;
        $maxHeight = 800;
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG and GIF
            if ($imageInfo['mime'] == 'image/png' || $imageInfo['mime'] == 'image/gif') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }
        
        // Save optimized image
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                return imagejpeg($image, $destination, $quality);
            case 'image/png':
                return imagepng($image, $destination, intval((100 - $quality) / 10));
            case 'image/gif':
                return imagegif($image, $destination);
        }
        
        imagedestroy($image);
        return false;
    }
    
    /**
     * Generate pagination
     */
    public function paginate($totalItems, $itemsPerPage = 20, $currentPage = 1) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
        ];
    }
    
    /**
     * Monitor database performance
     */
    public function getPerformanceMetrics() {
        try {
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Slow_queries'");
            $slowQueries = $stmt->fetch()['Value'] ?? 0;
            
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Questions'");
            $totalQueries = $stmt->fetch()['Value'] ?? 0;
            
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Uptime'");
            $uptime = $stmt->fetch()['Value'] ?? 0;
            
            $queriesPerSecond = $uptime > 0 ? round($totalQueries / $uptime, 2) : 0;
            
            return [
                'slow_queries' => $slowQueries,
                'total_queries' => $totalQueries,
                'queries_per_second' => $queriesPerSecond,
                'uptime_hours' => round($uptime / 3600, 1)
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Unable to fetch metrics'];
        }
    }
    
    /**
     * Clean up old cache files
     */
    public function cleanupCache($maxAge = 86400) { // 24 hours
        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (time() - filemtime($file) > $maxAge) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Enable or disable caching
     */
    public function setCacheEnabled($enabled) {
        $this->cacheEnabled = $enabled;
    }
}
?>