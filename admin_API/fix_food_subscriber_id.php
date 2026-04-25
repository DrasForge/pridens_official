<?php
// admin_API/fix_food_subscriber_id.php
require_once 'db.php';

try {
    echo "Fixing merchant_food_orders.subscriber_id column type...\n";
    
    // Change INT to VARCHAR(20) to match subscribers.account_id
    $pdo->exec("ALTER TABLE merchant_food_orders MODIFY COLUMN subscriber_id VARCHAR(20) NOT NULL");
    
    echo "✓ subscriber_id changed to VARCHAR(20)\n";
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
