<?php
// admin_API/migrate_service_orders.php
require_once 'db.php';

try {
    echo "Starting Service Orders System migration...\n";

    $sql = "CREATE TABLE IF NOT EXISTS merchant_service_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_sn VARCHAR(50) NOT NULL UNIQUE,
        merchant_id INT NOT NULL,
        service_id INT NOT NULL,
        subscriber_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        exclusive_discount DECIMAL(10,2) DEFAULT 0.00,
        payable_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(100) NOT NULL,
        service_method VARCHAR(100) NOT NULL,
        schedule_date DATE DEFAULT NULL,
        schedule_time TIME DEFAULT NULL,
        status ENUM('Pending', 'Confirmed', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending',
        payment_status ENUM('Unpaid', 'Paid', 'Refunded') DEFAULT 'Unpaid',
        proof_of_payment VARCHAR(255) DEFAULT NULL,
        customer_notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES merchant_services(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "✓ merchant_service_orders table created\n";

    echo "Service Orders Migration completed!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
