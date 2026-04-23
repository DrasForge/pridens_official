<?php
require_once 'db.php';
$merchantId = 1;
$categoryFilter = 'Products';
$status = 'To Ship';

$apiMode = ($categoryFilter === 'Foods') ? 'Food' : 'Product';

if ($apiMode === 'Food') {
    $query = "SELECT o.* FROM merchant_food_orders o WHERE o.merchant_id = ?";
    $params = [$merchantId];
} else {
    $query = "SELECT DISTINCT o.* FROM merchant_orders o 
              LEFT JOIN merchant_order_items oi ON o.id = oi.order_id
              LEFT JOIN merchant_products p ON oi.product_id = p.id
              WHERE o.merchant_id = ?";
    $params = [$merchantId];
    if ($categoryFilter) { 
        $query .= " AND p.category = ?"; 
        $params[] = $categoryFilter;
    }
}

if ($status && $status !== 'All') {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

echo "Query: $query\n";
echo "Params: " . json_encode($params) . "\n";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "RESULT COUNT: " . count($rows) . "\n";
print_r($rows);
?>
