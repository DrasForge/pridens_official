<?php
// admin_API/migrate_payment_channels.php
require_once 'db.php';

try {
    echo "Starting Payment Channels migration...\n";

    // 1. Create merchant_payment_channels table
    $sql = "CREATE TABLE IF NOT EXISTS merchant_payment_channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        merchant_id INT NOT NULL,
        type ENUM('Bank', 'E-Wallet') NOT NULL,
        provider_name VARCHAR(100) NOT NULL,
        account_name VARCHAR(255) NOT NULL,
        account_number VARCHAR(100) NOT NULL,
        qr_code_path VARCHAR(255) DEFAULT NULL,
        is_enabled BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "✓ merchant_payment_channels table created\n";

    // 2. Add allow_direct_payment to merchant_products
    // First check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM `merchant_products` LIKE 'allow_direct_payment'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE `merchant_products` ADD COLUMN `allow_direct_payment` BOOLEAN DEFAULT FALSE AFTER `is_exclusive` ");
        echo "✓ merchant_products updated with allow_direct_payment column\n";
    } else {
        echo "- Column allow_direct_payment already exists in merchant_products\n";
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
