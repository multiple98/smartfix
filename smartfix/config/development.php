<?php
return [
    "database" => [
        "host" => "127.0.0.1",
        "port" => 3306,
        "name" => "smartfix",
        "user" => "root",
        "pass" => ""
    ],
    "debug" => true,
    "cache_enabled" => false,
    "log_level" => "debug",
    "google_maps_api_key" => "AIzaSyBOti4mM-6x9WDnZIjIeyb7TlR-2K7_BDc",
    "email" => [
        "smtp_host" => "localhost",
        "smtp_port" => 587,
        "smtp_user" => "",
        "smtp_pass" => "",
        "from_email" => "noreply@smartfix.local",
        "from_name" => "SmartFix Development"
    ],
    "security" => [
        "csrf_protection" => true,
        "rate_limiting" => true,
        "session_timeout" => 3600
    ]
];
?>