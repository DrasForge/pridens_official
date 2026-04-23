<?php
// admin_API/migrate_item_snapshots.php
require_once 'db.php';

try {
    echo "Starting Item Snapshot migration...\n";

    $sql = "ALTER TABLE merchant_order_items 
        ADD COLUMN srp_snapshot DECIMAL(15, 2) DEFAULT 0.00 AFTER price,
        ADD COLUMN item_exclusive_discount DECIMAL(15, 2) DEFAULT 0.00 AFTER srp_snapshot
    ";
    
    $pdo->exec($sql);
    echo "✓ merchant_order_items updated with snapshot columns\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Columns already exist.\n";
    } else {
        die("Migration failed: " . $e->getMessage() . "\n");
    }
}
?>
