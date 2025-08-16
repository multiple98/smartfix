<?php
include('includes/db.php');

// Check if technicians table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'technicians'")->fetchAll();
    if (count($result) == 0) {
        // Create technicians table
        $create_table = "CREATE TABLE technicians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20) NOT NULL,
            specialization VARCHAR(50) NOT NULL,
            regions TEXT,
            address TEXT,
            latitude VARCHAR(20),
            longitude VARCHAR(20),
            status ENUM('available', 'busy', 'offline') DEFAULT 'available',
            bio TEXT,
            rating DECIMAL(3,1) DEFAULT 0,
            total_jobs INT DEFAULT 0,
            user_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        echo "Created technicians table<br>";
    }
    
    // Check if we already have technicians
    $count = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
    if ($count > 0) {
        echo "Technicians already exist in the database. Skipping sample data.<br>";
    } else {
        // Add sample technicians
        $technicians = [
            [
                'name' => 'John Mwanza',
                'email' => 'john.mwanza@example.com',
                'phone' => '+260 977123456',
                'specialization' => 'Phone Repair',
                'regions' => 'Lusaka,Central',
                'address' => '123 Cairo Road, Lusaka',
                'latitude' => '-15.4167',
                'longitude' => '28.2833',
                'bio' => 'Experienced phone repair technician with over 8 years of experience fixing all types of smartphones. Specialized in screen replacements, battery issues, and water damage repair.',
                'rating' => 4.8,
                'total_jobs' => 56
            ],
            [
                'name' => 'Mary Banda',
                'email' => 'mary.banda@example.com',
                'phone' => '+260 966789012',
                'specialization' => 'Computer Repair',
                'regions' => 'Lusaka',
                'address' => '45 Great East Road, Lusaka',
                'latitude' => '-15.3982',
                'longitude' => '28.3232',
                'bio' => 'Computer technician with expertise in hardware and software troubleshooting. Certified in Microsoft and Cisco technologies. Specializes in virus removal, data recovery, and system optimization.',
                'rating' => 4.5,
                'total_jobs' => 42
            ],
            [
                'name' => 'David Phiri',
                'email' => 'david.phiri@example.com',
                'phone' => '+260 955345678',
                'specialization' => 'Vehicle Repair',
                'regions' => 'Copperbelt,North-Western',
                'address' => '78 Obote Avenue, Kitwe',
                'latitude' => '-12.8058',
                'longitude' => '28.2132',
                'bio' => 'Certified automotive technician with 12 years of experience. Specialized in engine diagnostics, electrical systems, and general maintenance for all vehicle makes and models.',
                'rating' => 4.9,
                'total_jobs' => 89
            ],
            [
                'name' => 'Sarah Tembo',
                'email' => 'sarah.tembo@example.com',
                'phone' => '+260 977567890',
                'specialization' => 'Electrical',
                'regions' => 'Lusaka,Southern',
                'address' => '12 Independence Avenue, Lusaka',
                'latitude' => '-15.4265',
                'longitude' => '28.2868',
                'bio' => 'Licensed electrician with expertise in residential and commercial electrical systems. Specializes in wiring, installations, repairs, and safety inspections.',
                'rating' => 4.7,
                'total_jobs' => 63
            ],
            [
                'name' => 'Michael Zulu',
                'email' => 'michael.zulu@example.com',
                'phone' => '+260 966123789',
                'specialization' => 'Plumbing',
                'regions' => 'Lusaka,Eastern',
                'address' => '34 Addis Ababa Drive, Lusaka',
                'latitude' => '-15.4023',
                'longitude' => '28.3102',
                'bio' => 'Professional plumber with 10 years of experience in residential and commercial plumbing. Expert in leak detection, pipe installation, drain cleaning, and water heater repairs.',
                'rating' => 4.6,
                'total_jobs' => 51
            ]
        ];
        
        $query = "INSERT INTO technicians (name, email, phone, specialization, regions, address, latitude, longitude, bio, rating, total_jobs, status) 
                  VALUES (:name, :email, :phone, :specialization, :regions, :address, :latitude, :longitude, :bio, :rating, :total_jobs, 'available')";
        $stmt = $pdo->prepare($query);
        
        foreach ($technicians as $tech) {
            $stmt->execute($tech);
        }
        
        echo "Added " . count($technicians) . " sample technicians<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Check if transport_providers table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'transport_providers'")->fetchAll();
    if (count($result) == 0) {
        // Create transport_providers table
        $create_table = "CREATE TABLE transport_providers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            contact VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            regions TEXT,
            address TEXT,
            cost_per_km DECIMAL(10,2) DEFAULT 0,
            estimated_days INT DEFAULT 1,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($create_table);
        echo "Created transport_providers table<br>";
    }
    
    // Check if we already have transport providers
    $count = $pdo->query("SELECT COUNT(*) FROM transport_providers")->fetchColumn();
    if ($count > 0) {
        echo "Transport providers already exist in the database. Skipping sample data.<br>";
    } else {
        // Add sample transport providers
        $providers = [
            [
                'name' => 'ZamExpress Logistics',
                'description' => 'Fast and reliable delivery across Zambia',
                'contact' => '+260 977111222',
                'email' => 'info@zamexpress.co.zm',
                'regions' => 'Lusaka,Copperbelt,Central,Southern',
                'address' => '56 Independence Avenue, Lusaka',
                'cost_per_km' => 2.50,
                'estimated_days' => 2
            ],
            [
                'name' => 'Swift Couriers',
                'description' => 'Same-day delivery in major cities',
                'contact' => '+260 966333444',
                'email' => 'bookings@swiftcouriers.zm',
                'regions' => 'Lusaka,Copperbelt',
                'address' => '23 Cairo Road, Lusaka',
                'cost_per_km' => 3.75,
                'estimated_days' => 1
            ],
            [
                'name' => 'Zambia Transport Solutions',
                'description' => 'Nationwide delivery service with tracking',
                'contact' => '+260 955555666',
                'email' => 'support@zts.co.zm',
                'regions' => 'Lusaka,Copperbelt,Central,Eastern,Luapula,Muchinga,Northern,North-Western,Southern,Western',
                'address' => '78 Great East Road, Lusaka',
                'cost_per_km' => 2.00,
                'estimated_days' => 3
            ]
        ];
        
        $query = "INSERT INTO transport_providers (name, description, contact, email, regions, address, cost_per_km, estimated_days, status) 
                  VALUES (:name, :description, :contact, :email, :regions, :address, :cost_per_km, :estimated_days, 'active')";
        $stmt = $pdo->prepare($query);
        
        foreach ($providers as $provider) {
            $stmt->execute($provider);
        }
        
        echo "Added " . count($providers) . " sample transport providers<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "Sample data setup complete<br>";
?>