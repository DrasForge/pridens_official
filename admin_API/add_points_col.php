<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE merchant_orders ADD COLUMN points_earned DECIMAL(15, 2) DEFAULT 0.00 AFTER total_discount_deducted");
    echo "✓ points_earned added\n";
} catch(Exception $e) {
    echo $e->getMessage() . "\n";
}
