<?php
// admin_API/migrate_spots.php
require_once 'db.php';

try {
    echo "Starting Spots System migration...\n";

    // 1. Create merchant_spots table
    $sql1 = "CREATE TABLE IF NOT EXISTS merchant_spots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        spot_name VARCHAR(255) NOT NULL,
        spot_tagline VARCHAR(50) NOT NULL,
        spot_description TEXT,
        spot_profile VARCHAR(255) DEFAULT NULL,
        spot_cover VARCHAR(255) DEFAULT NULL,
        operating_hours TEXT,
        amenities TEXT,
        barangay VARCHAR(100),
        latitude DECIMAL(10, 7),
        longitude DECIMAL(10, 7),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql1);
    echo "✓ merchant_spots table created\n";

    // 2. Create merchant_spot_media table
    $sql2 = "CREATE TABLE IF NOT EXISTS merchant_spot_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spot_id INT NOT NULL,
        section ENUM('interior', 'exterior') NOT NULL,
        media_type ENUM('image', 'video') NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (spot_id) REFERENCES merchant_spots(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql2);
    echo "✓ merchant_spot_media table created\n";

    echo "Spots Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
