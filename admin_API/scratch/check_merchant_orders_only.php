<?php
require_once __DIR__ . '/../db.php';
echo "--- merchant_orders ---\n";
$stmt = $pdo->query("DESCRIBE merchant_orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
