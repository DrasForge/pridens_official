<?php
// admin_API/delete_pos_product.php
require_once 'db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

$id = $input['id'];

try {
    // Soft delete: update status to Inactive
    $stmt = $pdo->prepare("UPDATE `pos_products` SET `status` = 'Inactive' WHERE `id` = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Product deactivated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Product not found or already inactive']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
