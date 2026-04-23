<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE merchant_orders ADD COLUMN pridens_profit_amount DECIMAL(15, 2) DEFAULT 0.00 AFTER points_earned");
    echo "✓ pridens_profit_amount added\n";
} catch(Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
