<?php
// admin_API/get_merchant_pos_items.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$merchantId = intval($_GET['merchant_id'] ?? 0);
if (!$merchantId) {
    echo json_encode(['status' => 'error', 'message' => 'Merchant ID missing']);
    exit;
}

try {
    // 1. Get Products
    $stmtProd = $pdo->prepare("SELECT id, name, price, stock_quantity, image_path as display_image, pridens_sku, is_exclusive, 'Product' as item_type FROM merchant_products WHERE merchant_id = ? AND status = 'Active'");
    $stmtProd->execute([$merchantId]);
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Food (Dishes)
    $stmtFood = $pdo->prepare("SELECT id, name, base_price as price, 999 as stock_quantity, image_path as display_image, 'N/A' as pridens_sku, is_best_seller as is_exclusive, 'Food' as item_type FROM merchant_foods WHERE merchant_id = ? AND status = 'Available'");
    $stmtFood->execute([$merchantId]);
    $food = $stmtFood->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Services
    $stmtServ = $pdo->prepare("SELECT id, service_name as name, price, 999 as stock_quantity, cover_photo as display_image, 'N/A' as pridens_sku, is_exclusive, 'Service' as item_type FROM merchant_services WHERE merchant_id = ? AND status = 'active'");
    $stmtServ->execute([$merchantId]);
    $services = $stmtServ->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'products' => $products,
            'food' => $food,
            'services' => $services
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
