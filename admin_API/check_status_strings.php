<?php
require_once 'db.php';
$mId = 1;
echo "--- PRODUCTS ---\n";
$stmt = $pdo->prepare("SELECT id, name, status FROM merchant_products WHERE merchant_id = ?");
$stmt->execute([$mId]);
print_r($stmt->fetchAll());

echo "\n--- SERVICES ---\n";
$stmt = $pdo->prepare("SELECT id, service_name, status FROM merchant_services WHERE merchant_id = ?");
$stmt->execute([$mId]);
print_r($stmt->fetchAll());
?>
