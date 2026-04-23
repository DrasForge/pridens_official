<?php
// admin_API/save_merchant_voucher.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

try {
    $pdo->beginTransaction();

    $merchant_id          = intval($_POST['merchant_id'] ?? 0);
    $v_id                 = intval($_POST['id'] ?? 0);
    $name                 = trim($_POST['name'] ?? '');
    $code                 = trim($_POST['voucher_code'] ?? '');
    $type                 = $_POST['type'] ?? 'Standard';
    $reward_type          = $_POST['reward_type'] ?? 'Percentage';
    $reward_value         = floatval($_POST['reward_value'] ?? 0);
    $min_spend            = floatval($_POST['min_spend'] ?? 0);
    $limit_per_user       = intval($_POST['usage_limit_per_user'] ?? 1);
    $target_type          = $_POST['target_type'] ?? 'All';

    $start_time           = str_replace('T', ' ', $_POST['start_time'] ?? '');
    $end_time             = str_replace('T', ' ', $_POST['end_time'] ?? '');
    
    $item_ids_json        = $_POST['item_ids_json'] ?? '[]';
    $item_ids             = json_decode($item_ids_json, true);

    if (!$merchant_id || !$name || !$code || !$start_time || !$end_time) {
        throw new Exception("Required fields missing (Name, Code, or Schedule)");
    }

    if (strtotime($start_time) >= strtotime($end_time)) {
        throw new Exception("Start time must be before End time.");
    }

    // 1. Save or Update Voucher Meta
    if ($v_id) {
        $stmt = $pdo->prepare("UPDATE merchant_vouchers SET 
            name = ?, voucher_code = ?, type = ?, reward_type = ?, reward_value = ?, 
            min_spend = ?, usage_limit_per_user = ?, start_time = ?, end_time = ?, target_type = ? 
            WHERE id = ? AND merchant_id = ?");
        $stmt->execute([
            $name, $code, $type, $reward_type, $reward_value, 
            $min_spend, $limit_per_user, $start_time, $end_time, $target_type, 
            $v_id, $merchant_id
        ]);
        
        $pdo->prepare("DELETE FROM merchant_voucher_items WHERE voucher_id = ?")->execute([$v_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO merchant_vouchers 
            (merchant_id, name, voucher_code, type, reward_type, reward_value, min_spend, usage_limit_per_user, start_time, end_time, target_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $merchant_id, $name, $code, $type, $reward_type, $reward_value, $min_spend, $limit_per_user, $start_time, $end_time, $target_type
        ]);
        $v_id = $pdo->lastInsertId();
    }

    // 2. Save Targeted Items
    if ($target_type === 'Selected' && !empty($item_ids)) {
        $stmt = $pdo->prepare("INSERT INTO merchant_voucher_items (voucher_id, product_id) VALUES (?, ?)");
        foreach ($item_ids as $pid) {
            $stmt->execute([$v_id, intval($pid)]);
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Voucher saved successfully', 'id'=>$v_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
