<?php
/**
 * Configuration Manager
 * Handles application configuration
 */

class ConfigManager {
    private static $config = [];
    private static $environment = "development";
    
    /**
     * Load configuration
     */
    public static function load($environment = null) {
        if ($environment) {
            self::$environment = $environment;
        } else {
            // Auto-detect environment
            self::$environment = $_SERVER["SERVER_NAME"] === "localhost" ? "development" : "production";
        }
        
        $config_file = "config/" . self::$environment . ".php";
        if (file_exists($config_file)) {
            self::$config = require $config_file;
        }
        
        // Define environment constant
        if (!defined("ENVIRONMENT")) {
            define("ENVIRONMENT", self::$environment);
        }
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        $keys = explode(".", $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     */
    public static function set($key, $value) {
        $keys = explode(".", $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    /**
     * Get current environment
     */
    public static function getEnvironment() {
        return self::$environment;
    }
    
    /**
     * Check if in development mode
     */
    public static function isDevelopment() {
        return self::$environment === "development";
    }
}

// Auto-load configuration
ConfigManager::load();
?>