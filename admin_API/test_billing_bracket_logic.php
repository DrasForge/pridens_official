<?php
// admin_API/test_billing_bracket_logic.php
require_once 'db.php';

function test_bracket_logic($pdo, $dayOfMonth) {
    // 1. Create a dummy plan
    $pdo->exec("INSERT INTO pos_products (sku, name, price, category) VALUES ('TEST-ONB-01', 'Test Onboarding', 100.00, 'Test') ON DUPLICATE KEY UPDATE id=id");
    $onboardingId = $pdo->query("SELECT id FROM pos_products WHERE sku = 'TEST-ONB-01'")->fetchColumn();
    
    $pdo->exec("INSERT INTO pos_products (sku, name, price, category) VALUES ('TEST-MON-01', 'Test Monthly', 50.00, 'Test') ON DUPLICATE KEY UPDATE id=id");
    $monthlyId = $pdo->query("SELECT id FROM pos_products WHERE sku = 'TEST-MON-01'")->fetchColumn();

    $pdo->prepare("INSERT INTO subscription_plans (plan_name, onboarding_product_id, monthly_product_id, insurance_coverage, payment_terms, subscription_term_months, insurance_term_months) 
                   VALUES ('Test Bracket Plan', ?, ?, 'Individual', 'Every Month', 12, 12)")
        ->execute([$onboardingId, $monthlyId]);
    $planId = $pdo->lastInsertId();

    // 2. Add Brackets: 1-15 -> 15th, 16-31 -> 30th
    $pdo->prepare("INSERT INTO plan_billing_brackets (plan_id, approval_from_day, approval_to_day, bill_on_day) VALUES (?, 1, 15, 15)")->execute([$planId]);
    $pdo->prepare("INSERT INTO plan_billing_brackets (plan_id, approval_from_day, approval_to_day, bill_on_day) VALUES (?, 16, 31, 30)")->execute([$planId]);

    // 3. Logic to test
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

    echo "Day of Month: $dayOfMonth -> Assigned Billing Day: " . ($billingDay ?? 'None') . "\n";
    
    // Cleanup
    $pdo->prepare("DELETE FROM subscription_plans WHERE id = ?")->execute([$planId]);
}

echo "Testing Bracket Logic:\n";
test_bracket_logic($pdo, 10); // Expected: 15
test_bracket_logic($pdo, 20); // Expected: 30
?>
