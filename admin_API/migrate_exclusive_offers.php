<?php
// admin_API/migrate_exclusive_offers.php
require_once 'db.php';

try {
    echo "Starting migration for Merchant Exclusive Offers...\n";

    // 1. Add is_exclusive to merchant_products
    $pdo->exec("ALTER TABLE merchant_products ADD COLUMN IF NOT EXISTS is_exclusive BOOLEAN DEFAULT FALSE");

    // 2. Create merchant_exclusive_offers table
    $sql = "CREATE TABLE IF NOT EXISTS merchant_exclusive_offers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        plan_id INT NOT NULL,
        discount_percent DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `merchant_plan` (`merchant_id`, `plan_id`),
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
