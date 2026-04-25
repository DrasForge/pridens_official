<?php
// admin_API/get_merchant_payments_monitoring.php
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
    // We aggregate all orders from the 3 categories
    $sql = "
        SELECT 
            type,
            order_sn,
            total_amount,
            points_earned,
            pridens_profit,
            is_gateway,
            created_at,
            provider_name
        FROM (
            SELECT 
                'Product' as type, o.order_sn, o.total_amount, o.points_earned, o.pridens_profit_amount as pridens_profit, 
                IFNULL(pc.is_pridens_gateway, 0) as is_gateway, o.created_at, pc.provider_name
            FROM merchant_orders o 
            LEFT JOIN merchant_payment_channels pc ON o.payment_channel_id = pc.id
            WHERE o.merchant_id = ? AND o.status = 'Completed'
            
            UNION ALL
            
            SELECT 
                'Food' as type, o.order_sn, o.total_amount, o.points_received as points_earned, o.system_fee as pridens_profit, 
                IFNULL(pc.is_pridens_gateway, 0) as is_gateway, o.created_at, pc.provider_name
            FROM merchant_food_orders o 
            LEFT JOIN merchant_payment_channels pc ON o.payment_channel_id = pc.id
            WHERE o.merchant_id = ? AND o.status = 'Delivered'
            
            UNION ALL
            
            SELECT 
                'Service' as type, o.order_sn, o.total_amount, o.points_earned, o.pridens_profit, 
                IFNULL(pc.is_pridens_gateway, 0) as is_gateway, o.created_at, pc.provider_name
            FROM merchant_service_orders o 
            LEFT JOIN merchant_payment_channels pc ON o.payment_channel_id = pc.id
            WHERE o.merchant_id = ? AND o.status = 'Completed'
        ) as combined
        ORDER BY created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$merchantId, $merchantId, $merchantId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalOwedToPridens = 0; // Merchant owes Pridens (Fees from Direct payments)
    $totalOwedToMerchant = 0; // Pridens owes Merchant (Net from Gateway payments)
    $history = [];

    foreach ($orders as $o) {
        $fee = floatval($o['points_earned']) + floatval($o['pridens_profit']);
        $total = floatval($o['total_amount']);
        $isGateway = intval($o['is_gateway']);
        
        $payableToMerchant = 0;
        $payableToPridens = 0;

        if ($isGateway) {
            // Money is with Pridens. Pridens owes Merchant (Total - Fee)
            $payableToMerchant = $total - $fee;
            $totalOwedToMerchant += $payableToMerchant;
        } else {
            // Money is with Merchant. Merchant owes Pridens (Fee)
            $payableToPridens = $fee;
            $totalOwedToPridens += $payableToPridens;
        }

        $history[] = [
            'type' => $o['type'],
            'order_sn' => $o['order_sn'],
            'total' => $total,
            'fee' => $fee,
            'is_gateway' => $isGateway,
            'provider' => $o['provider_name'] ?? 'Direct/COD',
            'payable_to_merchant' => $payableToMerchant,
            'payable_to_pridens' => $payableToPridens,
            'created_at' => $o['created_at']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totals' => [
                'owed_to_pridens' => $totalOwedToPridens,
                'owed_to_merchant' => $totalOwedToMerchant,
                'net_balance' => $totalOwedToMerchant - $totalOwedToPridens
            ],
            'history' => array_slice($history, 0, 50)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
