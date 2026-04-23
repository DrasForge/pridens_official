<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT id, status FROM merchant_orders LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "RETAIL PRODUCT ORDERS:\n";
print_r($rows);

$stmt2 = $pdo->query("SELECT id, status FROM merchant_food_orders LIMIT 10");
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "\nFOOD ORDERS:\n";
print_r($rows2);
?>
