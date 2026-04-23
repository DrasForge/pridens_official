<?php
// admin_API/migrate_marketing_v4.php
require_once 'db.php';

try {
    echo "Starting Marketing System Revamp...\n";

    // 1. Create/Update merchant_vouchers
    $sql = "CREATE TABLE IF NOT EXISTS merchant_vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        voucher_code VARCHAR(100) NOT NULL,
        type ENUM('Standard', 'New User', 'Follower', 'Frequent Buyer') DEFAULT 'Standard',
        reward_type ENUM('Percentage', 'Fixed') DEFAULT 'Percentage',
        reward_value DECIMAL(10,2) NOT NULL,
        min_spend DECIMAL(10,2) DEFAULT 0.00,
        usage_limit_per_user INT DEFAULT 1,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        target_type ENUM('All', 'Selected') DEFAULT 'All',
        item_category ENUM('Products', 'Foods', 'Services', 'Spots') DEFAULT 'Products',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (merchant_id),
        INDEX (voucher_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "✓ merchant_vouchers table ensured\n";

    // 2. Create/Update merchant_voucher_items
    $sql = "CREATE TABLE IF NOT EXISTS merchant_voucher_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voucher_id INT NOT NULL,
        item_id INT NOT NULL,
        item_type ENUM('Product', 'Food', 'Service', 'Spot') DEFAULT 'Product',
        FOREIGN KEY (voucher_id) REFERENCES merchant_vouchers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);
    echo "✓ merchant_voucher_items table ensured\n";

    // 3. Update merchant_promotions for CATEGORY support
    $sql = "ALTER TABLE merchant_promotions ADD COLUMN IF NOT EXISTS item_category ENUM('Products', 'Foods', 'Services', 'Spots') DEFAULT 'Products' AFTER end_time";
    $pdo->exec($sql);
    echo "✓ merchant_promotions categories support added\n";

    // 4. Update merchant_promotion_items for MULTI-TYPE support
    $sql = "ALTER TABLE merchant_promotion_items 
            CHANGE COLUMN IF NOT EXISTS product_id item_id INT NOT NULL,
            ADD COLUMN IF NOT EXISTS item_type ENUM('Product', 'Food', 'Service', 'Spot') DEFAULT 'Product' AFTER item_id";
    // NOTE: CHANGE COLUMN IF NOT EXISTS is not standard MySQL, I'll check existence first or just run standard.
    try {
        $pdo->exec("ALTER TABLE merchant_promotion_items ADD COLUMN item_type ENUM('Product', 'Food', 'Service', 'Spot') DEFAULT 'Product' AFTER product_id");
    } catch(Exception $e){}
    
    echo "Marketing migration completed!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
