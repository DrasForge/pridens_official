<?php
// admin_API/verify_merchant_order_payment.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = intval($data['order_id'] ?? 0);
$action = $data['action'] ?? ''; // 'approve' or 'reject'

if (!$orderId || !$action) {
    echo json_encode(['status'=>'error', 'message'=>'Missing parameters']);
    exit;
}

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE merchant_orders SET status = 'To Ship', payment_verified_at = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        echo json_encode(['status'=>'success', 'message'=>'Payment verified. Order moved to To Ship.']);
    } else {
        // Revert to Unpaid if proof is invalid
        $stmt = $pdo->prepare("UPDATE merchant_orders SET status = 'Unpaid', payment_proof_path = NULL WHERE id = ?");
        $stmt->execute([$orderId]);
        echo json_encode(['status'=>'success', 'message'=>'Payment rejected. Order reverted to Unpaid.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
