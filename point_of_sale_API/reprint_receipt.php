<?php
// point_of_sale_API/reprint_receipt.php
// Returns full transaction data needed to regenerate the receipt on the frontend
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$transaction_id = intval($_GET['id'] ?? 0);

if (!$transaction_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid transaction ID']);
    exit;
}

try {
    // Fetch the transaction
    $stmt = $pdo->prepare("
        SELECT t.*, s.admin_id
        FROM pos_transactions t
        JOIN pos_shifts s ON t.shift_id = s.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $txn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$txn) {
        echo json_encode(['success' => false, 'error' => 'Transaction not found']);
        exit;
    }

    // Fetch items
    $stmtItems = $pdo->prepare("SELECT * FROM pos_transaction_items WHERE transaction_id = ?");
    $stmtItems->execute([$transaction_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'transaction' => [
            'receipt_number' => $txn['receipt_number'],
            'created_at' => $txn['created_at'],
            'customer_last_name' => $txn['customer_last_name'],
            'customer_first_name' => $txn['customer_first_name'],
            'customer_middle_name' => $txn['customer_middle_name'],
            'agent_referral_code' => $txn['agent_referral_code'],
            'discount_type' => $txn['discount_type'],
            'subtotal' => $txn['subtotal'],
            'discount_amount' => $txn['discount_amount'],
            'grand_total' => $txn['grand_total'],
            'tendered_amount' => $txn['tendered_amount'],
            'change_amount' => $txn['change_amount'],
            'status' => $txn['status']
        ],
        'items' => $items
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
