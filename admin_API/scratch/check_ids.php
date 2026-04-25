<?php
require_once __DIR__ . '/../db.php';
echo "--- merchant_food_orders ---\n";
$stmt = $pdo->query("SELECT subscriber_id FROM merchant_food_orders WHERE subscriber_id != 0 LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- subscribers ---\n";
$stmt = $pdo->query("SELECT account_id FROM subscribers LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
