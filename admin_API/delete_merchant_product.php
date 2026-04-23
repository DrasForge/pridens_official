<?php
// admin_API/delete_merchant_product.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if (!$id) {
    echo json_encode(['status'=>'error','message'=>'ID missing']);
    exit;
}

try {
    // Optionally: soft delete by setting status to 'Inactive'
    // But since the user wants to start with 'Products' one at a time, 
    // a hard delete is often preferred for cleanup during dev.
    $stmt = $pdo->prepare("DELETE FROM merchant_products WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status'=>'success','message'=>'Product deleted successfully']);

} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
?>
