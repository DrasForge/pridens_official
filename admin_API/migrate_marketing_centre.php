<?php
// admin_API/migrate_marketing_centre.php
require_once 'db.php';

try {
    echo "Starting Marketing Centre migration...\n";

    // 1. Create merchant_promotions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_promotions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `status` ENUM('Enabled', 'Disabled') DEFAULT 'Enabled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_promotions` created/verified.\n";

    // 2. Create merchant_promotion_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_promotion_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `promo_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `model_id` INT DEFAULT NULL,
        `promo_price` DECIMAL(15, 2) NOT NULL,
        `discount_percent` INT DEFAULT 0,
        FOREIGN KEY (`promo_id`) REFERENCES `merchant_promotions`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`model_id`) REFERENCES `product_models`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_promotion_items` created/verified.\n";

    echo "\nMarketing Centre Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
