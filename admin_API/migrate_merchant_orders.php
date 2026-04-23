<?php
// admin_API/migrate_merchant_orders.php
require_once 'db.php';

try {
    echo "Starting Merchant Order System migration...\n";

    // 1. Create merchant_orders table
    $sql = "CREATE TABLE IF NOT EXISTS merchant_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_sn VARCHAR(50) NOT NULL UNIQUE,
        merchant_id INT NOT NULL,
        subscriber_id VARCHAR(20) NOT NULL,
        total_amount DECIMAL(15, 2) NOT NULL,
        shipping_fee DECIMAL(15, 2) DEFAULT 0.00,
        discount_amount DECIMAL(15, 2) DEFAULT 0.00,
        status ENUM('Unpaid', 'To Ship', 'Shipping', 'Completed', 'Cancelled', 'Refunded') DEFAULT 'Unpaid',
        payment_method ENUM('COD', 'Bank', 'E-Wallet', 'Voucher', 'Paid Already') DEFAULT 'Paid Already',
        shipping_address_snapshot TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        shipped_at DATETIME DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE,
        FOREIGN KEY (subscriber_id) REFERENCES subscribers(account_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "✓ merchant_orders table created\n";

    // 2. Create merchant_order_items table
    $sql = "CREATE TABLE IF NOT EXISTS merchant_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        model_id INT DEFAULT NULL,
        name_snapshot VARCHAR(255) NOT NULL,
        variation_snapshot VARCHAR(255) DEFAULT NULL,
        price DECIMAL(15, 2) NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        subtotal DECIMAL(15, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES merchant_orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "✓ merchant_order_items table created\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
