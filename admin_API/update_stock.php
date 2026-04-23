<?php
// admin_API/update_stock.php
require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id']) || !isset($input['change_amount'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$product_id = $input['product_id'];
$change_amount = intval($input['change_amount']);
$action_type = $input['action_type'] ?? 'Adjustment';
$notes = $input['notes'] ?? '';
$admin_id = $input['admin_id'] ?? null; // To be replaced with session admin_id if available

try {
    $pdo->beginTransaction();

    // 1. Update stock_quantity
    $stmt = $pdo->prepare("UPDATE `pos_products` SET `stock_quantity` = `stock_quantity` + ? WHERE `id` = ?");
    $stmt->execute([$change_amount, $product_id]);

    // 2. Log the change
    $logStmt = $pdo->prepare("INSERT INTO `pos_inventory_logs` (product_id, change_amount, action_type, admin_id, notes) VALUES (?, ?, ?, ?, ?)");
    $logStmt->execute([$product_id, $change_amount, $action_type, $admin_id, $notes]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
