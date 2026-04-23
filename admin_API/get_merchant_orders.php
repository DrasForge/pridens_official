<?php
// admin_API/get_merchant_orders.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
$status = $_GET['status'] ?? ''; // Optional filter

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $query = "SELECT o.*, s.first_name, s.last_name, s.contact_number, s.email,
                     pc.provider_name as channel_provider, pc.account_name as channel_account_name
              FROM merchant_orders o
              JOIN subscribers s ON o.subscriber_id = s.account_id
              LEFT JOIN merchant_payment_channels pc ON o.payment_channel_id = pc.id
              WHERE o.merchant_id = ?";
    $params = [$merchantId];

    if ($status && $status !== 'All') {
        $query .= " AND o.status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each order
    foreach ($orders as &$order) {
        $itemStmt = $pdo->prepare("SELECT * FROM merchant_order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['status'=>'success', 'data'=>$orders]);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
