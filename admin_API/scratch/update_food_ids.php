<?php
require_once __DIR__ . '/../db.php';
$count = $pdo->exec("UPDATE merchant_food_orders SET subscriber_id = 'PR-0000001' WHERE subscriber_id = '0' OR subscriber_id = ''");
echo "Updated $count rows in merchant_food_orders\n";
