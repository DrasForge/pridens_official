<?php
// sync_db.php - Open this in your browser: http://localhost:8001/sync_db.php
require_once 'admin_API/db.php';

header('Content-Type: text/plain');

echo "--- Unified Database Sync Script ---\n";
echo "Connected to: " . $db . " on " . $host . "\n\n";

$migrations = [
    // 1. Subscribers table expansion
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS plan_id INT DEFAULT NULL AFTER sales_invoice_number",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS billing_day TINYINT DEFAULT NULL AFTER plan_id",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_policy_number VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_document_path VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_ready_for_approval BOOLEAN DEFAULT FALSE",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_approved_by INT DEFAULT NULL",
    "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_approved_at TIMESTAMP NULL DEFAULT NULL",

    // 2. POS Products expansion (Fixes the "Unknown column stock_quantity" error)
    "ALTER TABLE pos_products ADD COLUMN IF NOT EXISTS stock_quantity INT NOT NULL DEFAULT 0",
    "ALTER TABLE pos_products ADD COLUMN IF NOT EXISTS low_stock_threshold INT NOT NULL DEFAULT 5",
    "ALTER TABLE pos_products ADD COLUMN IF NOT EXISTS base_price DECIMAL(15, 2) DEFAULT 0.00",

    // 3. POS Transaction Items expansion
    "ALTER TABLE pos_transaction_items ADD COLUMN IF NOT EXISTS cost_price_snapshot DECIMAL(15, 2) DEFAULT 0.00",

    // 4. Admins expansion
    "ALTER TABLE admins ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) DEFAULT 'Admin'",
    "ALTER TABLE admins ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) DEFAULT 'User'",

    // 5. Create Inventory Logs table
    "CREATE TABLE IF NOT EXISTS `pos_inventory_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `change_amount` INT NOT NULL,
        `action_type` ENUM('Restock', 'Adjustment', 'Sale', 'Void Sale') NOT NULL,
        `admin_id` INT DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `pos_products`(`id`) ON DELETE CASCADE
    )",

    // 6. Subscription Plans expansion
    "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS has_insurance BOOLEAN DEFAULT TRUE",
    "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS contestability_period_days INT DEFAULT 0"
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        echo "SUCCESS: " . substr($sql, 0, 70) . "...\n";
    } catch (Exception $e) {
        echo "LOG: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Sync Complete! ---\n";
echo "Please refresh your dashboard and POS terminal now.\n";
?>
