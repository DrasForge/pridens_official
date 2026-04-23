<?php
require_once 'admin_API/db.php';

try {
    echo "Fixing POS Stock and Inventory issues in live database...\n";

    $queries = [
        // 1. pos_products
        "ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `stock_quantity` INT NOT NULL DEFAULT 0 AFTER `status` ",
        "ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `low_stock_threshold` INT NOT NULL DEFAULT 5 AFTER `stock_quantity` ",
        "ALTER TABLE `pos_products` ADD COLUMN IF NOT EXISTS `base_price` DECIMAL(15, 2) DEFAULT 0.00 AFTER `price` ",

        // 2. pos_transaction_items
        "ALTER TABLE `pos_transaction_items` ADD COLUMN IF NOT EXISTS `cost_price_snapshot` DECIMAL(15, 2) DEFAULT 0.00 AFTER `price_snapshot` ",

        // 3. pos_inventory_logs
        "CREATE TABLE IF NOT EXISTS `pos_inventory_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `change_amount` INT NOT NULL,
            `action_type` ENUM('Restock', 'Adjustment', 'Sale', 'Void Sale') NOT NULL,
            `admin_id` INT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`product_id`) REFERENCES `pos_products`(`id`) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "Executed: " . substr($sql, 0, 60) . "...\n";
        } catch (Exception $e) {
            echo "Error/Note: " . $e->getMessage() . "\n";
        }
    }

    echo "POS Fixes complete.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
