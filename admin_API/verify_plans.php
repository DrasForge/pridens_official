<?php
require 'c:/Pridens_trading_co/admin_API/db.php';
$stmt = $pdo->query('SELECT id, plan_name, monthly_pvoucher FROM subscription_plans');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $plan) {
    echo "ID: {$plan['id']} | Name: {$plan['plan_name']} | P-Voucher: {$plan['monthly_pvoucher']}\n";
}
