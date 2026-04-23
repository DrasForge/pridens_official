<?php
// admin_API/delete_merchant_category.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['status'=>'error', 'message'=>'Category ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM merchant_custom_categories WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success', 'message'=>'Category deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
