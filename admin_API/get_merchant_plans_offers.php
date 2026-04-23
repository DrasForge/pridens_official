<?php
// admin_API/get_merchant_plans_offers.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    // 1. Get all active plans
    $stmt = $pdo->prepare("SELECT id, plan_name FROM subscription_plans WHERE status = 'Active' ORDER BY id ASC");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get current merchant exclusive offers
    $stmt = $pdo->prepare("SELECT plan_id, discount_percent FROM merchant_exclusive_offers WHERE merchant_id = ?");
    $stmt->execute([$merchantId]);
    $offers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. Merge
    foreach ($plans as &$plan) {
        $plan['discount_percent'] = isset($offers[$plan['id']]) ? floatval($offers[$plan['id']]) : 0.00;
    }

    echo json_encode(['status' => 'success', 'data' => $plans]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
