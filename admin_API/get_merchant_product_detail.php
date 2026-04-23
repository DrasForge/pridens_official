<?php
// admin_API/get_merchant_product_detail.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    echo json_encode(['status'=>'error', 'message'=>'Product ID missing']);
    exit;
}

try {
    $now = date('Y-m-d H:i:s');

    // 1. Get Master Info (with active promo info)
    $stmt = $pdo->prepare("SELECT p.*,
        (SELECT pi.promo_price FROM merchant_promotion_items pi 
         JOIN merchant_promotions pr ON pi.promo_id = pr.id 
         WHERE pi.product_id = p.id AND pi.model_id IS NULL AND pr.status = 'Enabled' 
         AND ? BETWEEN pr.start_time AND pr.end_time LIMIT 1) as active_promo_price,
        (SELECT pi.discount_percent FROM merchant_promotion_items pi 
         JOIN merchant_promotions pr ON pi.promo_id = pr.id 
         WHERE pi.product_id = p.id AND pi.model_id IS NULL AND pr.status = 'Enabled' 
         AND ? BETWEEN pr.start_time AND pr.end_time LIMIT 1) as active_discount_percent
        FROM merchant_products p WHERE id = ?");
    $stmt->execute([$now, $now, $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['status'=>'error', 'message'=>'Product not found']);
        exit;
    }

    // 2. Get Variations (Tiers)
    $stmt = $pdo->prepare("SELECT * FROM product_variation_tiers WHERE product_id = ? ORDER BY tier_index ASC");
    $stmt->execute([$productId]);
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tiers as &$tier) {
        $stmt = $pdo->prepare("SELECT * FROM product_variation_options WHERE tier_id = ?");
        $stmt->execute([$tier['id']]);
        $tier['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Get Models (with active promo info)
    $stmt = $pdo->prepare("SELECT m.*,
        (SELECT pi.promo_price FROM merchant_promotion_items pi 
         JOIN merchant_promotions pr ON pi.promo_id = pr.id 
         WHERE pi.model_id = m.id AND pr.status = 'Enabled' 
         AND ? BETWEEN pr.start_time AND pr.end_time LIMIT 1) as active_promo_price,
        (SELECT pi.discount_percent FROM merchant_promotion_items pi 
         JOIN merchant_promotions pr ON pi.promo_id = pr.id 
         WHERE pi.model_id = m.id AND pr.status = 'Enabled' 
         AND ? BETWEEN pr.start_time AND pr.end_time LIMIT 1) as active_discount_percent
        FROM product_models m WHERE product_id = ?");
    $stmt->execute([$now, $now, $productId]);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get Images
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Get Wholesale
    $stmt = $pdo->prepare("SELECT * FROM product_wholesale_prices WHERE product_id = ? ORDER BY min_quantity ASC");
    $stmt->execute([$productId]);
    $wholesale = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => array_merge($product, [
            'variation_tiers' => $tiers,
            'models' => $models,
            'images' => $images,
            'wholesale' => $wholesale
        ])
    ]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
