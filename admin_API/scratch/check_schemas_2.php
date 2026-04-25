<?php
require_once __DIR__ . '/../db.php';
echo "--- merchant_products ---\n";
$stmt = $pdo->query("DESCRIBE merchant_products");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "--- merchant_food_dishes ---\n";
$stmt = $pdo->query("DESCRIBE merchant_food_dishes");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
