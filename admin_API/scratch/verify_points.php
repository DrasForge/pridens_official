<?php
require_once __DIR__ . '/../db.php';
$merchant_id = 1; // Assuming 1 is a valid merchant ID

echo "--- Totals for Merchant 1 ---\n";
$sql = "
    SELECT 
        SUM(p_earned) as total_points,
        SUM(CASE WHEN type = 'Food' THEN p_earned ELSE 0 END) as food_points,
        SUM(CASE WHEN type = 'Product' THEN p_earned ELSE 0 END) as product_points,
        SUM(CASE WHEN type = 'Service' THEN p_earned ELSE 0 END) as service_points
    FROM (
        SELECT 'Product' as type, points_earned as p_earned FROM merchant_orders WHERE merchant_id = ? AND points_earned > 0
        UNION ALL
        SELECT 'Food' as type, points_received as p_earned FROM merchant_food_orders WHERE merchant_id = ? AND points_received > 0
        UNION ALL
        SELECT 'Service' as type, points_earned as p_earned FROM merchant_service_orders WHERE merchant_id = ? AND points_earned > 0
    ) as combined_points
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$merchant_id, $merchant_id, $merchant_id]);
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n--- Recent History for Merchant 1 ---\n";
$sqlH = "
    SELECT type, order_sn, points, created_at
    FROM (
        SELECT 'Product' as type, order_sn, points_earned as points, created_at FROM merchant_orders WHERE merchant_id = ? AND points_earned > 0
        UNION ALL
        SELECT 'Food' as type, order_sn, points_received as points, created_at FROM merchant_food_orders WHERE merchant_id = ? AND points_received > 0
        UNION ALL
        SELECT 'Service' as type, order_sn, points_earned as points, created_at FROM merchant_service_orders WHERE merchant_id = ? AND points_earned > 0
    ) as h ORDER BY created_at DESC LIMIT 5
";
$stmtH = $pdo->prepare($sqlH);
$stmtH->execute([$merchant_id, $merchant_id, $merchant_id]);
print_r($stmtH->fetchAll(PDO::FETCH_ASSOC));
