<?php
// admin_API/migrate_product_availability.php
require_once 'db.php';

try {
    echo "Starting migration for Product Availability & Pre-order settings...\n";

    // Add columns if they don't exist
    $pdo->exec("ALTER TABLE merchant_products ADD COLUMN IF NOT EXISTS is_available BOOLEAN DEFAULT TRUE AFTER status");
    $pdo->exec("ALTER TABLE merchant_products ADD COLUMN IF NOT EXISTS is_preorder BOOLEAN DEFAULT FALSE AFTER is_available");
    $pdo->exec("ALTER TABLE merchant_products ADD COLUMN IF NOT EXISTS preorder_days INT DEFAULT 0 AFTER is_preorder");

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
