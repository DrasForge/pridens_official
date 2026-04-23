<?php
// admin_API/get_merchant_dishes.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

$merchantId = intval($_GET['merchant_id'] ?? 0);
$foodCatId = intval($_GET['shop_category_id'] ?? 0);

if (!$merchantId) { echo json_encode(['status'=>'error', 'message'=>'ID missing']); exit; }

try {
    $sql = "SELECT f.*, c.name as shop_category_name 
            FROM merchant_foods f
            LEFT JOIN merchant_food_categories c ON f.food_category_id = c.id
            WHERE f.merchant_id = ?";
    $params = [$merchantId];

    if ($foodCatId) {
        $sql .= " AND f.food_category_id = ?";
        $params[] = $foodCatId;
    }

    $sql .= " ORDER BY f.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$dishes]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
