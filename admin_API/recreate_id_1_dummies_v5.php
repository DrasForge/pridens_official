<?php
// admin_API/recreate_id_1_dummies_v5.php
require_once 'db.php';

try {
    $mId = 1;
    $sub = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $accId = $sub ? $sub['account_id'] : 'ACC-TEST-001';

    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    // Use 12% as requested
    $srvQuery = $pdo->prepare("SELECT id, price FROM merchant_services WHERE merchant_id = ? LIMIT 2");
    $srvQuery->execute([$mId]);
    $srvs = $srvQuery->fetchAll(PDO::FETCH_ASSOC);

    function calculateProductStylePricing($srp, $discPercent) {
        $discAmount = $srp * ($discPercent / 100);
        $grandTotal = $srp - $discAmount;
        
        $points = $grandTotal * 0.008;
        // system fee is the remaining 0.7% but we don't necessarily store it, the dashboard just calculates it.
        
        return [
            'total_amount' => $srp,
            'exclusive_discount' => $discAmount,
            'payable_amount' => $grandTotal, // Grand Total in standard orders
            'points_earned' => $points
        ];
    }

    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, points_earned, payable_amount, payment_method, service_method, schedule_date, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Order 1
    $p1 = calculateProductStylePricing(2800, 12);
    $pdo->prepare($sql)->execute(['SRV-BOOK1-ID1', $mId, $srvs[0]['id'], $accId, $p1['total_amount'], $p1['exclusive_discount'], $p1['points_earned'], $p1['payable_amount'], 'Direct (Bank)', 'Home Service', date('Y-m-d', strtotime('+1 day')), '14:00:00', 'Pending']);

    // Order 2
    $p2 = calculateProductStylePricing(850, 12);
    $pdo->prepare($sql)->execute(['SRV-BOOK2-ID1', $mId, $srvs[1]['id'], $accId, $p2['total_amount'], $p2['exclusive_discount'], $p2['points_earned'], $p2['payable_amount'], 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed']);

    echo "✓ Pricing updated to match Product Order style (12% disc, 1.5% buffer logic) for ID 1!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
