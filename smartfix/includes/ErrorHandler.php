<?php
/**
 * Error Handler Class
 * Centralized error handling and logging
 */

class ErrorHandler {
    private static $log_dir = "logs/";
    
    /**
     * Handle exceptions
     */
    public static function handleException($exception) {
        self::logError("EXCEPTION", $exception->getMessage(), [
            "file" => $exception->getFile(),
            "line" => $exception->getLine(),
            "trace" => $exception->getTraceAsString()
        ]);
        
        if (self::isDevelopment()) {
            echo "<div style=\"background: #ffebee; border: 1px solid #f44336; padding: 20px; margin: 20px; border-radius: 5px;\">";
            echo "<h3>🚨 Exception Occurred</h3>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre></details>";
            echo "</div>";
        } else {
            include "views/errors/500.php";
        }
    }
    
    /**
     * Handle errors
     */
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        self::logError("ERROR", $message, [
            "severity" => $severity,
            "file" => $file,
            "line" => $line
        ]);
        
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    
    /**
     * Log errors
     */
    public static function logError($type, $message, $context = []) {
        if (!is_dir(self::$log_dir)) {
            mkdir(self::$log_dir, 0755, true);
        }
        
        $log_entry = [
            "timestamp" => date("Y-m-d H:i:s"),
            "type" => $type,
            "message" => $message,
            "context" => $context,
            "url" => $_SERVER["REQUEST_URI"] ?? "",
            "method" => $_SERVER["REQUEST_METHOD"] ?? "",
            "ip" => $_SERVER["REMOTE_ADDR"] ?? "",
            "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? "",
            "user_id" => $_SESSION["user_id"] ?? null
        ];
        
        $log_file = self::$log_dir . "error_" . date("Y-m-d") . ".log";
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check if in development mode
     */
    private static function isDevelopment() {
        return defined("ENVIRONMENT") && ENVIRONMENT === "development";
    }
    
    /**
     * Get error statistics
     */
    public static function getErrorStats($days = 7) {
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = date("Y-m-d", strtotime("-$i days"));
            $log_file = self::$log_dir . "error_$date.log";
            
            if (file_exists($log_file)) {
                $lines = file($log_file, FILE_IGNORE_NEW_LINES);
                $stats[$date] = count($lines);
            } else {
                $stats[$date] = 0;
            }
        }
        
        return $stats;
    }
}

// Set error handlers
set_exception_handler([ErrorHandler::class, "handleException"]);
set_error_handler([ErrorHandler::class, "handleError"]);
?>