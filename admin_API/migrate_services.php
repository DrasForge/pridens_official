<?php
// admin_API/migrate_services.php
require_once 'db.php';

try {
    echo "Starting Services System migration...\n";

    // 1. Create merchant_services table
    $sql1 = "CREATE TABLE IF NOT EXISTS merchant_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        service_name VARCHAR(255) NOT NULL,
        tagline VARCHAR(50) DEFAULT NULL,
        description TEXT,
        category_id INT DEFAULT NULL,
        price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        duration_minutes INT DEFAULT 60,
        cover_photo VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql1);
    echo "✓ merchant_services table created\n";

    // 2. Create merchant_service_media table
    $sql2 = "CREATE TABLE IF NOT EXISTS merchant_service_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        section ENUM('samples', 'process', 'requirements') NOT NULL,
        media_type ENUM('image', 'video') NOT NULL,
        slot_index INT NOT NULL DEFAULT 0,
        file_path VARCHAR(255) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES merchant_services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql2);
    echo "✓ merchant_service_media table created\n";

    // 3. Create merchant_service_inclusions table
    $sql3 = "CREATE TABLE IF NOT EXISTS merchant_service_inclusions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_id INT NOT NULL,
        content VARCHAR(255) NOT NULL,
        FOREIGN KEY (service_id) REFERENCES merchant_services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql3);
    echo "✓ merchant_service_inclusions table created\n";

    echo "Services Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
