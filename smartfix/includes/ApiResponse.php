<?php
/**
 * API Response Handler
 * Standardized API responses
 */

class ApiResponse {
    
    /**
     * Send success response
     */
    public static function success($data = null, $message = "Success", $code = 200) {
        http_response_code($code);
        header("Content-Type: application/json");
        
        $response = [
            "success" => true,
            "message" => $message,
            "timestamp" => date("c"),
            "code" => $code
        ];
        
        if ($data !== null) {
            $response["data"] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send error response
     */
    public static function error($message = "Error", $code = 400, $details = null) {
        http_response_code($code);
        header("Content-Type: application/json");
        
        $response = [
            "success" => false,
            "error" => $message,
            "timestamp" => date("c"),
            "code" => $code
        ];
        
        if ($details !== null) {
            $response["details"] = $details;
        }
        
        // Log API errors
        ErrorHandler::logError("API_ERROR", $message, [
            "code" => $code,
            "details" => $details,
            "endpoint" => $_SERVER["REQUEST_URI"] ?? ""
        ]);
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Validate API request
     */
    public static function validateRequest($required_fields = [], $method = "POST") {
        // Check request method
        if ($_SERVER["REQUEST_METHOD"] !== $method) {
            self::error("Invalid request method. Expected $method", 405);
        }
        
        // Get request data
        $data = [];
        if ($method === "POST" || $method === "PUT") {
            $input = file_get_contents("php://input");
            $data = json_decode($input, true) ?? $_POST;
        } else {
            $data = $_GET;
        }
        
        // Validate required fields
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                self::error("Missing required field: $field", 400);
            }
        }
        
        return $data;
    }
    
    /**
     * Rate limit API requests
     */
    public static function rateLimit($identifier = null, $max_requests = 100, $time_window = 3600) {
        $identifier = $identifier ?? $_SERVER["REMOTE_ADDR"];
        
        if (!SecurityManager::checkRateLimit("api_" . $identifier, $max_requests, $time_window)) {
            self::error("Rate limit exceeded. Try again later.", 429);
        }
    }
}
?>