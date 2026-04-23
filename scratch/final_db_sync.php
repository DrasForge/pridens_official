<?php
require_once 'admin_API/db.php';

try {
    echo "Running FINAL comprehensive database sync...\n";

    $migrations = [
        // Subscribers table
        "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS plan_id INT DEFAULT NULL AFTER sales_invoice_number",
        "ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS billing_day TINYINT DEFAULT NULL AFTER plan_id",
        "ALTER TABLE subscribers ADD FOREIGN KEY IF NOT EXISTS (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL",
        
        // Subscription Plans table
        "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS has_insurance BOOLEAN DEFAULT TRUE AFTER plan_name",
        "ALTER TABLE subscription_plans ADD COLUMN IF NOT EXISTS contestability_period_days INT DEFAULT 0 AFTER insurance_term_months",
        
        // POS Transactions table
        "ALTER TABLE pos_transactions ADD COLUMN IF NOT EXISTS subscriber_account_id VARCHAR(20) DEFAULT NULL AFTER agent_referral_code"
    ];

    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            echo "Success: " . substr($sql, 0, 60) . "...\n";
        } catch (Exception $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }

    echo "Sync Complete. Verifying specific columns now...\n";
    
    // Verify subscribers columns
    $stmt = $pdo->query("DESCRIBE subscribers");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('plan_id', $cols) && in_array('billing_day', $cols)) {
        echo "✓ Subscribers table is now fully synchronized.\n";
    } else {
        echo "✗ Error: Subscribers table still missing columns.\n";
    }

    // Verify plans columns
    $stmt = $pdo->query("DESCRIBE subscription_plans");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('has_insurance', $cols) && in_array('contestability_period_days', $cols)) {
        echo "✓ Subscription Plans table is now fully synchronized.\n";
    } else {
        echo "✗ Error: Subscription Plans table still missing columns.\n";
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
