<?php
// admin_API/check_plans.php
require_once 'db.php';

echo "--- Subscription Plans ---\n";
$plans = $pdo->query("SELECT id, plan_name, has_insurance FROM subscription_plans")->fetchAll(PDO::FETCH_ASSOC);
print_r($plans);

echo "\n--- Plan Billing Brackets ---\n";
$brackets = $pdo->query("SELECT * FROM plan_billing_brackets")->fetchAll(PDO::FETCH_ASSOC);
print_r($brackets);
?>
