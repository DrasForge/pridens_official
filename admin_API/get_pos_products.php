<?php
// admin_API/get_pos_products.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT * FROM `pos_products` WHERE `name` LIKE ? OR `sku` LIKE ? ORDER BY `created_at` DESC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT * FROM `pos_products` ORDER BY `created_at` DESC");
    }
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
