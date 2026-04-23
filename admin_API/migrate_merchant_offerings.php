<?php
// admin_API/migrate_merchant_offerings.php
require_once 'db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchant_products` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL,
            `product_sku` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `category` ENUM('Products', 'Foods', 'Spots', 'Services') NOT NULL,
            `price` DECIMAL(15, 2) NOT NULL,
            `base_price` DECIMAL(15, 2) DEFAULT 0.00,
            `stock_quantity` INT DEFAULT 0,
            `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
            `is_featured` BOOLEAN DEFAULT FALSE,
            `image_path` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ merchant_products table created successfully\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
