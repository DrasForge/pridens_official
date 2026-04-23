<?php
// admin_API/migrate_pos_stock.php
require_once 'db.php';

header('Content-Type: text/plain');

try {
    echo "Starting Migration...\n";

    // 1. Add columns to pos_products
    // We use a check to see if columns exist to prevent errors on re-run
    $checkCols = $pdo->query("SHOW COLUMNS FROM `pos_products` LIKE 'stock_quantity'");
    if (!$checkCols->fetch()) {
        $pdo->exec("ALTER TABLE `pos_products` 
                    ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 0,
                    ADD COLUMN `low_stock_threshold` INT NOT NULL DEFAULT 5");
        echo "Added stock columns to pos_products table.\n";
    } else {
        echo "Stock columns already exist in pos_products.\n";
    }

    // 2. Create pos_inventory_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pos_inventory_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `change_amount` INT NOT NULL,
        `action_type` ENUM('Restock', 'Adjustment', 'Sale', 'Void Sale') NOT NULL,
        `admin_id` INT DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `pos_products`(`id`) ON DELETE CASCADE
    )");
    echo "Created/Verified pos_inventory_logs table.\n";

    echo "Migration Completed Successfully.\n";
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
?>
