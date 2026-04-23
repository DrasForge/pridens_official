<?php
// point_of_sale_API/search_products.php
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    $stmt = $pdo->prepare("SELECT id, name, price, category FROM `pos_products` WHERE `status` = 'Active' AND (`name` LIKE ? OR `category` LIKE ?) ORDER BY `name` ASC LIMIT 10");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
