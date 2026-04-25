<?php
// admin_API/generate_monitoring_dummies.php
require_once 'db.php';

try {
    $mId = 1; // Testing for Merchant 1
    $sId = 'PR-0000001';

    echo "Generating comprehensive monitoring dummy data for Merchant 1...\n";

    // 1. Ensure Payment Channels exist
    // Delete old test channels for M1 to avoid duplicates
    $pdo->prepare("DELETE FROM merchant_payment_channels WHERE merchant_id = ? AND provider_name IN ('GCash Direct', 'Pridens Paymongo')")->execute([$mId]);
    
    // Direct Channel
    $pdo->prepare("INSERT INTO merchant_payment_channels (merchant_id, type, provider_name, account_name, account_number, is_pridens_gateway) VALUES (?, 'E-Wallet', 'GCash Direct', 'Merchant Store', '09123456789', 0)")
        ->execute([$mId]);
    $directChannelId = $pdo->lastInsertId();
    
    // Gateway Channel
    $pdo->prepare("INSERT INTO merchant_payment_channels (merchant_id, type, provider_name, account_name, account_number, is_pridens_gateway) VALUES (?, 'E-Wallet', 'Pridens Paymongo', 'Platform Gateway', 'PAY-GATE-999', 1)")
        ->execute([$mId]);
    $gatewayChannelId = $pdo->lastInsertId();

    echo "✓ Payment channels created (Direct & Gateway)\n";

    // 2. Clear previous test orders for M1 to have a clean view
    $pdo->prepare("DELETE FROM merchant_orders WHERE merchant_id = ?")->execute([$mId]);
    $pdo->prepare("DELETE FROM merchant_food_orders WHERE merchant_id = ?")->execute([$mId]);
    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    // 3. Helper to insert orders
    $now = date('Y-m-d H:i:s');
    
    // --- PRODUCT ORDERS ---
    $productScenarios = [
        ['sn' => 'PRD-DIRECT-001', 'total' => 1000.00, 'channel' => $directChannelId, 'status' => 'Completed'],
        ['sn' => 'PRD-GATEWAY-002', 'total' => 2500.00, 'channel' => $gatewayChannelId, 'status' => 'Completed']
    ];
    foreach ($productScenarios as $ps) {
        $points = $ps['total'] * 0.008;
        $profit = $ps['total'] * 0.007;
        $stmt = $pdo->prepare("INSERT INTO merchant_orders (order_sn, merchant_id, subscriber_id, total_amount, points_earned, pridens_profit_amount, payment_channel_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$ps['sn'], $mId, $sId, $ps['total'], $points, $profit, $ps['channel'], $ps['status'], $now]);
    }

    // --- FOOD ORDERS ---
    $foodScenarios = [
        ['sn' => 'FOOD-DIRECT-001', 'total' => 500.00, 'channel' => $directChannelId, 'status' => 'Delivered'],
        ['sn' => 'FOOD-GATEWAY-002', 'total' => 850.00, 'channel' => $gatewayChannelId, 'status' => 'Delivered']
    ];
    foreach ($foodScenarios as $fs) {
        $points = $fs['total'] * 0.008;
        $profit = $fs['total'] * 0.007;
        $stmt = $pdo->prepare("INSERT INTO merchant_food_orders (order_sn, merchant_id, subscriber_id, total_amount, points_received, system_fee, payment_channel_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$fs['sn'], $mId, $sId, $fs['total'], $points, $profit, $fs['channel'], $fs['status'], $now]);
    }

    // --- SERVICE ORDERS ---
    // Ensure a service exists for M1
    $pdo->prepare("DELETE FROM merchant_services WHERE merchant_id = ?")->execute([$mId]);
    $pdo->prepare("INSERT INTO merchant_services (merchant_id, service_name, price, status) VALUES (?, 'General Consultation', 500.00, 'active')")
        ->execute([$mId]);
    $serviceId = $pdo->lastInsertId();

    $serviceScenarios = [
        ['sn' => 'SRV-DIRECT-001', 'total' => 1500.00, 'channel' => $directChannelId, 'status' => 'Completed'],
        ['sn' => 'SRV-GATEWAY-002', 'total' => 3000.00, 'channel' => $gatewayChannelId, 'status' => 'Completed']
    ];
    foreach ($serviceScenarios as $ss) {
        $points = $ss['total'] * 0.008;
        $profit = $ss['total'] * 0.007;
        $stmt = $pdo->prepare("INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, points_earned, pridens_profit, payment_channel_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$ss['sn'], $mId, $serviceId, $sId, $ss['total'], $points, $profit, $ss['channel'], $ss['status'], $now]);
    }

    echo "✓ Comprehensive test orders generated for all categories!\n";
    echo "Summary for test:\n";
    echo "- Direct Payments: 1.5% Fee will be 'Due to Pridens'\n";
    echo "- Gateway Payments: (Total - 1.5% Fee) will be 'Due from Pridens'\n";
    echo "Check the Marketing Centre UI now.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
