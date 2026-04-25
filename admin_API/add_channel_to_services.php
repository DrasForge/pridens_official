<?php
// admin_API/add_channel_to_services.php
require_once 'db.php';

try {
    echo "Adding payment_channel_id to merchant_service_orders...\n";
    $pdo->exec("ALTER TABLE merchant_service_orders ADD COLUMN payment_channel_id INT DEFAULT NULL AFTER pridens_profit");
    echo "✓ payment_channel_id added\n";
    echo "Migration completed!\n";
} catch (PDOException $e) {
    echo "Notice: " . $e->getMessage() . "\n";
}
?>
