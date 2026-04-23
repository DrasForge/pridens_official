<?php
// admin_API/get_merchant_addon_deals.php
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

    $sql = "SELECT d.*, 
            (SELECT COUNT(*) FROM merchant_addon_items WHERE addon_deal_id = d.id) as item_count,
            CASE 
                WHEN ? < start_time THEN 'Upcoming'
                WHEN ? BETWEEN start_time AND end_time THEN 'Ongoing'
                ELSE 'Expired'
            END as calculated_status
            FROM merchant_addon_deals d
            WHERE d.merchant_id = ?
            ORDER BY d.start_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$now, $now, $merchant_id]);
    $deals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$deals]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
