<?php
// admin_API/delete_merchant_food_category.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    if (!$id) throw new Exception("ID missing");

    $stmt = $pdo->prepare("DELETE FROM merchant_food_categories WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success', 'message'=>'Category deleted']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
