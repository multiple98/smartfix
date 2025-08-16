<?php
/**
 * Cache Manager Class
 * Handles caching functionality
 */

class CacheManager {
    private static $cache_dir = "cache/";
    private static $default_ttl = 3600; // 1 hour
    
    /**
     * Get cached data
     */
    public static function get($key) {
        $file = self::$cache_dir . md5($key) . ".cache";
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data["expires"] > time()) {
                return $data["value"];
            } else {
                unlink($file); // Remove expired cache
            }
        }
        
        return null;
    }
    
    /**
     * Set cache data
     */
    public static function set($key, $value, $ttl = null) {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
        
        $ttl = $ttl ?? self::$default_ttl;
        $file = self::$cache_dir . md5($key) . ".cache";
        
        $data = [
            "value" => $value,
            "expires" => time() + $ttl,
            "created" => time()
        ];
        
        return file_put_contents($file, json_encode($data)) !== false;
    }
    
    /**
     * Delete cached data
     */
    public static function delete($key) {
        $file = self::$cache_dir . md5($key) . ".cache";
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    /**
     * Clear all cache
     */
    public static function clear() {
        $files = glob(self::$cache_dir . "*.cache");
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats() {
        $files = glob(self::$cache_dir . "*.cache");
        $total_size = 0;
        $expired = 0;
        $active = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data["expires"] > time()) {
                $active++;
            } else {
                $expired++;
            }
        }
        
        return [
            "total_files" => count($files),
            "active_files" => $active,
            "expired_files" => $expired,
            "total_size" => $total_size,
            "cache_dir" => self::$cache_dir
        ];
    }
}
?>