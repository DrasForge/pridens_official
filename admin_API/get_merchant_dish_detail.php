<?php
// admin_API/get_merchant_dish_detail.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['status'=>'error', 'message'=>'ID missing']); exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM merchant_foods WHERE id = ?");
    $stmt->execute([$id]);
    $dish = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dish) throw new Exception("Dish not found");

    // Map fields for the shared editor UI
    $dish['base_price'] = $dish['base_price'];
    $dish['custom_category_id'] = $dish['food_category_id'];

    // Get Modifiers
    $stmt = $pdo->prepare("SELECT * FROM merchant_food_modifier_groups WHERE food_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($groups as &$g) {
        $optStmt = $pdo->prepare("SELECT * FROM merchant_food_modifier_options WHERE group_id = ? ORDER BY sort_order ASC");
        $optStmt->execute([$g['id']]);
        $g['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
        $g['min_sel'] = $g['min_selection'];
        $g['max_sel'] = $g['max_selection'];
    }
    $dish['modifier_groups'] = $groups;

    // Fetch Exclusive Deductions
    $stmt = $pdo->prepare("SELECT * FROM merchant_food_exclusive_offers WHERE food_id = ?");
    $stmt->execute([$id]);
    $dish['exclusive_deductions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'success', 'data'=>$dish]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
