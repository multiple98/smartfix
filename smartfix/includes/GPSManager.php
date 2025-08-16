<?php
/**
 * GPS Manager Class
 * Handles location-based functionality for SmartFix platform
 */

require_once __DIR__ . '/ZambiaLocationValidator.php';

class GPSManager {
    private $pdo;
    private $google_maps_api_key;
    
    public function __construct($pdo, $api_key = null) {
        $this->pdo = $pdo;
        
        // Load API key from configuration
        if ($api_key) {
            $this->google_maps_api_key = $api_key;
        } else {
            require_once dirname(__DIR__) . '/config/maps_config.php';
            $this->google_maps_api_key = getGoogleMapsApiKey();
        }
    }
    
    /**
     * Save customer location
     */
    public function saveCustomerLocation($request_id, $latitude, $longitude, $address = null, $accuracy = null) {
        try {
            // Create locations table if it doesn't exist
            $this->createLocationsTable();
            
            $query = "INSERT INTO service_locations 
                     (request_id, latitude, longitude, address, accuracy, location_type, created_at) 
                     VALUES (?, ?, ?, ?, ?, 'customer', NOW())
                     ON DUPLICATE KEY UPDATE 
                     latitude = VALUES(latitude), 
                     longitude = VALUES(longitude), 
                     address = VALUES(address),
                     accuracy = VALUES(accuracy),
                     updated_at = NOW()";
            
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$request_id, $latitude, $longitude, $address, $accuracy]);
            
        } catch (PDOException $e) {
            error_log("GPS Manager - Save location error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update technician location (restricted to Zambia)
     */
    public function updateTechnicianLocation($technician_id, $latitude, $longitude, $accuracy = null) {
        try {
            // Validate coordinates are within Zambia
            $validation = ZambiaLocationValidator::validateCoordinates($latitude, $longitude);
            
            if (!$validation['valid']) {
                error_log("GPS Manager - Invalid coordinates for technician {$technician_id}: " . $validation['error']);
                return [
                    'success' => false,
                    'error' => $validation['error'],
                    'suggestion' => $validation['suggestion'] ?? 'Please provide valid Zambian coordinates'
                ];
            }
            
            $this->createLocationsTable();
            
            $query = "INSERT INTO technician_locations 
                     (technician_id, latitude, longitude, accuracy, last_updated) 
                     VALUES (?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE 
                     latitude = VALUES(latitude), 
                     longitude = VALUES(longitude), 
                     accuracy = VALUES(accuracy),
                     last_updated = NOW()";
            
            $stmt = $this->pdo->prepare($query);
            $success = $stmt->execute([$technician_id, $validation['latitude'], $validation['longitude'], $accuracy]);
            
            if ($success) {
                return [
                    'success' => true,
                    'latitude' => $validation['latitude'],
                    'longitude' => $validation['longitude'],
                    'province' => $validation['province'],
                    'nearest_city' => $validation['nearest_city'],
                    'distance_to_city' => $validation['distance_to_city']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to update location in database'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("GPS Manager - Update technician location error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred'
            ];
        }
    }
    
    /**
     * Find nearest technicians
     */
    public function findNearestTechnicians($latitude, $longitude, $service_type = null, $radius_km = 50, $limit = 10) {
        try {
            $service_filter = $service_type ? "AND t.specialization LIKE CONCAT('%', ?, '%')" : "";
            $params = [$latitude, $longitude, $latitude, $radius_km];
            if ($service_type) {
                $params[] = $service_type;
            }
            
            $query = "SELECT 
                        t.id,
                        t.name,
                        t.email,
                        t.phone,
                        t.specialization,
                        t.rating,
                        tl.latitude,
                        tl.longitude,
                        tl.last_updated,
                        (6371 * acos(cos(radians(?)) * cos(radians(tl.latitude)) * 
                         cos(radians(tl.longitude) - radians(?)) + 
                         sin(radians(?)) * sin(radians(tl.latitude)))) AS distance_km,
                        CASE 
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                            ELSE 'offline'
                        END AS status
                      FROM technicians t
                      INNER JOIN technician_locations tl ON t.id = tl.technician_id
                      WHERE t.status = 'active'
                      $service_filter
                      HAVING distance_km <= ?
                      ORDER BY distance_km ASC, t.rating DESC
                      LIMIT ?";
            
            $params[] = $limit;
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("GPS Manager - Find nearest technicians error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate distance between two points
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Get address from coordinates (Reverse Geocoding)
     */
    public function getAddressFromCoordinates($latitude, $longitude) {
        if (!$this->isApiKeyConfigured()) {
            return null;
        }
        
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$this->google_maps_api_key}";
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                return $data['results'][0]['formatted_address'];
            }
        }
        
        return null;
    }
    
    /**
     * Get coordinates from address (Geocoding)
     */
    public function getCoordinatesFromAddress($address) {
        if (!$this->isApiKeyConfigured()) {
            return null;
        }
        
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key={$this->google_maps_api_key}";
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng']
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Check if location is in service area
     */
    public function isInServiceArea($latitude, $longitude) {
        // Kigali approximate bounds (you can modify for your service area)
        $service_bounds = [
            'north' => -1.8,
            'south' => -2.1,
            'east' => 30.2,
            'west' => 29.9
        ];
        
        return ($latitude >= $service_bounds['south'] && 
                $latitude <= $service_bounds['north'] && 
                $longitude >= $service_bounds['west'] && 
                $longitude <= $service_bounds['east']);
    }
    
    /**
     * Get route information between two points
     */
    public function getRouteInfo($start_lat, $start_lng, $end_lat, $end_lng) {
        if (!$this->isApiKeyConfigured()) {
            return null;
        }
        
        $url = "https://maps.googleapis.com/maps/api/directions/json?" . 
               "origin={$start_lat},{$start_lng}&" .
               "destination={$end_lat},{$end_lng}&" .
               "key={$this->google_maps_api_key}";
        
        $response = @file_get_contents($url);
        if ($response) {
            $data = json_decode($response, true);
            if ($data['status'] === 'OK' && !empty($data['routes'])) {
                $route = $data['routes'][0];
                $leg = $route['legs'][0];
                
                return [
                    'distance' => $leg['distance']['text'],
                    'duration' => $leg['duration']['text'],
                    'distance_value' => $leg['distance']['value'], // in meters
                    'duration_value' => $leg['duration']['value']  // in seconds
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Create necessary database tables
     */
    private function createLocationsTable() {
        try {
            // Service locations table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS service_locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id INT NOT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                address TEXT,
                accuracy FLOAT,
                location_type ENUM('customer', 'service_point') DEFAULT 'customer',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_request_location (request_id, location_type),
                INDEX idx_coordinates (latitude, longitude),
                INDEX idx_request_id (request_id)
            )");
            
            // Technician locations table
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS technician_locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                technician_id INT NOT NULL UNIQUE,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                accuracy FLOAT,
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_technician_id (technician_id),
                INDEX idx_coordinates (latitude, longitude),
                INDEX idx_last_updated (last_updated)
            )");
            
        } catch (PDOException $e) {
            error_log("GPS Manager - Create tables error: " . $e->getMessage());
        }
    }
    
    /**
     * Get service area statistics
     */
    public function getServiceAreaStats() {
        try {
            $stats = [];
            
            // Total service requests with locations
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM service_locations WHERE location_type = 'customer'");
            $stats['total_locations'] = $stmt->fetchColumn();
            
            // Active technicians with locations
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM technician_locations 
                                     WHERE last_updated > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['active_technicians'] = $stmt->fetchColumn();
            
            // Most common service areas (simplified)
            $stmt = $this->pdo->query("SELECT 
                                        ROUND(latitude, 2) as lat_area,
                                        ROUND(longitude, 2) as lng_area,
                                        COUNT(*) as request_count
                                      FROM service_locations 
                                      WHERE location_type = 'customer'
                                      GROUP BY lat_area, lng_area
                                      ORDER BY request_count DESC
                                      LIMIT 5");
            $stats['popular_areas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("GPS Manager - Get stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all technicians within Zambia
     */
    public function getTechniciansInZambia($filters = []) {
        try {
            $zambia_bounds = ZambiaLocationValidator::getZambiaBounds();
            
            // Build WHERE conditions
            $where_conditions = ["t.status = 'available'"];
            $params = [];
            
            // Add specialization filter
            if (!empty($filters['specialization'])) {
                $where_conditions[] = "t.specialization LIKE ?";
                $params[] = "%{$filters['specialization']}%";
            }
            
            // Add status filter
            if (!empty($filters['location_status'])) {
                if ($filters['location_status'] === 'online') {
                    $where_conditions[] = "tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
                } elseif ($filters['location_status'] === 'recently_active') {
                    $where_conditions[] = "tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) AND tl.last_updated <= DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
                } elseif ($filters['location_status'] === 'offline') {
                    $where_conditions[] = "(tl.last_updated IS NULL OR tl.last_updated <= DATE_SUB(NOW(), INTERVAL 2 HOUR))";
                }
            }
            
            // Add rating filter
            if (!empty($filters['min_rating'])) {
                $where_conditions[] = "t.rating >= ?";
                $params[] = floatval($filters['min_rating']);
            }
            
            // Restrict to Zambia boundaries
            $where_conditions[] = "(tl.latitude IS NULL OR (tl.latitude BETWEEN ? AND ? AND tl.longitude BETWEEN ? AND ?))";
            $params = array_merge($params, [
                $zambia_bounds['south'], 
                $zambia_bounds['north'], 
                $zambia_bounds['west'], 
                $zambia_bounds['east']
            ]);
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $query = "SELECT 
                        t.id,
                        t.name,
                        t.email,
                        t.phone,
                        t.specialization,
                        t.regions,
                        t.address,
                        t.bio,
                        t.rating,
                        t.total_jobs,
                        t.status,
                        t.created_at,
                        tl.latitude,
                        tl.longitude,
                        tl.accuracy,
                        tl.last_updated,
                        CASE 
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 'online'
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'recently_active'
                            ELSE 'offline'
                        END AS location_status
                      FROM technicians t
                      LEFT JOIN technician_locations tl ON t.id = tl.technician_id
                      WHERE {$where_clause}
                      ORDER BY 
                        CASE 
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1
                            WHEN tl.last_updated > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 2
                            ELSE 3
                        END,
                        t.rating DESC, 
                        t.total_jobs DESC";
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process and validate each technician's coordinates
            foreach ($technicians as &$tech) {
                if ($tech['latitude'] && $tech['longitude']) {
                    $validation = ZambiaLocationValidator::validateCoordinates($tech['latitude'], $tech['longitude']);
                    $tech['coordinates_valid'] = $validation['valid'];
                    $tech['province'] = $validation['province'] ?? null;
                    $tech['nearest_city'] = $validation['nearest_city'] ?? null;
                    $tech['distance_to_city'] = $validation['distance_to_city'] ?? null;
                    
                    // If coordinates are invalid, remove them
                    if (!$validation['valid']) {
                        $tech['latitude'] = null;
                        $tech['longitude'] = null;
                        $tech['location_status'] = 'offline';
                    }
                } else {
                    $tech['coordinates_valid'] = false;
                    $tech['province'] = null;
                    $tech['nearest_city'] = null;
                    $tech['distance_to_city'] = null;
                }
                
                // Format additional data
                $tech['rating'] = floatval($tech['rating']);
                $tech['total_jobs'] = intval($tech['total_jobs']);
                $tech['specialization_list'] = explode(',', $tech['specialization']);
                $tech['regions_list'] = $tech['regions'] ? explode(',', $tech['regions']) : [];
            }
            
            return $technicians;
            
        } catch (PDOException $e) {
            error_log("GPS Manager - Get Zambian technicians error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if API key is properly configured
     */
    private function isApiKeyConfigured() {
        return !empty($this->google_maps_api_key) && 
               $this->google_maps_api_key !== 'YOUR_GOOGLE_MAPS_API_KEY' &&
               $this->google_maps_api_key !== 'YOUR_GOOGLE_MAPS_API_KEY_HERE';
    }
}
?>