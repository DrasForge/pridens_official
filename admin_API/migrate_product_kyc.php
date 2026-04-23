<?php
// admin_API/migrate_product_kyc.php
require_once 'db.php';

try {
    echo "Starting Product KYC migration...\n";

    $sql = "CREATE TABLE IF NOT EXISTS `merchant_product_kyc` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL UNIQUE,
        `reg_type` ENUM('None', 'DTI', 'FDA', 'ICC', 'PS Mark', 'NTC', 'SEC') DEFAULT 'None',
        `license_number` VARCHAR(100) DEFAULT NULL,
        `expiry_date` DATE DEFAULT NULL,
        `manufacturing_origin` ENUM('Local-PH', 'Imported') DEFAULT 'Local-PH',
        `usage_warnings` TEXT DEFAULT NULL,
        `is_certified` BOOLEAN DEFAULT FALSE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✓ merchant_product_kyc table created/updated\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
