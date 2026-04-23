<?php
// admin_API/get_merchant_inventory.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $sql = "
    (SELECT 
        p.id as item_id,
        p.id as product_id,
        NULL as model_id,
        p.name as display_name,
        p.pridens_sku as sku,
        p.seller_sku as seller_sku,
        p.stock_quantity as stock,
        'product' as type,
        COALESCE(p.image_path, (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1)) as display_image,
        p.brand,
        p.category
     FROM merchant_products p
     WHERE p.merchant_id = ? AND p.has_variations = 0)
    UNION ALL
    (SELECT 
        m.id as item_id,
        p.id as product_id,
        m.id as model_id,
        CONCAT(p.name, ' (', m.name, ')') as display_name,
        m.pridens_sku as sku,
        m.seller_sku as seller_sku,
        m.stock as stock,
        'model' as type,
        COALESCE(p.image_path, (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1)) as display_image,
        p.brand,
        p.category
     FROM product_models m
     JOIN merchant_products p ON m.product_id = p.id
     WHERE p.merchant_id = ?)
    ORDER BY display_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$merchantId, $merchantId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $items]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
