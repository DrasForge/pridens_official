<?php
// admin_API/get_merchant_promotions.php
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

    // Fetch promos with item counts
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM merchant_promotion_items WHERE promo_id = p.id) as item_count,
            CASE 
                WHEN ? < start_time THEN 'Upcoming'
                WHEN ? BETWEEN start_time AND end_time THEN 'Ongoing'
                ELSE 'Expired'
            END as calculated_status
            FROM merchant_promotions p
            WHERE p.merchant_id = ?
            ORDER BY p.start_time DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$now, $now, $merchant_id]);
    $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$promos]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
