<?php
// admin_API/get_merchant_points_monitoring.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    // 1. Get Aggregated Totals
    $sqlTotals = "
        SELECT 
            SUM(p_earned) as total_points,
            SUM(p_pridens) as total_pridens_points,
            SUM(CASE WHEN type = 'Food' THEN p_earned ELSE 0 END) as food_points,
            SUM(CASE WHEN type = 'Product' THEN p_earned ELSE 0 END) as product_points,
            SUM(CASE WHEN type = 'Service' THEN p_earned ELSE 0 END) as service_points
        FROM (
            SELECT 'Product' as type, points_earned as p_earned, pridens_profit_amount as p_pridens FROM merchant_orders WHERE merchant_id = ? AND (points_earned > 0 OR pridens_profit_amount > 0)
            UNION ALL
            SELECT 'Food' as type, points_received as p_earned, system_fee as p_pridens FROM merchant_food_orders WHERE merchant_id = ? AND (points_received > 0 OR system_fee > 0)
            UNION ALL
            SELECT 'Service' as type, points_earned as p_earned, pridens_profit as p_pridens FROM merchant_service_orders WHERE merchant_id = ? AND (points_earned > 0 OR pridens_profit > 0)
        ) as combined_points
    ";
    $stmtTotals = $pdo->prepare($sqlTotals);
    $stmtTotals->execute([$merchantId, $merchantId, $merchantId]);
    $totals = $stmtTotals->fetch(PDO::FETCH_ASSOC);

    // 2. Get 30-Day Trend
    $sqlTrend = "
        SELECT 
            DATE(created_at) as date,
            SUM(p_earned) as daily_points,
            SUM(p_pridens) as daily_pridens_points
        FROM (
            SELECT created_at, points_earned as p_earned, pridens_profit_amount as p_pridens FROM merchant_orders WHERE merchant_id = ? AND (points_earned > 0 OR pridens_profit_amount > 0)
            UNION ALL
            SELECT created_at, points_received as p_earned, system_fee as p_pridens FROM merchant_food_orders WHERE merchant_id = ? AND (points_received > 0 OR system_fee > 0)
            UNION ALL
            SELECT created_at, points_earned as p_earned, pridens_profit as p_pridens FROM merchant_service_orders WHERE merchant_id = ? AND (points_earned > 0 OR pridens_profit > 0)
        ) as combined_trend
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $stmtTrend = $pdo->prepare($sqlTrend);
    $stmtTrend->execute([$merchantId, $merchantId, $merchantId]);
    $trend = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Recent Transactions (Detailed History)
    $sqlHistory = "
        SELECT 
            type,
            order_sn,
            points,
            pridens_points,
            created_at,
            first_name,
            last_name
        FROM (
            SELECT 'Product' as type, o.order_sn, o.points_earned as points, o.pridens_profit_amount as pridens_points, o.created_at, s.first_name, s.last_name 
            FROM merchant_orders o JOIN subscribers s ON o.subscriber_id = s.account_id 
            WHERE o.merchant_id = ? AND (o.points_earned > 0 OR o.pridens_profit_amount > 0)
            
            UNION ALL
            
            SELECT 'Food' as type, o.order_sn, o.points_received as points, o.system_fee as pridens_points, o.created_at, s.first_name, s.last_name 
            FROM merchant_food_orders o JOIN subscribers s ON o.subscriber_id = s.account_id 
            WHERE o.merchant_id = ? AND (o.points_received > 0 OR o.system_fee > 0)
            
            UNION ALL
            
            SELECT 'Service' as type, o.order_sn, o.points_earned as points, o.pridens_profit as pridens_points, o.created_at, s.first_name, s.last_name 
            FROM merchant_service_orders o JOIN subscribers s ON o.subscriber_id = s.account_id 
            WHERE o.merchant_id = ? AND (o.points_earned > 0 OR o.pridens_profit > 0)
        ) as combined_history
        ORDER BY created_at DESC
        LIMIT 50
    ";
    $stmtHistory = $pdo->prepare($sqlHistory);
    $stmtHistory->execute([$merchantId, $merchantId, $merchantId]);
    $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totals' => $totals,
            'trend' => $trend,
            'history' => $history
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
