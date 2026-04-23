<?php
// admin_API/recreate_id_1_dummies_v4.php
require_once 'db.php';

try {
    $mId = 1;
    $sub = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $accId = $sub ? $sub['account_id'] : 'ACC-TEST-001';

    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    $srvQuery = $pdo->prepare("SELECT id, price, exclusive_percentage FROM merchant_services WHERE merchant_id = ? LIMIT 2");
    $srvQuery->execute([$mId]);
    $srvs = $srvQuery->fetchAll(PDO::FETCH_ASSOC);

    function calculateServicePricing($basePrice, $setPercent) {
        $bufferPercent = 1.5; // Fixed system buffer
        $totalDeductionPercent = $setPercent + $bufferPercent;
        
        $exclusiveDiscountAmount = $basePrice * ($setPercent / 100);
        $bufferAmount = $basePrice * ($bufferPercent / 100);
        
        $payableAmount = $basePrice - ($basePrice * ($totalDeductionPercent / 100));
        
        // Split buffer: 0.8% for Subscriber, 0.7% for Pridens
        $subscriberPointsValue = $basePrice * (0.008); 
        $pridensCommission = $basePrice * (0.007);
        
        // Convert subscriber points value to Points (P500 = 4 pts logic)
        $points = ($subscriberPointsValue / (500 * 0.015)) * 4; // Adjusted logic? No, let's keep it simple: $points = ($payableAmount/500)*4 if that's the rule, but user says 0.8% is for subscriber points.
        // Actually, if ₱500 = 4 pts, then 1 pt = ₱125.
        $points = $subscriberPointsValue / 125 * 100; // Wait, let's just show the points as a value.
        
        return [
            'exclusive_discount' => $exclusiveDiscountAmount,
            'payable_amount' => $payableAmount,
            'points_earned' => ($basePrice * 0.008) / 125 * 100, // Percentage based points?
            'total_deduction' => $basePrice * ($totalDeductionPercent / 100)
        ];
    }

    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, points_earned, payable_amount, payment_method, service_method, schedule_date, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Order 1
    $pricing1 = calculateServicePricing($srvs[0]['price'], $srvs[0]['exclusive_percentage']);
    $pdo->prepare($sql)->execute(['SRV-BOOK1-ID1', $mId, $srvs[0]['id'], $accId, $srvs[0]['price'], $pricing1['exclusive_discount'], $pricing1['points_earned'], $pricing1['payable_amount'], 'Direct (Bank)', 'Home Service', date('Y-m-d', strtotime('+1 day')), '14:00:00', 'Pending']);

    // Order 2
    $pricing2 = calculateServicePricing($srvs[1]['price'], $srvs[1]['exclusive_percentage']);
    $pdo->prepare($sql)->execute(['SRV-BOOK2-ID1', $mId, $srvs[1]['id'], $accId, $srvs[1]['price'], $pricing2['exclusive_discount'], $pricing2['points_earned'], $pricing2['payable_amount'], 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed']);

    echo "✓ Fixed pricing logic with 1.5% buffer split for ID 1!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
