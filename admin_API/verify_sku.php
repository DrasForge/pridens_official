<?php
require_once 'db.php';
header('Content-Type: application/json');
// require_once 'csrf.php'; 

try {
    $sku = $_GET['sku'] ?? '';
    
    if (empty($sku)) {
        echo json_encode(['success' => false, 'message' => 'SKU is required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, name, price FROM pos_products WHERE sku = ? AND status = 'Active'");
    $stmt->execute([$sku]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo json_encode([
            'success' => true, 
            'product' => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => number_format($product['price'], 2, '.', '')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or Inactive SKU']);
    }

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
