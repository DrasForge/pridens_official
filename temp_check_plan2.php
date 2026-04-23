<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query("SELECT id, plan_name, contestability_period_days FROM subscription_plans WHERE plan_name='Diamond Elite'");
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($plan) . "\n";
$stmtItems = $pdo->query("SELECT benefit_name, amount, requires_contestability FROM plan_insurance_benefits WHERE plan_id=" . $plan['id']);
echo json_encode($stmtItems->fetchAll(PDO::FETCH_ASSOC));
?>
