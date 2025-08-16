<?php
/**
 * Google Maps API Configuration
 * 
 * To get your Google Maps API key:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select existing one
 * 3. Enable these APIs:
 *    - Maps JavaScript API
 *    - Geocoding API
 *    - Directions API
 * 4. Create API key with appropriate restrictions
 * 5. Replace 'YOUR_GOOGLE_MAPS_API_KEY' below with your actual key
 */

function getGoogleMapsApiKey() {
    // Replace this with your actual Google Maps API key
    $api_key = 'YOUR_GOOGLE_MAPS_API_KEY';
    
    // For development, you can also use environment variables
    if (isset($_ENV['GOOGLE_MAPS_API_KEY'])) {
        $api_key = $_ENV['GOOGLE_MAPS_API_KEY'];
    }
    
    // Check if running in development mode
    if ($api_key === 'YOUR_GOOGLE_MAPS_API_KEY') {
        // In development, you can use a demo key or return null to disable maps
        error_log("Google Maps API key not configured. Please update config/maps_config.php");
        return null;
    }
    
    return $api_key;
}

/**
 * Check if Google Maps is properly configured
 */
function isGoogleMapsConfigured() {
    $key = getGoogleMapsApiKey();
    return !empty($key) && $key !== 'YOUR_GOOGLE_MAPS_API_KEY';
}

/**
 * Get Google Maps JavaScript API URL
 */
function getGoogleMapsJsUrl($callback = 'initMap') {
    $key = getGoogleMapsApiKey();
    if (!$key) {
        return null;
    }
    
    return "https://maps.googleapis.com/maps/api/js?key={$key}&callback={$callback}";
}

/**
 * Zambia-specific map configuration
 */
function getZambiaMapConfig() {
    return [
        'center' => [
            'lat' => -13.133,
            'lng' => 27.849
        ],
        'zoom' => 6,
        'bounds' => [
            'north' => -8.224,
            'south' => -18.079,
            'east' => 33.706,
            'west' => 21.999
        ],
        'major_cities' => [
            'Lusaka' => ['lat' => -15.4167, 'lng' => 28.2833],
            'Kitwe' => ['lat' => -12.8058, 'lng' => 28.2132],
            'Ndola' => ['lat' => -12.9587, 'lng' => 28.6366],
            'Kabwe' => ['lat' => -14.4469, 'lng' => 28.4464],
            'Chingola' => ['lat' => -12.5289, 'lng' => 27.8881],
            'Mufulira' => ['lat' => -12.5500, 'lng' => 28.2400],
            'Livingstone' => ['lat' => -17.8419, 'lng' => 25.8544],
            'Luanshya' => ['lat' => -13.1367, 'lng' => 28.4167],
            'Kasama' => ['lat' => -10.2167, 'lng' => 31.1833],
            'Chipata' => ['lat' => -13.6333, 'lng' => 32.6500]
        ]
    ];
}
?>