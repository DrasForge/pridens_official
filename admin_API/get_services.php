<?php
// admin_API/get_services.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT s.*, c.name as category_name 
                           FROM merchant_services s 
                           LEFT JOIN merchant_custom_categories c ON s.category_id = c.id 
                           WHERE s.merchant_id = ? 
                           ORDER BY s.created_at DESC");
    $stmt->execute([$merchantId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $services]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
