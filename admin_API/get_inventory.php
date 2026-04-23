<?php
// admin_API/get_inventory.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $query = "SELECT id, sku, name, category, price, stock_quantity, low_stock_threshold, status 
              FROM `pos_products` ";
    
    if (!empty($search)) {
        $query .= " WHERE `name` LIKE ? OR `sku` LIKE ? ";
        $stmt = $pdo->prepare($query . " ORDER BY stock_quantity ASC");
        $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
    } else {
        $stmt = $pdo->query($query . " ORDER BY stock_quantity ASC");
    }
    
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $inventory]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
