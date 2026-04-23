<?php
// admin_API/upgrade_food_features.php
require_once 'db.php';

try {
    echo "Upgrading Food module with serial numbers and financial tracking...\n";

    // 1. Upgrade merchant_food_orders
    $sqlAlterOrders = "ALTER TABLE `merchant_food_orders` 
        ADD COLUMN `order_sn` VARCHAR(50) DEFAULT NULL AFTER `id`,
        ADD COLUMN `srp_total` DECIMAL(15, 2) DEFAULT 0.00,
        ADD COLUMN `exclusive_discount` DECIMAL(15, 2) DEFAULT 0.00,
        ADD COLUMN `voucher_discount` DECIMAL(15, 2) DEFAULT 0.00,
        ADD COLUMN `points_received` DECIMAL(15, 2) DEFAULT 0.00,
        ADD COLUMN `system_fee` DECIMAL(15, 2) DEFAULT 0.00,
        ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'Voucher/Points'";
    $pdo->exec($sqlAlterOrders);
    echo "✓ merchant_food_orders upgraded\n";

    // 2. Create Food Exclusive Offers (Deduction system)
    $sqlFoodOffers = "CREATE TABLE IF NOT EXISTS `merchant_food_exclusive_offers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `plan_id` INT NOT NULL,
        `food_id` INT NOT NULL,
        `deduction_percent` DECIMAL(5, 2) DEFAULT 0.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_offer` (`merchant_id`, `plan_id`, `food_id`)
    )";
    $pdo->exec($sqlFoodOffers);
    echo "✓ merchant_food_exclusive_offers created\n";

    echo "Migration completed!\n";
} catch (PDOException $e) {
    echo "Note: " . $e->getMessage() . "\n";
}
?>
