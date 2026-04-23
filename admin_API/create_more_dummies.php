<?php
// admin_API/create_more_dummies.php
require_once 'db.php';

try {
    $mId = 49; 
    $subId = 88; // Another dummy subscriber (Rizal)

    echo "Adding second dummy service and booking for testing...\n";

    // 1. Create a different service type: Grooming/Barber
    $pdo->prepare("DELETE FROM merchant_services WHERE service_name = 'Signature Executive Cut' AND merchant_id = ?")->execute([$mId]);
    
    $sql = "INSERT INTO merchant_services (merchant_id, service_name, tagline, description, price, duration_minutes, is_exclusive, exclusive_percentage, service_method, payment_methods, status) 
            VALUES (?, 'Signature Executive Cut', 'The gold standard in modern grooming', 'A comprehensive grooming experience including precision haircut, hot towel shave, and scalp massage.', 850.00, 45, 1, 10.00, 'In-Store', 'Direct (E-wallet), Pay on Service', 'active')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mId]);
    $serviceId = $pdo->lastInsertId();

    // 2. Add Inclusions
    $incs = ["Precision Haircut", "Hot Towel Treatment", "Deep Tissue Scalp Massage", "Style & Finish"];
    $incStmt = $pdo->prepare("INSERT INTO merchant_service_inclusions (service_id, content) VALUES (?, ?)");
    foreach ($incs as $i) $incStmt->execute([$serviceId, $i]);

    // 3. Create a Booking for this new service
    $orderSn = 'SRV-' . strtoupper(bin2hex(random_bytes(4)));
    $price = 850.00;
    $disc = 85.00; // 10%
    $payable = $price - $disc;

    $sqlBook = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, payable_amount, payment_method, service_method, schedule_date, schedule_time, status, payment_status, customer_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $pdo->prepare($sqlBook)->execute([
        $orderSn, $mId, $serviceId, $subId, $price, $disc, $payable, 'Pay on Service', 'In-Store', date('Y-m-d'), '10:30:00', 'Confirmed', 'Unpaid', 'First time visit, excited!'
    ]);

    echo "✓ Created 'Signature Executive Cut' service and an active booking (Ref: $orderSn) for Merchant 49.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
