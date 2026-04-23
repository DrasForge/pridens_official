<?php
require 'c:/Pridens_trading_co/admin_API/db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `subscriber_pvoucher_ledger` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `account_id` VARCHAR(20) NOT NULL,
        `amount` DECIMAL(15, 2) NOT NULL,
        `type` ENUM('Credit', 'Debit') NOT NULL,
        `description` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`account_id`) REFERENCES `subscribers`(`account_id`) ON DELETE CASCADE
    )");
    echo "Table subscriber_pvoucher_ledger created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
