<?php
require_once 'db.php';
$pdo->exec("UPDATE merchant_food_orders SET points_received = total_amount * 0.008, srp_total = total_amount, system_fee = total_amount * 0.007 WHERE points_received = 0");
echo "Updated orders successfully";
?>
