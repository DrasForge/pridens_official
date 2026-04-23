<?php
// admin_API/save_merchant_promotion.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

try {
    $pdo->beginTransaction();

    $merchant_id = intval($_POST['merchant_id'] ?? 0);
    $promo_id    = intval($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    
    $start_time  = str_replace('T', ' ', $_POST['start_time'] ?? '');
    $end_time    = str_replace('T', ' ', $_POST['end_time'] ?? '');
    $item_category = $_POST['item_category'] ?? 'Products';
    
    $items_json  = $_POST['items_json'] ?? '[]';
    $items       = json_decode($items_json, true);

    if (!$merchant_id || !$name || !$start_time || !$end_time) {
        throw new Exception("Required fields missing (Merchant ID, Name, or Schedule)");
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        throw new Exception("Start time must be before End time.");
    }

    // 1. Save or Update Promotion Meta
    if ($promo_id) {
        // Verify ownership and existence
        $check = $pdo->prepare("SELECT id FROM merchant_promotions WHERE id = ? AND merchant_id = ?");
        $check->execute([$promo_id, $merchant_id]);
        if (!$check->fetch()) {
            throw new Exception("Promotion not found for this merchant.");
        }

        $stmt = $pdo->prepare("UPDATE merchant_promotions SET name = ?, start_time = ?, end_time = ?, item_category = ? WHERE id = ?");
        $stmt->execute([$name, $start_time, $end_time, $item_category, $promo_id]);
        
        // Wipe items for replacement
        $pdo->prepare("DELETE FROM merchant_promotion_items WHERE promo_id = ?")->execute([$promo_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO merchant_promotions (merchant_id, name, start_time, end_time, item_category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$merchant_id, $name, $start_time, $end_time, $item_category]);
        $promo_id = $pdo->lastInsertId();
    }

    // 2. Save Promotion Items
    if (!empty($items)) {
        $single_type = 'Product';
        if ($item_category === 'Foods') $single_type = 'Food';
        elseif ($item_category === 'Services') $single_type = 'Service';
        elseif ($item_category === 'Spots') $single_type = 'Spot';

        $stmt = $pdo->prepare("INSERT INTO merchant_promotion_items (promo_id, item_id, item_type, model_id, promo_price, discount_percent) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $itm_id  = intval($item['product_id']); // Logic in JS sends product_id key
            $mod_id  = (isset($item['model_id']) && $item['model_id'] !== null && $item['model_id'] !== 'null' && $item['model_id'] !== '') ? intval($item['model_id']) : null;
            $price   = floatval($item['promo_price']);
            $percent = intval($item['discount_percent'] ?? $item['discount']);

            $stmt->execute([$promo_id, $itm_id, $single_type, $mod_id, $price, $percent]);
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Promotion saved successfully', 'id'=>$promo_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
