<?php
// admin_API/save_addon_deal.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

try {
    $pdo->beginTransaction();

    $merchant_id      = intval($_POST['merchant_id'] ?? 0);
    $deal_id          = intval($_POST['id'] ?? 0);
    $name             = trim($_POST['name'] ?? '');
    $min_spend        = floatval($_POST['min_spend'] ?? 0);
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    
    $start_time       = str_replace('T', ' ', $_POST['start_time'] ?? '');
    $end_time         = str_replace('T', ' ', $_POST['end_time'] ?? '');
    
    $item_ids_json    = $_POST['item_ids_json'] ?? '[]';
    $item_ids         = json_decode($item_ids_json, true);

    if (!$merchant_id || !$name || !$start_time || !$end_time || $min_spend < 0) {
        throw new Exception("Required fields missing or invalid (Name, Schedule, Min Spend)");
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        throw new Exception("Start time must be before End time.");
    }

    // 1. Save or Update Add-on Deal Meta
    if ($deal_id) {
        $stmt = $pdo->prepare("UPDATE merchant_addon_deals SET name = ?, min_spend = ?, discount_percent = ?, start_time = ?, end_time = ? WHERE id = ? AND merchant_id = ?");
        $stmt->execute([$name, $min_spend, $discount_percent, $start_time, $end_time, $deal_id, $merchant_id]);
        
        $pdo->prepare("DELETE FROM merchant_addon_items WHERE addon_deal_id = ?")->execute([$deal_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO merchant_addon_deals (merchant_id, name, min_spend, discount_percent, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$merchant_id, $name, $min_spend, $discount_percent, $start_time, $end_time]);
        $deal_id = $pdo->lastInsertId();
    }

    // 2. Save Add-on Items
    if (!empty($item_ids)) {
        $stmt = $pdo->prepare("INSERT INTO merchant_addon_items (addon_deal_id, product_id) VALUES (?, ?)");
        foreach ($item_ids as $pid) {
            $stmt->execute([$deal_id, intval($pid)]);
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Add-on Deal saved successfully', 'id'=>$deal_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
