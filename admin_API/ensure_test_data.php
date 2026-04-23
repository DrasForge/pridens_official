<?php
// admin_API/ensure_test_data.php
require_once 'db.php';

try {
    // 1. Ensure a test subscriber exists
    $sub = $pdo->query("SELECT id FROM subscribers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$sub) {
        $pdo->exec("INSERT INTO subscribers (first_name, last_name, phone, email, password) VALUES ('Test', 'User', '09123456789', 'test@user.com', 'pass')");
        $subId = $pdo->lastInsertId();
    } else {
        $subId = $sub['id'];
    }

    $mId = 1;

    // 2. Ensure Services exist
    $s1 = $pdo->prepare("SELECT id FROM merchant_services WHERE merchant_id = ? LIMIT 1");
    $s1->execute([$mId]);
    $srv = $s1->fetch(PDO::FETCH_ASSOC);

    if (!$srv) {
        $pdo->prepare("INSERT INTO merchant_services (merchant_id, service_name, tagline, description, price, duration_minutes, status) VALUES (?, 'Professional Testing', 'Testing Tag', 'Testing Description', 1000, 30, 'active')")->execute([$mId]);
        $srvId = $pdo->lastInsertId();
    } else {
        $srvId = $srv['id'];
    }

    // 3. Clear and Re-insert Orders to be 100% sure
    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, payable_amount, payment_method, service_method, schedule_date, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $pdo->prepare($sql)->execute(['SRV-TEST-001', $mId, $srvId, $subId, 1000, 1000, 'Cash', 'In-Store', date('Y-m-d'), '14:00:00', 'Pending']);

    echo "DEBUG: Created Order SRV-TEST-001 for Merchant $mId and Subscriber $subId\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
