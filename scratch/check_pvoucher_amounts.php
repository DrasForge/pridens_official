<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query('SELECT id, plan_name, monthly_pvoucher FROM subscription_plans');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
