<?php
include('includes/db.php');

try {
    // Drop the technicians table if it exists
    $pdo->exec("DROP TABLE IF EXISTS technicians");
    echo "Dropped existing technicians table.<br>";
    
    // Create technicians table with all required columns
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
    echo "Created new technicians table with all required columns.<br>";
    
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
    
    echo "Added " . count($technicians) . " sample technicians.<br>";
    echo "Table reset complete. <a href='technicians.php'>View Technicians</a> | <a href='register_technician.php'>Register as Technician</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>