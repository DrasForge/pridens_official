<?php
// admin_API/delete_merchant_promotion.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);
$merchant_id = intval($input['merchant_id'] ?? 0);

if (!$id || !$merchant_id) {
    echo json_encode(['status'=>'error','message'=>'ID or Merchant ID missing']);
    exit;
}

try {
    // Verify ownership
    $check = $pdo->prepare("SELECT id FROM merchant_promotions WHERE id = ? AND merchant_id = ?");
    $check->execute([$id, $merchant_id]);
    if (!$check->fetch()) {
        throw new Exception("Promotion not found for this merchant.");
    }

    $stmt = $pdo->prepare("DELETE FROM merchant_promotions WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status'=>'success','message'=>'Promotion deleted successfully']);

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
