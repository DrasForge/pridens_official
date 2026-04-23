<?php
// admin_API/update_merchant_order_status.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = intval($data['order_id'] ?? 0);
$newStatus = $data['status'] ?? ''; // 'To Ship', 'Shipping', 'Completed', etc.

if (!$orderId || !$newStatus) {
    echo json_encode(['status'=>'error', 'message'=>'Missing required parameters']);
    exit;
}

try {
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE merchant_orders SET status = ?, updated_at = NOW()";
    $params = [$newStatus, $orderId];

    if ($newStatus === 'Shipping') {
        $sql .= ", shipped_at = ?";
        $params = [$newStatus, $now, $orderId];
    } elseif ($newStatus === 'Completed') {
        $sql .= ", completed_at = ?";
        $params = [$newStatus, $now, $orderId];
    }

    $sql .= " WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['status'=>'success', 'message'=>'Order status updated to ' . $newStatus]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
