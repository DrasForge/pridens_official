<?php
// admin_API/migrate_commissions.php
require_once 'db.php';

try {
    // 1. Alter POS Transactions to include subscriber logic
    $pdo->exec("ALTER TABLE pos_transactions ADD COLUMN IF NOT EXISTS subscriber_account_id VARCHAR(20) DEFAULT NULL AFTER agent_referral_code");

    // 2. Alter Agents table to officially capture upline sponsor
    $pdo->exec("ALTER TABLE agents ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) DEFAULT NULL AFTER agent_position");

    // 3. Create agent_commissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `agent_commissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id` VARCHAR(50) NOT NULL,
            `source_account_id` VARCHAR(50) NOT NULL,
            `transaction_id` INT DEFAULT NULL,
            `commission_type` ENUM('One-Time', 'Residual', 'Collection Fee', 'Position-Based') NOT NULL,
            `level` INT DEFAULT NULL,
            `amount` DECIMAL(15, 2) NOT NULL,
            `status` ENUM('Outright', 'Unpaid', 'Pending Encashment', 'Paid') DEFAULT 'Unpaid',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `cleared_at` DATETIME DEFAULT NULL
        )
    ");

    echo "Migration for Commissions Engine completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
