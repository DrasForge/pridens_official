<?php
// admin_API/get_merchant_categories.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$merchantId = intval($_GET['merchant_id'] ?? 0);
$type = $_GET['type'] ?? '';

if (!$merchantId) {
    echo json_encode(['status'=>'error', 'message'=>'Merchant ID missing']);
    exit;
}

try {
    $sql = "SELECT * FROM merchant_custom_categories WHERE merchant_id = ?";
    $params = [$merchantId];
    
    if ($type) {
        $sql .= " AND general_type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY sort_order ASC, name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $categories]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
