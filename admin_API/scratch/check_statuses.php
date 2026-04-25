<?php
require_once __DIR__ . '/../db.php';
$pdo->exec("UPDATE merchant_orders SET status = 'Completed' WHERE merchant_id = 1 LIMIT 1");
echo "Updated 1 order to Completed\n";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM merchant_orders WHERE merchant_id = 1 GROUP BY status");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
