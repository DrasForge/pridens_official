<?php
// admin_API/recreate_id_1_dummies.php
require_once 'db.php';

try {
    $mId = 1;
    
    // Find a real subscriber account_id
    $sub = $pdo->query("SELECT account_id FROM subscribers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$sub) {
        $accId = 'ACC-TEST-001';
        $pdo->prepare("INSERT INTO subscribers (account_id, first_name, last_name, contact_number, email) VALUES (?, 'Test', 'User', '09123456789', 'test@user.com')")->execute([$accId]);
    } else {
        $accId = $sub['account_id'];
    }

    echo "Using Subscriber Account ID: $accId\n";

    // Clear existing for this merchant
    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    // Ensure services exist
    $srvQuery = $pdo->prepare("SELECT id FROM merchant_services WHERE merchant_id = ? LIMIT 2");
    $srvQuery->execute([$mId]);
    $srvs = $srvQuery->fetchAll(PDO::FETCH_ASSOC);

    if (count($srvs) < 2) {
        // Create them if missing
        $pdo->prepare("INSERT INTO merchant_services (merchant_id, service_name, price, status) VALUES (?, 'Service Alpha', 1000, 'active')")->execute([$mId]);
        $pdo->prepare("INSERT INTO merchant_services (merchant_id, service_name, price, status) VALUES (?, 'Service Beta', 2000, 'active')")->execute([$mId]);
        $srvQuery->execute([$mId]);
        $srvs = $srvQuery->fetchAll(PDO::FETCH_ASSOC);
    }

    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, payable_amount, payment_method, service_method, schedule_date, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $pdo->prepare($sql)->execute(['SRV-BOOK1-ID1', $mId, $srvs[0]['id'], $accId, 1000, 1000, 'Direct (Bank)', 'Home Service', date('Y-m-d', strtotime('+1 day')), '14:00:00', 'Pending']);
    $pdo->prepare($sql)->execute(['SRV-BOOK2-ID1', $mId, $srvs[1]['id'], $accId, 2000, 2000, 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed']);

    echo "✓ Fixed ecosystem for Merchant ID 1 successfully!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
