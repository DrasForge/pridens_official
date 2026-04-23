<?php
// admin_API/get_merchant_pvoucher_ledger.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$mId = intval($_GET['merchant_id'] ?? 0);
if (!$mId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID required']);
    exit;
}

try {
    $sql = "SELECT l.*, v.voucher_code, v.amount as voucher_base_amount 
            FROM merchant_pvoucher_ledgers l
            JOIN p_vouchers v ON l.voucher_id = v.id
            WHERE l.merchant_id = ?
            ORDER BY l.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$data]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
