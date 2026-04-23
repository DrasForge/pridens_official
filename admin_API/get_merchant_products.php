<?php
// admin_API/get_merchant_products.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
$pridensCat = $_GET['category'] ?? ''; // platform category
$shopCatId  = intval($_GET['shop_category_id'] ?? 0); // merchant custom category

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $now = date('Y-m-d H:i:s');
    
    $query = "SELECT p.*, c.name as shop_category_name,
              (SELECT pi.promo_price 
               FROM merchant_promotion_items pi 
               JOIN merchant_promotions pr ON pi.promo_id = pr.id 
               WHERE pi.product_id = p.id AND pi.model_id IS NULL
               AND pr.status = 'Enabled' 
               AND ? BETWEEN pr.start_time AND pr.end_time 
               LIMIT 1) as active_promo_price,
              (SELECT pi.discount_percent 
               FROM merchant_promotion_items pi 
               JOIN merchant_promotions pr ON pi.promo_id = pr.id 
               WHERE pi.product_id = p.id AND pi.model_id IS NULL
               AND pr.status = 'Enabled' 
               AND ? BETWEEN pr.start_time AND pr.end_time 
               LIMIT 1) as active_discount_percent,
              (SELECT SUM(stock) FROM product_models WHERE product_id = p.id) as var_total_stock,
              (SELECT MIN(price) FROM product_models WHERE product_id = p.id) as var_min_price,
              (SELECT MAX(price) FROM product_models WHERE product_id = p.id) as var_max_price,
              COALESCE(p.image_path, (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1)) as display_image
              FROM merchant_products p
              LEFT JOIN merchant_custom_categories c ON p.custom_category_id = c.id
              WHERE p.merchant_id = ?";
    
    $params = [$now, $now, $merchantId];

    if ($pridensCat) {
        $query .= " AND p.category = ?";
        $params[] = $pridensCat;
    }

    if ($shopCatId) {
        $query .= " AND p.custom_category_id = ?";
        $params[] = $shopCatId;
    }

    $query .= " ORDER BY c.sort_order ASC, p.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$products]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
