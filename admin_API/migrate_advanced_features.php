<?php
// admin_API/migrate_advanced_features.php
require_once 'db.php';

try {
    echo "Starting advanced features migration...\n";

    // 1. Add Shipping & Payment toggles to merchant_products
    $columns = [
        'allow_local_deliv'      => "TINYINT(1) DEFAULT 1",
        'allow_nationwide_deliv' => "TINYINT(1) DEFAULT 1",
        'allow_pickup'           => "TINYINT(1) DEFAULT 0",
        'allow_cod'              => "TINYINT(1) DEFAULT 1",
        'allow_bank'             => "TINYINT(1) DEFAULT 1",
        'allow_ewallet'          => "TINYINT(1) DEFAULT 1"
    ];

    foreach ($columns as $col => $type) {
        $check = $pdo->query("SHOW COLUMNS FROM `merchant_products` LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `merchant_products` ADD COLUMN `$col` $type");
            echo "✓ Added column `$col` to `merchant_products`.\n";
        }
    }

    // 2. Create merchant_addon_deals table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_addon_deals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `min_spend` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
        `discount_percent` INT NOT NULL DEFAULT 0,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `status` ENUM('Enabled', 'Disabled') DEFAULT 'Enabled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_addon_deals` created/verified.\n";

    // 3. Create merchant_addon_items table
    // These are the "selected items" that get the discount once the min_spend is met
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_addon_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `addon_deal_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        FOREIGN KEY (`addon_deal_id`) REFERENCES `merchant_addon_deals`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_addon_items` created/verified.\n";

    echo "\nAdvanced Features Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
