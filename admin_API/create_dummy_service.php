<?php
// admin_API/create_dummy_service.php
require_once 'db.php';

try {
    $merchantId = 49; // Default testing merchant

    // 1. Delete if exists
    $pdo->prepare("DELETE FROM merchant_services WHERE service_name = 'Elite Holistic Therapy' AND merchant_id = ?")->execute([$merchantId]);

    // 2. Insert dummy service
    $sql = "INSERT INTO merchant_services (merchant_id, service_name, tagline, description, price, duration_minutes, is_exclusive, exclusive_percentage, service_method, payment_methods, status) 
            VALUES (?, 'Elite Holistic Therapy', 'Premium full-body rejuvenation and deep tissue care', 'A complete 90-minute therapeutic session combining Swedish massage, aromatherapy, and hot stone therapy to align your body and spirit.', 2800.00, 90, 1, 15.00, 'In-Store', 'Pridens API (E-wallet), Direct (Bank), Pay on Service', 'active')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$merchantId]);
    $serviceId = $pdo->lastInsertId();

    // 3. Add Inclusions
    $inclusions = [
        "90-min Full Body Treatment",
        "Essential Oil Aromatherapy",
        "Hot Stone Therapy session",
        "Post-service Refreshment Tea",
        "Gratuity Included"
    ];
    $incStmt = $pdo->prepare("INSERT INTO merchant_service_inclusions (service_id, content) VALUES (?, ?)");
    foreach ($inclusions as $inc) {
        $incStmt->execute([$serviceId, $inc]);
    }

    echo "Dummy Service 'Elite Holistic Therapy' created successfully for Merchant ID 49!\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
