<?php
/**
 * Professional Transport Provider Calculator
 * Calculates shipping costs and delivery times based on location and order details
 */

class TransportCalculator {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get available transport providers for a specific location
     */
    public function getAvailableProviders($province, $city = null, $total_weight = 0) {
        try {
            // Get all active providers
            $query = "SELECT * FROM transport_providers WHERE status = 'active' ORDER BY rating DESC, cost_per_km ASC";
            $stmt = $this->pdo->query($query);
            $all_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $available_providers = [];
            
            foreach ($all_providers as $provider) {
                // Check if provider serves this region
                $regions = array_map('trim', explode(',', $provider['regions']));
                $serves_region = false;
                
                foreach ($regions as $region) {
                    if (stripos($province, $region) !== false || stripos($region, $province) !== false) {
                        $serves_region = true;
                        break;
                    }
                }
                
                if ($serves_region) {
                    // Check weight capacity
                    $max_weight = $provider['max_weight_kg'] ?? 100;
                    if ($total_weight <= $max_weight) {
                        $available_providers[] = $provider;
                    }
                }
            }
            
            return $available_providers;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Calculate shipping cost for a provider
     */
    public function calculateShippingCost($provider_id, $province, $city, $total_weight = 0) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM transport_providers WHERE id = ? AND status = 'active'");
            $stmt->execute([$provider_id]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$provider) {
                return null;
            }
            
            // Base cost
            $base_cost = $provider['base_cost'] ?? 20.00;
            
            // Distance-based calculation (simplified - in real world, use Google Maps API)
            $distance = $this->estimateDistance($province, $city);
            $distance_cost = $distance * ($provider['cost_per_km'] ?? 5.00);
            
            // Weight-based surcharge
            $weight_surcharge = 0;
            if ($total_weight > 10) {
                $weight_surcharge = ($total_weight - 10) * 2.00; // ZMW 2 per kg over 10kg
            }
            
            // Service type multiplier
            $service_multiplier = $this->getServiceMultiplier($provider['service_type'] ?? 'standard');
            
            $total_cost = ($base_cost + $distance_cost + $weight_surcharge) * $service_multiplier;
            
            return [
                'base_cost' => $base_cost,
                'distance_cost' => $distance_cost,
                'weight_surcharge' => $weight_surcharge,
                'service_multiplier' => $service_multiplier,
                'total_cost' => round($total_cost, 2),
                'estimated_distance' => $distance,
                'estimated_days' => $provider['estimated_days'] ?? 3
            ];
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Estimate distance based on province and city
     */
    private function estimateDistance($province, $city) {
        // Simplified distance calculation - in production, use Google Maps Distance Matrix API
        $distances = [
            'Lusaka' => 0,
            'Copperbelt' => 350,
            'Central' => 200,
            'Eastern' => 400,
            'Northern' => 600,
            'Northwestern' => 500,
            'Southern' => 300,
            'Western' => 450,
            'Muchinga' => 550
        ];
        
        foreach ($distances as $region => $km) {
            if (stripos($province, $region) !== false) {
                return $km;
            }
        }
        
        return 250; // Default distance
    }
    
    /**
     * Get service type multiplier
     */
    private function getServiceMultiplier($service_type) {
        $multipliers = [
            'standard' => 1.0,
            'express' => 1.5,
            'overnight' => 1.8,
            'same_day' => 2.0
        ];
        
        return $multipliers[$service_type] ?? 1.0;
    }
    
    /**
     * Get delivery time estimate
     */
    public function getDeliveryEstimate($provider_id, $province) {
        try {
            $stmt = $this->pdo->prepare("SELECT estimated_days, service_type FROM transport_providers WHERE id = ?");
            $stmt->execute([$provider_id]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$provider) {
                return null;
            }
            
            $base_days = $provider['estimated_days'] ?? 3;
            
            // Add extra days for remote provinces
            $remote_provinces = ['Northern', 'Northwestern', 'Muchinga'];
            $extra_days = 0;
            
            foreach ($remote_provinces as $remote) {
                if (stripos($province, $remote) !== false) {
                    $extra_days = 1;
                    break;
                }
            }
            
            $total_days = $base_days + $extra_days;
            
            // Calculate delivery date
            $delivery_date = date('Y-m-d', strtotime("+{$total_days} days"));
            
            return [
                'estimated_days' => $total_days,
                'delivery_date' => $delivery_date,
                'delivery_date_formatted' => date('l, F j, Y', strtotime($delivery_date)),
                'service_type' => $provider['service_type'] ?? 'standard'
            ];
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Create sample transport providers if none exist
     */
    public function createSampleProviders() {
        try {
            // Check if providers exist
            $count = $this->pdo->query("SELECT COUNT(*) FROM transport_providers")->fetchColumn();
            
            if ($count == 0) {
                $providers = [
                    [
                        'name' => 'FastTrack Express',
                        'contact' => '+260-97-123-4567',
                        'email' => 'info@fasttrack.zm',
                        'regions' => 'Lusaka,Copperbelt,Central,Eastern',
                        'estimated_days' => 2,
                        'cost_per_km' => 3.50,
                        'base_cost' => 25.00,
                        'max_weight_kg' => 100,
                        'service_type' => 'express',
                        'vehicle_type' => 'Truck',
                        'rating' => 4.8,
                        'operating_hours' => '6:00 AM - 8:00 PM',
                        'description' => 'Fast and reliable express delivery service with real-time tracking'
                    ],
                    [
                        'name' => 'City Logistics',
                        'contact' => '+260-96-987-6543',
                        'email' => 'orders@citylogistics.zm',
                        'regions' => 'Lusaka,Central,Southern,Eastern',
                        'estimated_days' => 3,
                        'cost_per_km' => 2.80,
                        'base_cost' => 20.00,
                        'max_weight_kg' => 75,
                        'service_type' => 'standard',
                        'vehicle_type' => 'Van',
                        'rating' => 4.5,
                        'operating_hours' => '8:00 AM - 6:00 PM',
                        'description' => 'Affordable standard delivery with excellent customer service'
                    ],
                    [
                        'name' => 'QuickMove Same-Day',
                        'contact' => '+260-95-555-0123',
                        'email' => 'urgent@quickmove.zm',
                        'regions' => 'Lusaka,Copperbelt',
                        'estimated_days' => 1,
                        'cost_per_km' => 5.00,
                        'base_cost' => 40.00,
                        'max_weight_kg' => 50,
                        'service_type' => 'same_day',
                        'vehicle_type' => 'Motorcycle',
                        'rating' => 4.9,
                        'operating_hours' => '24/7',
                        'description' => 'Same-day delivery for urgent orders within major cities'
                    ],
                    [
                        'name' => 'Provincial Courier',
                        'contact' => '+260-94-777-8888',
                        'email' => 'delivery@provincial.zm',
                        'regions' => 'Northern,Northwestern,Muchinga,Western',
                        'estimated_days' => 5,
                        'cost_per_km' => 4.20,
                        'base_cost' => 30.00,
                        'max_weight_kg' => 200,
                        'service_type' => 'standard',
                        'vehicle_type' => 'Truck',
                        'rating' => 4.2,
                        'operating_hours' => '7:00 AM - 7:00 PM',
                        'description' => 'Specialized delivery service for remote provinces and rural areas'
                    ]
                ];
                
                $insert_stmt = $this->pdo->prepare("
                    INSERT INTO transport_providers 
                    (name, contact, email, regions, estimated_days, cost_per_km, base_cost, max_weight_kg, 
                     service_type, vehicle_type, rating, operating_hours, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($providers as $provider) {
                    $insert_stmt->execute([
                        $provider['name'],
                        $provider['contact'],
                        $provider['email'],
                        $provider['regions'],
                        $provider['estimated_days'],
                        $provider['cost_per_km'],
                        $provider['base_cost'],
                        $provider['max_weight_kg'],
                        $provider['service_type'],
                        $provider['vehicle_type'],
                        $provider['rating'],
                        $provider['operating_hours'],
                        $provider['description']
                    ]);
                }
                
                return count($providers);
            }
            
            return 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>