<?php
// admin_API/alter_service_orders_v3.php
require_once 'db.php';

try {
    echo "Adding points_earned to service orders...\n";
    $pdo->exec("ALTER TABLE merchant_service_orders ADD COLUMN points_earned DECIMAL(10,2) DEFAULT 0.00 AFTER payable_amount");
    echo "✓ points_earned column added\n";
} catch (PDOException $e) {
    die("Alter failed: " . $e->getMessage() . "\n");
}
?>
