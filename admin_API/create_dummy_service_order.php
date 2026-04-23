<?php
// admin_API/create_dummy_service_order.php
require_once 'db.php';

try {
    $merchantId = 49; // Testing Merchant
    $subscriberId = 77; // Dummy Subscriber (Maria Clara)
    $serviceIdQuery = $pdo->prepare("SELECT id, price, is_exclusive, exclusive_percentage FROM merchant_services WHERE merchant_id = ? LIMIT 1");
    $serviceIdQuery->execute([$merchantId]);
    $service = $serviceIdQuery->fetch(PDO::FETCH_ASSOC);

    if (!$service) die("No service found for merchant 49\n");

    $price = $service['price'];
    $discount = 0;
    if ($service['is_exclusive']) {
        $discount = ($price * $service['exclusive_percentage'] / 100);
    }
    $payable = $price - $discount;

    $orderSn = 'SRV-' . strtoupper(bin2hex(random_bytes(4)));
    
    $sql = "INSERT INTO merchant_service_orders (order_sn, merchant_id, service_id, subscriber_id, total_amount, exclusive_discount, payable_amount, payment_method, service_method, schedule_date, schedule_time, status, payment_status, customer_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $orderSn,
        $merchantId,
        $service['id'],
        $subscriberId,
        $price,
        $discount,
        $payable,
        'Pridens API (E-wallet)',
        'Home Service',
        date('Y-m-d', strtotime('+1 day')),
        '14:00:00',
        'Pending',
        'Unpaid',
        'Please arrive 15 minutes early for preparation.'
    ]);

    echo "✓ Dummy Service Order $orderSn created for monitoring test!\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
