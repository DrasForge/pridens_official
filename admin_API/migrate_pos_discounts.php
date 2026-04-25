<?php
// admin_API/migrate_pos_discounts.php
require_once 'db.php';

try {
    echo "Updating order tables for discount tracking...\n";

    // 1. merchant_orders (Check if they already exist, usually they do)
    // 2. merchant_food_orders
    $table = 'merchant_food_orders';
    $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'voucher_discount'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `voucher_discount` DECIMAL(15,2) DEFAULT 0.00 AFTER `exclusive_discount` ");
        echo "✓ Added 'voucher_discount' to $table\n";
    }

    // 3. merchant_service_orders
    $table = 'merchant_service_orders';
    $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'voucher_discount'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `voucher_discount` DECIMAL(10,2) DEFAULT 0.00 AFTER `exclusive_discount` ");
        echo "✓ Added 'voucher_discount' to $table\n";
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
