<?php
// admin_API/migrate_voucher_centre.php
require_once 'db.php';

try {
    echo "Starting Voucher Centre migration...\n";

    // 1. Cleanup old / experimental tables
    $pdo->exec("DROP TABLE IF EXISTS `merchant_addon_items` ");
    $pdo->exec("DROP TABLE IF EXISTS `merchant_addon_deals` ");
    echo "✓ Cleaned up old addon deal tables.\n";

    // 2. Create merchant_vouchers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_vouchers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `voucher_code` VARCHAR(50) NOT NULL,
        `type` ENUM('Standard', 'New User', 'Follower', 'Frequent Buyer') DEFAULT 'Standard',
        `reward_type` ENUM('Percentage', 'Fixed') DEFAULT 'Percentage',
        `reward_value` DECIMAL(15, 2) NOT NULL,
        `min_spend` DECIMAL(15, 2) DEFAULT 0.00,
        `usage_limit_total` INT DEFAULT 0,
        `usage_limit_per_user` INT DEFAULT 1,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `target_type` ENUM('All', 'Selected') DEFAULT 'All',
        `status` ENUM('Enabled', 'Disabled') DEFAULT 'Enabled',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_vouchers` created/verified.\n";

    // 3. Create merchant_voucher_items table (for target_type = 'Selected')
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_voucher_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `voucher_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        FOREIGN KEY (`voucher_id`) REFERENCES `merchant_vouchers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `merchant_products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_voucher_items` created/verified.\n";

    // 4. Create merchant_followers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_followers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `merchant_id` INT NOT NULL,
        `subscriber_id` VARCHAR(20) NOT NULL,
        `followed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_follow` (`merchant_id`, `subscriber_id`),
        FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_followers` created/verified.\n";

    // 5. Create merchant_voucher_usage table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `merchant_voucher_usage` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `voucher_id` INT NOT NULL,
        `subscriber_id` VARCHAR(20) NOT NULL,
        `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`voucher_id`) REFERENCES `merchant_vouchers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✓ Table `merchant_voucher_usage` created/verified.\n";

    echo "\nVoucher Centre Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
