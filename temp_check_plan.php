<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query("SELECT * FROM subscription_plans WHERE plan_name='Diamond Elite'");
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($plan);
$stmtItems = $pdo->query("SELECT * FROM plan_insurance_benefits WHERE plan_id=" . $plan['id']);
print_r($stmtItems->fetchAll(PDO::FETCH_ASSOC));
?>
