<?php
// admin_API/migrate_subscribers_billing.php
require_once 'db.php';

try {
    echo "Starting migration...\n";

    // 1. Add columns to subscribers table if they don't exist
    $pdo->exec("ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS plan_id INT DEFAULT NULL AFTER sales_invoice_number");
    $pdo->exec("ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS billing_day TINYINT DEFAULT NULL AFTER plan_id");
    
    // 2. Update insurance_status ENUM
    // Note: In MySQL, you can't easily use IF NOT EXISTS for MODIFY COLUMN ENUM, 
    // so we'll just try to modify it.
    $pdo->exec("ALTER TABLE subscribers MODIFY COLUMN insurance_status ENUM('Active', 'Pending', 'N/A') DEFAULT 'Pending'");
    
    // 3. Add Foreign Key if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE subscribers ADD CONSTRAINT fk_subscriber_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL");
    } catch (Exception $e) {
        // Likely already exists
        echo "Note: Foreign key might already exist or could not be added: " . $e->getMessage() . "\n";
    }

    // 4. Backfill existing subscribers
    echo "Backfilling existing subscribers...\n";
    $subscribers = $pdo->query("SELECT account_id, sales_invoice_number, joined_date FROM subscribers WHERE plan_id IS NULL")->fetchAll();

    foreach ($subscribers as $sub) {
        $si = $sub['sales_invoice_number'];
        $accountId = $sub['account_id'];
        $joinedDate = $sub['joined_date'];
        $dayOfMonth = (int)date('j', strtotime($joinedDate));

        // Find Plan ID from POS
        $stmtPlan = $pdo->prepare("
            SELECT sp.id, sp.has_insurance
            FROM pos_transactions pt
            JOIN pos_transaction_items pti ON pt.id = pti.transaction_id
            JOIN subscription_plans sp ON (pti.product_id = sp.onboarding_product_id OR pti.product_id = sp.monthly_product_id)
            WHERE pt.receipt_number = ?
            LIMIT 1
        ");
        $stmtPlan->execute([$si]);
        $plan = $stmtPlan->fetch();

        if ($plan) {
            $planId = $plan['id'];
            $hasInsurance = $plan['has_insurance'];
            
            // Find Billing Bracket
            $stmtBrackets = $pdo->prepare("SELECT * FROM plan_billing_brackets WHERE plan_id = ?");
            $stmtBrackets->execute([$planId]);
            $brackets = $stmtBrackets->fetchAll();

            $billingDay = null;
            foreach ($brackets as $b) {
                if ($dayOfMonth >= $b['approval_from_day'] && $dayOfMonth <= $b['approval_to_day']) {
                    $billingDay = $b['bill_on_day'];
                    break;
                }
            }

            // Update subscriber
            $insStatus = $hasInsurance ? 'Active' : 'N/A'; // For existing, assume Active if they had insurance
            $pdo->prepare("UPDATE subscribers SET plan_id = ?, billing_day = ?, insurance_status = ? WHERE account_id = ?")
                ->execute([$planId, $billingDay, $insStatus, $accountId]);
            
            echo "Updated Subscriber $accountId: Plan $planId, Billing Day " . ($billingDay ?? 'N/A') . "\n";
        } else {
            echo "Could not find plan for Subscriber $accountId (SI: $si)\n";
        }
    }

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
