<?php
// admin_API/get_merchant_hours.php
header('Content-Type: application/json');
require_once 'db.php';

$merchantId = intval($_GET['merchant_id'] ?? 0);

if (!$merchantId) {
    echo json_encode(['status' => 'error', 'message' => 'Merchant ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM merchant_operating_hours WHERE merchant_id = ?");
    $stmt->execute([$merchantId]);
    $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $hours]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
