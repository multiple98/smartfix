<?php
include('includes/db.php');

try {
    // Check if technicians table exists
    $result = $pdo->query("SHOW TABLES LIKE 'technicians'")->fetchAll();
    
    if (count($result) > 0) {
        // Check if bio column exists
        $columns = $pdo->query("SHOW COLUMNS FROM technicians LIKE 'bio'")->fetchAll();
        
        if (count($columns) == 0) {
            // Add bio column if it doesn't exist
            $pdo->exec("ALTER TABLE technicians ADD COLUMN bio TEXT AFTER status");
            echo "Added 'bio' column to technicians table.<br>";
            
            // Update existing technicians with sample bios
            $bios = [
                "Experienced technician with expertise in various repair services. Committed to providing quality work and excellent customer service.",
                "Professional repair specialist with a focus on efficient and reliable solutions. Dedicated to solving technical problems with precision.",
                "Skilled technician with years of experience in the field. Known for attention to detail and comprehensive repair services.",
                "Certified repair expert offering professional services with a customer-first approach. Specializes in quick and effective solutions.",
                "Knowledgeable technician with extensive training and experience. Committed to delivering high-quality repair services."
            ];
            
            // Get all technicians
            $technicians = $pdo->query("SELECT id FROM technicians")->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($technicians) > 0) {
                $stmt = $pdo->prepare("UPDATE technicians SET bio = ? WHERE id = ?");
                
                foreach ($technicians as $index => $id) {
                    $bio_index = $index % count($bios);
                    $stmt->execute([$bios[$bio_index], $id]);
                }
                
                echo "Updated " . count($technicians) . " technicians with sample bio information.<br>";
            }
        } else {
            echo "Bio column already exists in technicians table.<br>";
        }
    } else {
        echo "Technicians table doesn't exist. Please run add_sample_technicians.php first.<br>";
    }
    
    echo "Table check complete. <a href='run_setup.php'>Run full setup</a> | <a href='technicians.php'>View Technicians</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>