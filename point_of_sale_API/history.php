<?php
// point_of_sale_API/history.php
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$admin_id = $_SESSION['admin_id'];

try {
    // Get current open shift for this admin
    $stmt = $pdo->prepare("SELECT id FROM `pos_shifts` WHERE `admin_id` = ? AND `status` = 'Open' LIMIT 1");
    $stmt->execute([$admin_id]);
    $shift = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shift) {
        // If no open shift, just return empty or error. Since history is usually per shift...
        echo json_encode(['success' => true, 'transactions' => []]);
        exit;
    }

    $shift_id = $shift['id'];

    // Fetch transactions for the current shift
    $stmtT = $pdo->prepare("
        SELECT id, receipt_number, grand_total, status, created_at, discount_type,
               customer_first_name, customer_last_name, customer_middle_name
        FROM `pos_transactions` 
        WHERE `shift_id` = ? 
        ORDER BY id DESC
    ");
    $stmtT->execute([$shift_id]);
    $transactions = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'transactions' => $transactions]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
