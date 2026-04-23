<?php
// admin_API/seed_brackets.php
require_once 'db.php';

try {
    $plans = $pdo->query("SELECT id, plan_name FROM subscription_plans")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($plans as $plan) {
        $plan_id = $plan['id'];
        
        // Remove existing to avoid duplicates if any
        $pdo->prepare("DELETE FROM plan_billing_brackets WHERE plan_id = ?")->execute([$plan_id]);
        
        // Seed 2 brackets: 
        // 1-15 -> 15th
        // 16-31 -> 30th
        $stmt = $pdo->prepare("INSERT INTO plan_billing_brackets (plan_id, approval_from_day, approval_to_day, bill_on_day) VALUES (?, ?, ?, ?)");
        $stmt->execute([$plan_id, 1, 15, 15]);
        $stmt->execute([$plan_id, 16, 31, 30]);
        
        echo "Seeded brackets for: " . $plan['plan_name'] . " (ID: $plan_id)\n";
    }

    // Now re-run migration to update subscribers with these new brackets
    echo "Running migration to update subscribers...\n";
    require 'migrate_subscribers_billing.php';

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
