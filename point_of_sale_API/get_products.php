<?php
// point_of_sale_API/get_products.php
require_once '../admin_API/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM `pos_products` WHERE `status` = 'Active' ORDER BY `name` ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $products]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
