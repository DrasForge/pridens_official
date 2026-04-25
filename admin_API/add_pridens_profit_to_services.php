<?php
// admin_API/add_pridens_profit_to_services.php
require_once 'db.php';

try {
    echo "Adding pridens_profit column to merchant_service_orders...\n";
    $pdo->exec("ALTER TABLE merchant_service_orders ADD COLUMN pridens_profit DECIMAL(10, 2) DEFAULT 0.00 AFTER points_earned");
    echo "✓ pridens_profit added\n";
    echo "Migration completed!\n";
} catch (PDOException $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
?>
