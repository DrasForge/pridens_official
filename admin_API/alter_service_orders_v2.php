<?php
// admin_API/alter_service_orders_v2.php
require_once 'db.php';

try {
    echo "Correcting subscriber_id type in merchant_service_orders...\n";

    // 1. Drop foreign keys if any (I didn't explicitly name one but usually it exists)
    // Actually I'll just change the column type.
    $pdo->exec("ALTER TABLE merchant_service_orders MODIFY COLUMN subscriber_id VARCHAR(20) NOT NULL");
    
    echo "✓ subscriber_id changed to VARCHAR(20)\n";
} catch (PDOException $e) {
    die("Alter failed: " . $e->getMessage() . "\n");
}
?>
