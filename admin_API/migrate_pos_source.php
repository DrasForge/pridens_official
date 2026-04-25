<?php
// admin_API/migrate_pos_source.php
require_once 'db.php';

try {
    echo "Updating order tables for POS source tracking...\n";

    $tables = ['merchant_orders', 'merchant_food_orders', 'merchant_service_orders'];
    
    foreach ($tables as $table) {
        // Check if source column exists
        $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'source'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `source` ENUM('Online', 'POS') DEFAULT 'Online' AFTER `status` ");
            echo "✓ Added 'source' to $table\n";
        }

        // Check if pos_transaction_id exists
        $checkId = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'pos_transaction_id'")->fetch();
        if (!$checkId) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `pos_transaction_id` VARCHAR(50) DEFAULT NULL AFTER `source` ");
            echo "✓ Added 'pos_transaction_id' to $table\n";
        }
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
