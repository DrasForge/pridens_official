<?php
// admin_API/update_merchant_stock.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? ''; // 'product' or 'model'
$id   = intval($data['id'] ?? 0);
$stock = intval($data['stock'] ?? 0);

if (!$type || !$id) {
    echo json_encode(['status'=>'error', 'message'=>'Missing required parameters']);
    exit;
}

try {
    if ($type === 'product') {
        $stmt = $pdo->prepare("UPDATE merchant_products SET stock_quantity = ? WHERE id = ?");
        $stmt->execute([$stock, $id]);
    } elseif ($type === 'model') {
        $stmt = $pdo->prepare("UPDATE product_models SET stock = ? WHERE id = ?");
        $stmt->execute([$stock, $id]);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Invalid type']);
        exit;
    }

    echo json_encode(['status'=>'success', 'message'=>'Stock updated successfully']);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
