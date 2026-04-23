<?php
// admin_API/recreate_id_1_dummies_v6.php
require_once 'db.php';

try {
    $mId = 1;
    $sub = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $accId = $sub ? $sub['account_id'] : 'ACC-TEST-001';

    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    $srvQuery = $pdo->prepare("SELECT id, price FROM merchant_services WHERE merchant_id = ? LIMIT 2");
    $srvQuery->execute([$mId]);
    $srvs = $srvQuery->fetchAll(PDO::FETCH_ASSOC);

    // Logic: 
    // 1. Base - MerchantDisc% = PreBuffer
    // 2. PreBuffer - 1.5%Buffer = FinalTotal
    // 3. Points = 0.8% of PreBuffer
    function calculateAdvancedPricing($srp, $merchantPercent) {
        $merchantDiscAmount = $srp * ($merchantPercent / 100);
        $preBufferAmount = $srp - $merchantDiscAmount;
        
        $bufferPercent = 1.5;
        $bufferAmount = $preBufferAmount * ($bufferPercent / 100);
        
        $finalTotal = $preBufferAmount - $bufferAmount;
        
        $pointsReceived = $preBufferAmount * 0.008;
        
        return [
            'total_amount' => $srp,
            'exclusive_discount' => $merchantDiscAmount,
            'payable_amount' => $finalTotal, 
            'points_earned' => $pointsReceived
        ];
    }

    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, points_earned, payable_amount, payment_method, service_method, schedule_date, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Order 1 (2800 SRP, 13% Merchant Disc)
    $p1 = calculateAdvancedPricing(2800, 13);
    $pdo->prepare($sql)->execute(['SRV-BOOK1-ID1', $mId, $srvs[0]['id'], $accId, $p1['total_amount'], $p1['exclusive_discount'], $p1['points_earned'], $p1['payable_amount'], 'Direct (Bank)', 'Home Service', date('Y-m-d', strtotime('+1 day')), '14:00:00', 'Pending']);

    // Order 2 (850 SRP, 13% Merchant Disc)
    $p2 = calculateAdvancedPricing(850, 13);
    $pdo->prepare($sql)->execute(['SRV-BOOK2-ID1', $mId, $srvs[1]['id'], $accId, $p2['total_amount'], $p2['exclusive_discount'], $p2['points_earned'], $p2['payable_amount'], 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed']);

    echo "✓ Pricing updated with precise 13% + 1.5% sequential deduction for ID 1!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
