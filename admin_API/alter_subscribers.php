<?php
require 'c:/Pridens_trading_co/admin_API/db.php';
$queries = [
    "ALTER TABLE subscribers ADD COLUMN approved_by VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN insurance_approved_by VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN insurance_approved_at TIMESTAMP NULL DEFAULT NULL"
];

foreach ($queries as $sql) {
    try {
        $pdo->exec($sql);
        echo "Success: $sql\n";
    } catch (Exception $e) {
        echo "Skipped: " . $e->getMessage() . "\n";
    }
}
