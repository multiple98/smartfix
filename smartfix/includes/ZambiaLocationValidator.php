<?php
/**
 * Zambia Location Validator
 * Ensures all GPS coordinates are within Zambia boundaries
 */

class ZambiaLocationValidator {
    
    // Zambia geographical boundaries (approximate)
    const ZAMBIA_BOUNDS = [
        'north' => -8.224,
        'south' => -18.079,
        'east' => 33.706,
        'west' => 21.999
    ];
    
    // Major Zambian cities with their coordinates for reference
    const MAJOR_CITIES = [
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
    ];
    
    // Zambian provinces with their approximate boundaries
    const PROVINCES = [
        'Central' => [
            'bounds' => ['north' => -12.0, 'south' => -16.0, 'east' => 30.0, 'west' => 26.0],
            'capital' => 'Kabwe'
        ],
        'Copperbelt' => [
            'bounds' => ['north' => -11.5, 'south' => -14.0, 'east' => 29.0, 'west' => 26.5],
            'capital' => 'Ndola'
        ],
        'Eastern' => [
            'bounds' => ['north' => -10.0, 'south' => -17.0, 'east' => 33.7, 'west' => 29.0],
            'capital' => 'Chipata'
        ],
        'Luapula' => [
            'bounds' => ['north' => -8.2, 'south' => -12.0, 'east' => 30.5, 'west' => 27.5],
            'capital' => 'Mansa'
        ],
        'Lusaka' => [
            'bounds' => ['north' => -14.0, 'south' => -16.5, 'east' => 29.5, 'west' => 27.0],
            'capital' => 'Lusaka'
        ],
        'Muchinga' => [
            'bounds' => ['north' => -9.0, 'south' => -13.0, 'east' => 33.0, 'west' => 29.5],
            'capital' => 'Chinsali'
        ],
        'Northern' => [
            'bounds' => ['north' => -8.2, 'south' => -12.0, 'east' => 33.0, 'west' => 28.0],
            'capital' => 'Kasama'
        ],
        'North-Western' => [
            'bounds' => ['north' => -10.0, 'south' => -14.5, 'east' => 26.5, 'west' => 22.0],
            'capital' => 'Solwezi'
        ],
        'Southern' => [
            'bounds' => ['north' => -15.0, 'south' => -18.1, 'east' => 29.0, 'west' => 24.0],
            'capital' => 'Choma'
        ],
        'Western' => [
            'bounds' => ['north' => -13.0, 'south' => -18.1, 'east' => 25.0, 'west' => 22.0],
            'capital' => 'Mongu'
        ]
    ];
    
    /**
     * Validate if coordinates are within Zambia
     */
    public static function isWithinZambia($latitude, $longitude) {
        $lat = floatval($latitude);
        $lng = floatval($longitude);
        
        return (
            $lat >= self::ZAMBIA_BOUNDS['south'] && 
            $lat <= self::ZAMBIA_BOUNDS['north'] &&
            $lng >= self::ZAMBIA_BOUNDS['west'] && 
            $lng <= self::ZAMBIA_BOUNDS['east']
        );
    }
    
    /**
     * Get the province for given coordinates
     */
    public static function getProvince($latitude, $longitude) {
        $lat = floatval($latitude);
        $lng = floatval($longitude);
        
        foreach (self::PROVINCES as $province => $data) {
            $bounds = $data['bounds'];
            if ($lat >= $bounds['south'] && $lat <= $bounds['north'] &&
                $lng >= $bounds['west'] && $lng <= $bounds['east']) {
                return $province;
            }
        }
        
        return null;
    }
    
    /**
     * Get nearest major city
     */
    public static function getNearestCity($latitude, $longitude) {
        $lat = floatval($latitude);
        $lng = floatval($longitude);
        
        $nearest_city = null;
        $min_distance = PHP_FLOAT_MAX;
        
        foreach (self::MAJOR_CITIES as $city => $coords) {
            $distance = self::calculateDistance($lat, $lng, $coords['lat'], $coords['lng']);
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $nearest_city = $city;
            }
        }
        
        return [
            'city' => $nearest_city,
            'distance_km' => round($min_distance, 2)
        ];
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     */
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // Earth's radius in kilometers
        
        $lat1_rad = deg2rad($lat1);
        $lng1_rad = deg2rad($lng1);
        $lat2_rad = deg2rad($lat2);
        $lng2_rad = deg2rad($lng2);
        
        $delta_lat = $lat2_rad - $lat1_rad;
        $delta_lng = $lng2_rad - $lng1_rad;
        
        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lng / 2) * sin($delta_lng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Validate and sanitize coordinates
     */
    public static function validateCoordinates($latitude, $longitude) {
        $lat = floatval($latitude);
        $lng = floatval($longitude);
        
        // Basic coordinate validation
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return [
                'valid' => false,
                'error' => 'Invalid coordinate format',
                'latitude' => null,
                'longitude' => null
            ];
        }
        
        // Check if within Zambia
        if (!self::isWithinZambia($lat, $lng)) {
            return [
                'valid' => false,
                'error' => 'Coordinates are outside Zambia',
                'latitude' => $lat,
                'longitude' => $lng,
                'suggestion' => 'Please ensure your location is within Zambia boundaries'
            ];
        }
        
        // Get additional location information
        $province = self::getProvince($lat, $lng);
        $nearest_city = self::getNearestCity($lat, $lng);
        
        return [
            'valid' => true,
            'latitude' => $lat,
            'longitude' => $lng,
            'province' => $province,
            'nearest_city' => $nearest_city['city'],
            'distance_to_city' => $nearest_city['distance_km']
        ];
    }
    
    /**
     * Get Zambia center coordinates
     */
    public static function getZambiaCenter() {
        return [
            'latitude' => -13.133,
            'longitude' => 27.849
        ];
    }
    
    /**
     * Get Zambia boundaries
     */
    public static function getZambiaBounds() {
        return self::ZAMBIA_BOUNDS;
    }
    
    /**
     * Get all major cities
     */
    public static function getMajorCities() {
        return self::MAJOR_CITIES;
    }
    
    /**
     * Get all provinces
     */
    public static function getProvinces() {
        return self::PROVINCES;
    }
    
    /**
     * Generate random coordinates within Zambia (for testing)
     */
    public static function generateRandomZambianCoordinates() {
        $lat = mt_rand(
            intval(self::ZAMBIA_BOUNDS['south'] * 10000), 
            intval(self::ZAMBIA_BOUNDS['north'] * 10000)
        ) / 10000;
        
        $lng = mt_rand(
            intval(self::ZAMBIA_BOUNDS['west'] * 10000), 
            intval(self::ZAMBIA_BOUNDS['east'] * 10000)
        ) / 10000;
        
        return [
            'latitude' => $lat,
            'longitude' => $lng
        ];
    }
    
    /**
     * Check if technician location needs updating
     */
    public static function needsLocationUpdate($last_updated, $max_age_hours = 24) {
        if (!$last_updated) {
            return true;
        }
        
        $last_update_time = strtotime($last_updated);
        $max_age_seconds = $max_age_hours * 3600;
        
        return (time() - $last_update_time) > $max_age_seconds;
    }
    
    /**
     * Format coordinates for display
     */
    public static function formatCoordinates($latitude, $longitude, $precision = 4) {
        if (!$latitude || !$longitude) {
            return 'Location not available';
        }
        
        $lat = number_format(floatval($latitude), $precision);
        $lng = number_format(floatval($longitude), $precision);
        
        $lat_dir = $latitude >= 0 ? 'N' : 'S';
        $lng_dir = $longitude >= 0 ? 'E' : 'W';
        
        return "{$lat}°{$lat_dir}, {$lng}°{$lng_dir}";
    }
    
    /**
     * Get location accuracy description
     */
    public static function getAccuracyDescription($accuracy_meters) {
        if (!$accuracy_meters) {
            return 'Unknown accuracy';
        }
        
        $accuracy = floatval($accuracy_meters);
        
        if ($accuracy <= 5) {
            return 'Very High (±' . round($accuracy) . 'm)';
        } elseif ($accuracy <= 20) {
            return 'High (±' . round($accuracy) . 'm)';
        } elseif ($accuracy <= 100) {
            return 'Medium (±' . round($accuracy) . 'm)';
        } elseif ($accuracy <= 1000) {
            return 'Low (±' . round($accuracy) . 'm)';
        } else {
            return 'Very Low (±' . round($accuracy/1000, 1) . 'km)';
        }
    }
}
?>