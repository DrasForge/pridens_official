<?php
require_once __DIR__ . '/../db.php';
$stmt = $pdo->query("DESCRIBE merchant_orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("DESCRIBE merchant_food_orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("DESCRIBE merchant_service_orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
