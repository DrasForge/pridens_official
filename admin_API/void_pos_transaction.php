<?php
// admin_API/void_pos_transaction.php
require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['transaction_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing transaction ID']);
    exit;
}

$transaction_id = $input['transaction_id'];
$reason = $input['reason'] ?? 'Admin Void';
$admin_id = $input['admin_id'] ?? 1; // Replace with session id

try {
    $pdo->beginTransaction();

    // 1. Get Transaction Info (Check if already voided)
    $stmtT = $pdo->prepare("SELECT * FROM `pos_transactions` WHERE id = ? FOR UPDATE");
    $stmtT->execute([$transaction_id]);
    $transaction = $stmtT->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) throw new Exception("Transaction not found.");
    if ($transaction['status'] === 'Voided') throw new Exception("Transaction is already voided.");

    // 2. Fetch Items to restore stock
    $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM `pos_transaction_items` WHERE transaction_id = ?");
    $stmtItems->execute([$transaction_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        // Restore Stock
        $pdo->prepare("UPDATE `pos_products` SET `stock_quantity` = `stock_quantity` + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['product_id']]);

        // Log Inventory Restoration
        $logInv = $pdo->prepare("INSERT INTO `pos_inventory_logs` (product_id, change_amount, action_type, admin_id, notes) VALUES (?, ?, ?, ?, ?)");
        $logInv->execute([$item['product_id'], $item['quantity'], 'Void Sale', $admin_id, "Voided SI# {$transaction['receipt_number']}"]);
    }

    // 3. Update Transaction Status
    $pdo->prepare("UPDATE `pos_transactions` SET `status` = 'Voided' WHERE id = ?")->execute([$transaction_id]);

    // 4. Update Shift Totals (Subtract from sales, potentially add to a VOIDED_SALES column if you have one, or just subtract)
    $pdo->prepare("UPDATE `pos_shifts` SET total_sales = total_sales - ?, total_discounts = total_discounts - ? WHERE id = ?")
        ->execute([$transaction['grand_total'], $transaction['discount_amount'], $transaction['shift_id']]);

    // 5. Audit Log (BIR required)
    $stmtAudit = $pdo->prepare("INSERT INTO `pos_audit_logs` (admin_id, action_type, description, affected_record_id) VALUES (?, ?, ?, ?)");
    $stmtAudit->execute([$admin_id, 'VOID_TRANSACTION', "Voided transaction {$transaction['receipt_number']}. Reason: {$reason}", $transaction_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Transaction voided and stock restored successfully.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
