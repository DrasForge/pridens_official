<?php
// admin_API/migrate_order_breakdown.php
require_once 'db.php';

try {
    echo "Starting Order Breakdown migration...\n";

    // Add financial columns to merchant_orders
    $sql = "ALTER TABLE merchant_orders 
        ADD COLUMN srp_total DECIMAL(15, 2) DEFAULT 0.00 AFTER total_amount,
        ADD COLUMN exclusive_discount_amount DECIMAL(15, 2) DEFAULT 0.00 AFTER srp_total,
        ADD COLUMN voucher_discount_amount DECIMAL(15, 2) DEFAULT 0.00 AFTER exclusive_discount_amount,
        ADD COLUMN total_discount_deducted DECIMAL(15, 2) DEFAULT 0.00 AFTER voucher_discount_amount,
        ADD COLUMN points_earned DECIMAL(15, 2) DEFAULT 0.00 AFTER total_discount_deducted
    ";
    
    $pdo->exec($sql);
    echo "✓ merchant_orders table updated with financial breakdown columns\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Columns already exist.\n";
    } else {
        die("Migration failed: " . $e->getMessage() . "\n");
    }
}
?>
