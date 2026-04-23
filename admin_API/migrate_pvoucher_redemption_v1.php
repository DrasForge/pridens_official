<?php
// admin_API/migrate_pvoucher_redemption_v1.php
require_once 'db.php';

try {
    // 1. Storage for generated vouchers that can be scanned
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `p_vouchers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `voucher_code` VARCHAR(50) NOT NULL UNIQUE,
            `account_id` VARCHAR(20) NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `status` ENUM('Active', 'Redeemed', 'Expired') DEFAULT 'Active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `expires_at` DATETIME DEFAULT NULL,
            `redeemed_at` DATETIME DEFAULT NULL,
            `redeemed_by` INT DEFAULT NULL, -- Merchant ID
            FOREIGN KEY (`account_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
        )
    ");
    echo "✓ p_vouchers table created\n";

    // 2. Ledger for disbursements (The payout queue)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `merchant_pvoucher_ledgers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `merchant_id` INT NOT NULL,
            `voucher_id` INT NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `handling_fee` DECIMAL(15,2) DEFAULT 0.00,
            `total_payout` DECIMAL(15,2) NOT NULL,
            `payout_status` ENUM('Pending', 'In-Process', 'Paid', 'Failed') DEFAULT 'Pending',
            `paymongo_id` VARCHAR(100) DEFAULT NULL,
            `paid_at` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`merchant_id`) REFERENCES `merchants`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`voucher_id`) REFERENCES `p_vouchers`(`id`) ON DELETE CASCADE
        )
    ");
    echo "✓ merchant_pvoucher_ledgers table created\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
