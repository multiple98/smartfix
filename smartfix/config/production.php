<?php
return [
    "database" => [
        "host" => "127.0.0.1",
        "port" => 3306,
        "name" => "smartfix",
        "user" => "root",
        "pass" => ""
    ],
    "debug" => false,
    "cache_enabled" => true,
    "log_level" => "error",
    "google_maps_api_key" => "YOUR_PRODUCTION_API_KEY",
    "email" => [
        "smtp_host" => "smtp.gmail.com",
        "smtp_port" => 587,
        "smtp_user" => "your-email@gmail.com",
        "smtp_pass" => "your-app-password",
        "from_email" => "noreply@smartfix.com",
        "from_name" => "SmartFix"
    ],
    "security" => [
        "csrf_protection" => true,
        "rate_limiting" => true,
        "session_timeout" => 1800
    ]
];
?>