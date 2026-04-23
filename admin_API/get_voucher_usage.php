<?php
// admin_API/get_voucher_usage.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

try {
    $merchant_id = intval($_GET['merchant_id'] ?? 0);
    if (!$merchant_id) throw new Exception("Merchant ID required");

    $sql = "SELECT 
                u.used_at,
                s.first_name,
                s.last_name,
                v.name as voucher_name,
                v.voucher_code
            FROM merchant_voucher_usage u
            JOIN subscribers s ON u.subscriber_id = s.account_id
            JOIN merchant_vouchers v ON u.voucher_id = v.id
            WHERE v.merchant_id = ?
            ORDER BY u.used_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$merchant_id]);
    $usage = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$usage]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
