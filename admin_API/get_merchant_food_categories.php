<?php
// admin_API/get_merchant_food_categories.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

$merchantId = intval($_GET['merchant_id'] ?? 0);
if (!$merchantId) { echo json_encode(['status'=>'error', 'message'=>'ID missing']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM merchant_food_categories WHERE merchant_id = ? ORDER BY sort_order ASC, name ASC");
    $stmt->execute([$merchantId]);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
