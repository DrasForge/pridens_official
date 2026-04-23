<?php
// admin_API/update_food_order_status.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = intval($data['order_id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!$orderId || !$status) throw new Exception("Data missing");

    $stmt = $pdo->prepare("UPDATE merchant_food_orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    echo json_encode(['status'=>'success', 'message'=>"Order updated to $status"]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
