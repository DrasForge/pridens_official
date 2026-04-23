<?php
// admin_API/migrate_p2p_verification.php
require_once 'db.php';

try {
    echo "Starting P2P Verification migration...\n";

    // 1. Update merchant_orders status ENUM and add proof fields
    $pdo->exec("ALTER TABLE merchant_orders 
        MODIFY COLUMN status ENUM('Unpaid', 'Payment Sent', 'To Ship', 'Shipping', 'Completed', 'Cancelled', 'Refunded') DEFAULT 'Unpaid',
        ADD COLUMN payment_proof_path VARCHAR(255) DEFAULT NULL AFTER payment_method,
        ADD COLUMN payment_verified_at DATETIME DEFAULT NULL AFTER payment_proof_path,
        ADD COLUMN payment_channel_id INT DEFAULT NULL AFTER payment_verified_at
    ");
    echo "✓ merchant_orders table updated with P2P fields\n";

    // 2. Add foreign key for payment_channel_id
    $pdo->exec("ALTER TABLE merchant_orders
        ADD CONSTRAINT fk_order_payment_channel 
        FOREIGN KEY (payment_channel_id) REFERENCES merchant_payment_channels(id) ON DELETE SET NULL
    ");
    echo "✓ Foreign key added for payment_channel_id\n";

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
?>
