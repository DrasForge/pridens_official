<?php
// admin_API/migrate_merchant_v7.php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE merchants 
        ADD COLUMN IF NOT EXISTS pvoucher_handling_fee DECIMAL(15,2) DEFAULT 0.00
    ");
    echo "✓ merchants table upgraded with pvoucher_handling_fee\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
