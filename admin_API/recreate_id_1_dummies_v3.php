<?php
// admin_API/recreate_id_1_dummies_v3.php
require_once 'db.php';

try {
    $mId = 1;
    $sub = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $accId = $sub ? $sub['account_id'] : 'ACC-TEST-001';

    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    $srvQuery = $pdo->prepare("SELECT id, price, exclusive_percentage FROM merchant_services WHERE merchant_id = ? LIMIT 2");
    $srvQuery->execute([$mId]);
    $srvs = $srvQuery->fetchAll(PDO::FETCH_ASSOC);

    function calcPoints($amount) {
        return ($amount / 500) * 4;
    }

    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, points_earned, payable_amount, payment_method, service_method, schedule_date, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Order 1
    $p1 = $srvs[0]['price'];
    $d1 = $p1 * ($srvs[0]['exclusive_percentage'] / 100);
    $pay1 = $p1 - $d1;
    $pts1 = calcPoints($pay1);
    $pdo->prepare($sql)->execute(['SRV-BOOK1-ID1', $mId, $srvs[0]['id'], $accId, $p1, $d1, $pts1, $pay1, 'Direct (Bank)', 'Home Service', date('Y-m-d', strtotime('+1 day')), '14:00:00', 'Pending']);

    // Order 2
    $p2 = $srvs[1]['price'];
    $d2 = $p2 * ($srvs[1]['exclusive_percentage'] / 100);
    $pay2 = $p2 - $d2;
    $pts2 = calcPoints($pay2);
    $pdo->prepare($sql)->execute(['SRV-BOOK2-ID1', $mId, $srvs[1]['id'], $accId, $p2, $d2, $pts2, $pay2, 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed']);

    echo "✓ Fixed ecosystem with points and breakdowns for ID 1!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
