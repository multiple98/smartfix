<?php
// SmartFix Security Configuration
return [
    'session' => [
        'cookie_lifetime' => 3600, // 1 hour
        'cookie_httponly' => true,
        'cookie_secure' => false,
        'use_strict_mode' => true,
    ],
    'rate_limiting' => [
        'login_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
        'cleanup_interval' => 3600, // 1 hour
    ],
    'file_upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'upload_path' => 'uploads/',
    ],
    'csrf' => [
        'token_lifetime' => 3600, // 1 hour
        'regenerate_interval' => 300, // 5 minutes
    ],
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
    ],
];
