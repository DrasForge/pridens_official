<?php
// admin_API/save_merchant_exclusive_offers.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$merchantId = intval($data['merchant_id'] ?? 0);
$offers = $data['offers'] ?? []; // Array of {plan_id, discount_percent}

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Prepare upsert
    $stmt = $pdo->prepare("INSERT INTO merchant_exclusive_offers (merchant_id, plan_id, discount_percent) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE discount_percent = VALUES(discount_percent)");

    foreach ($offers as $offer) {
        $planId = intval($offer['plan_id'] ?? $offer['id'] ?? 0);
        $percent = floatval($offer['discount_percent']);
        if ($planId > 0) {
            $stmt->execute([$merchantId, $planId, $percent]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Exclusive offers updated successfully']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
