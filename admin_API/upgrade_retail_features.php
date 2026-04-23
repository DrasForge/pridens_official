<?php
// admin_API/upgrade_retail_features.php
require_once 'db.php';

try {
    echo "Upgrading Retail (Product) module with financial tracking and points...\n";

    // 1. Upgrade merchant_orders
    $sqlAlterOrders = "ALTER TABLE `merchant_orders` 
        ADD COLUMN `srp_total` DECIMAL(15, 2) DEFAULT 0.00 AFTER `total_amount`,
        ADD COLUMN `exclusive_discount` DECIMAL(15, 2) DEFAULT 0.00 AFTER `srp_total`,
        ADD COLUMN `voucher_discount` DECIMAL(15, 2) DEFAULT 0.00 AFTER `exclusive_discount`,
        ADD COLUMN `points_received` DECIMAL(15, 2) DEFAULT 0.00 AFTER `voucher_discount`,
        ADD COLUMN `system_fee` DECIMAL(15, 2) DEFAULT 0.00 AFTER `points_received`
    ";
    
    $pdo->exec($sqlAlterOrders);
    echo "✓ merchant_orders table upgraded with financial tracking columns\n";

    echo "Retail upgrade completed successfully!\n";
} catch (PDOException $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
?>
