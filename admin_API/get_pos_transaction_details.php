<?php
// admin_API/get_pos_transaction_details.php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['transaction_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing transaction ID']);
    exit;
}

$transaction_id = $_GET['transaction_id'];

try {
    // 1. Get items
    $stmt = $pdo->prepare("SELECT ti.*, p.sku 
                          FROM `pos_transaction_items` ti
                          LEFT JOIN `pos_products` p ON ti.product_id = p.id
                          WHERE ti.transaction_id = ?");
    $stmt->execute([$transaction_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $items]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
