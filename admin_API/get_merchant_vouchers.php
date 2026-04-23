<?php
// admin_API/get_merchant_vouchers.php
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

    $now = date('Y-m-d H:i:s');

    $sql = "SELECT v.*, 
            CASE 
                WHEN ? < start_time THEN 'Upcoming'
                WHEN ? BETWEEN start_time AND end_time THEN 'Ongoing'
                ELSE 'Expired'
            END as calculated_status,
            (SELECT COUNT(*) FROM merchant_voucher_items WHERE voucher_id = v.id) as item_count
            FROM merchant_vouchers v
            WHERE v.merchant_id = ?
            ORDER BY v.start_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$now, $now, $merchant_id]);
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$vouchers]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
