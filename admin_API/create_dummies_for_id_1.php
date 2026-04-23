<?php
// admin_API/create_dummies_for_id_1.php
require_once 'db.php';

try {
    $mId = 1; 
    $subId = 77; 

    echo "Creating dummy ecosystem for Merchant ID 1...\n";

    // 1. Holistic Service
    $pdo->prepare("DELETE FROM merchant_services WHERE service_name = 'Elite Holistic Therapy' AND merchant_id = ?")->execute([$mId]);
    $sql1 = "INSERT INTO merchant_services (merchant_id, service_name, tagline, description, price, duration_minutes, is_exclusive, exclusive_percentage, service_method, payment_methods, status) 
            VALUES (?, 'Elite Holistic Therapy', 'Premium full-body rejuvenation', 'A complete 90-minute therapeutic session.', 2800.00, 90, 1, 15.00, 'Home Service', 'Pridens API (E-wallet), Direct (Bank)', 'active')";
    $pdo->prepare($sql1)->execute([$mId]);
    $s1 = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO merchant_service_inclusions (service_id, content) VALUES (?, '90-min Treatment'), (?, 'Aromatherapy')")->execute([$s1, $s1]);

    // 2. Executive Cut
    $pdo->prepare("DELETE FROM merchant_services WHERE service_name = 'Signature Executive Cut' AND merchant_id = ?")->execute([$mId]);
    $sql2 = "INSERT INTO merchant_services (merchant_id, service_name, tagline, description, price, duration_minutes, is_exclusive, exclusive_percentage, service_method, payment_methods, status) 
            VALUES (?, 'Signature Executive Cut', 'Modern grooming experience', 'Precision haircut and hot towel shave.', 850.00, 45, 1, 10.00, 'In-Store', 'Pay on Service', 'active')";
    $pdo->prepare($sql2)->execute([$mId]);
    $s2 = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO merchant_service_inclusions (service_id, content) VALUES (?, 'Haircut'), (?, 'Hot Towel')")->execute([$s2, $s2]);

    // 3. Bookings
    $osn1 = 'SRV-BOOK1-ID1';
    $osn2 = 'SRV-BOOK2-ID1';
    
    $pdo->prepare("DELETE FROM merchant_service_orders WHERE merchant_id = ?")->execute([$mId]);

    $sqlBook = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, payable_amount, payment_method, service_method, schedule_date, schedule_time, status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $pdo->prepare($sqlBook)->execute([$osn1, $mId, $s1, $subId, 2800, 420, 2380, 'Pridens API (E-wallet)', 'Home Service', date('Y-m-d', strtotime('+1 day')), '14:00:00', 'Pending', 'Unpaid']);
    $pdo->prepare($sqlBook)->execute([$osn2, $mId, $s2, $subId, 850, 85, 765, 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed', 'Unpaid']);

    echo "✓ Ecosystem for Merchant ID 1 is now fully populated!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
