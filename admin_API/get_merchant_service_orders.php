<?php
// admin_API/get_merchant_service_orders.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? $_GET['id'] ?? 0);
if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $sql = "SELECT o.*, s.service_name, s.exclusive_percentage, sub.first_name, sub.last_name, sub.contact_number as phone, o.payment_status as pay_status
            FROM merchant_service_orders o
            JOIN merchant_services s ON o.service_id = s.id
            JOIN subscribers sub ON o.subscriber_id = sub.account_id
            WHERE o.merchant_id = ?
            ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$merchantId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
