<?php
// admin_API/migrate_dual_sku.php
require_once 'db.php';

try {
    echo "Starting Dual-SKU migration...\n";

    // 1. Update merchant_products
    $stmt = $pdo->query("SHOW COLUMNS FROM `merchant_products` LIKE 'pridens_sku'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `merchant_products` ADD COLUMN `pridens_sku` VARCHAR(50) UNIQUE DEFAULT NULL AFTER `merchant_id` ");
        $pdo->exec("ALTER TABLE `merchant_products` CHANGE COLUMN `product_sku` `seller_sku` VARCHAR(50) DEFAULT NULL");
        echo "✓ merchant_products updated (pridens_sku added, product_sku renamed to seller_sku)\n";
    }

    // 2. Update product_models
    $stmt = $pdo->query("SHOW COLUMNS FROM `product_models` LIKE 'pridens_sku'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `product_models` ADD COLUMN `pridens_sku` VARCHAR(50) UNIQUE DEFAULT NULL AFTER `product_id` ");
        $pdo->exec("ALTER TABLE `product_models` CHANGE COLUMN `sku` `seller_sku` VARCHAR(50) DEFAULT NULL");
        echo "✓ product_models updated (pridens_sku added, sku renamed to seller_sku)\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
