<?php
// admin_API/migrate_prices.php
require_once 'db.php';

try {
    // 1. Add base_price to pos_products
    $pdo->exec("ALTER TABLE `pos_products` ADD COLUMN `base_price` DECIMAL(15,2) DEFAULT 0.00 AFTER `name` ");
    echo "Added base_price to pos_products.<br>";

    // 2. Add cost_price_snapshot to pos_transaction_items
    $pdo->exec("ALTER TABLE `pos_transaction_items` ADD COLUMN `cost_price_snapshot` DECIMAL(15,2) DEFAULT 0.00 AFTER `price_snapshot` ");
    echo "Added cost_price_snapshot to pos_transaction_items.<br>";

    echo "<h3>Migration Completed Successfully</h3>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<h3>Migration already applied.</h3>";
    } else {
        echo "<h3>Migration Failed: " . $e->getMessage() . "</h3>";
    }
}
?>
