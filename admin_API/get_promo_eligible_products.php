<?php
// admin_API/get_promo_eligible_products.php
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

    // Get products and their models
    $stmt = $pdo->prepare("SELECT id, name, price, pridens_sku, image_path, has_variations FROM merchant_products WHERE merchant_id = ? AND status = 'Active'");
    $stmt->execute([$merchant_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$p) {
        if ($p['has_variations']) {
            $m_stmt = $pdo->prepare("SELECT id, name, variation_key, price, pridens_sku FROM product_models WHERE product_id = ?");
            $m_stmt->execute([$p['id']]);
            $p['models'] = $m_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $p['models'] = [];
        }
    }

    echo json_encode(['status'=>'success', 'data'=>$products]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
