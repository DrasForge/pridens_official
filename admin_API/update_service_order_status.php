<?php
// admin_API/update_service_order_status.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = intval($data['order_id'] ?? 0);
$status = $data['status'] ?? '';
$payStatus = $data['payment_status'] ?? '';

if (!$orderId || (!$status && !$payStatus)) {
    echo json_encode(['status'=>'error', 'message'=>'Missing input']);
    exit;
}

try {
    $updates = [];
    $params = [];

    if ($status) {
        $updates[] = "status = ?";
        $params[] = $status;
    }
    if ($payStatus) {
        $updates[] = "payment_status = ?";
        $params[] = $payStatus;
    }

    $params[] = $orderId;
    $sql = "UPDATE merchant_service_orders SET " . implode(", ", $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Monitoring status updated']);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
