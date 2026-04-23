<?php
// point_of_sale_API/void_transaction.php
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$input = json_decode(file_get_contents('php://input'), true);
$transaction_id = intval($input['id'] ?? 0);

if (!$transaction_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid transaction ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Verify the transaction exists and belongs to the current shift
    $stmt = $pdo->prepare("
        SELECT t.id, t.grand_total, t.discount_amount, t.status, t.shift_id, t.receipt_number
        FROM pos_transactions t
        JOIN pos_shifts s ON t.shift_id = s.id
        WHERE t.id = ? AND s.admin_id = ? AND s.status = 'Open'
    ");
    $stmt->execute([$transaction_id, $admin_id]);
    $txn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$txn) {
        throw new Exception('Transaction not found or shift is closed.');
    }

    if ($txn['status'] === 'Voided') {
        throw new Exception('Transaction is already voided.');
    }

    // 2. Update transaction status to Voided
    $pdo->prepare("UPDATE pos_transactions SET status = 'Voided' WHERE id = ?")->execute([$transaction_id]);

    // 3. Reverse the shift totals
    $pdo->prepare("UPDATE pos_shifts SET total_sales = total_sales - ?, total_discounts = total_discounts - ? WHERE id = ?")
        ->execute([floatval($txn['grand_total']), floatval($txn['discount_amount']), $txn['shift_id']]);

    // 4. Reverse accumulated grand total in system settings
    $pdo->prepare("UPDATE pos_system_settings SET accumulated_grand_total = accumulated_grand_total - ?")
        ->execute([floatval($txn['grand_total'])]);

    // 5. Audit log
    $pdo->prepare("INSERT INTO pos_audit_logs (admin_id, action_type, description) VALUES (?, 'VOID', CONCAT('Voided transaction OR# ', ?))")
        ->execute([$admin_id, $txn['receipt_number']]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Transaction ' . $txn['receipt_number'] . ' voided successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
