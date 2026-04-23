<?php
// admin_API/save_merchant_dish.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

try {
    $pdo->beginTransaction();

    $id = intval($_POST['id'] ?? 0);
    $merchantId = intval($_POST['merchant_id'] ?? 0);
    $foodCatId = intval($_POST['custom_category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $basePrice = floatval($_POST['base_price'] ?? 0);
    $prepTime = intval($_POST['prep_time_mins'] ?? 15);
    $servingSize = $_POST['brand'] ?? ''; // Reusing the 'brand' field name for serving size consistency in naming
    $spicyLevel = intval($_POST['spicy_level'] ?? 0);
    $isBest = isset($_POST['is_best_seller']) ? 1 : 0;
    $isNew = isset($_POST['is_new']) ? 1 : 0;
    $isVegan = isset($_POST['is_vegan']) ? 1 : 0;
    $isHalal = isset($_POST['is_halal']) ? 1 : 0;

    if (!$merchantId || !$name) throw new Exception("Required fields missing");

    $imagePath = $_POST['existing_main_image'] ?? null;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/foods/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $imagePath = $uploadDir . time() . '_' . basename($_FILES['main_image']['name']);
        move_uploaded_file($_FILES['main_image']['tmp_name'], $imagePath);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE merchant_foods SET food_category_id = ?, name = ?, description = ?, base_price = ?, image_path = ?, prep_time_mins = ?, serving_size = ?, spicy_level = ?, is_best_seller = ?, is_new = ?, is_vegan = ?, is_halal = ? WHERE id = ? AND merchant_id = ?");
        $stmt->execute([$foodCatId, $name, $description, $basePrice, $imagePath, $prepTime, $servingSize, $spicyLevel, $isBest, $isNew, $isVegan, $isHalal, $id, $merchantId]);
        $dishId = $id;
    } else {
        $stmt = $pdo->prepare("INSERT INTO merchant_foods (merchant_id, food_category_id, name, description, base_price, image_path, prep_time_mins, serving_size, spicy_level, is_best_seller, is_new, is_vegan, is_halal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$merchantId, $foodCatId, $name, $description, $basePrice, $imagePath, $prepTime, $servingSize, $spicyLevel, $isBest, $isNew, $isVegan, $isHalal]);
        $dishId = $pdo->lastInsertId();
    }

    // Handle Modifiers
    $modifiersJson = $_POST['modifiers_data'] ?? '[]';
    $modifierGroups = json_decode($modifiersJson, true);
    if ($dishId && is_array($modifierGroups)) {
        $pdo->prepare("DELETE FROM merchant_food_modifier_groups WHERE food_id = ?")->execute([$dishId]);
        foreach ($modifierGroups as $idx => $group) {
            if (empty($group['name'])) continue;
            $stmt = $pdo->prepare("INSERT INTO merchant_food_modifier_groups (food_id, name, min_selection, max_selection, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$dishId, $group['name'], $group['min_sel'], $group['max_sel'], ($group['is_required'] ? 1 : 0), $idx]);
            $groupId = $pdo->lastInsertId();
            if (isset($group['options']) && is_array($group['options'])) {
                foreach ($group['options'] as $oIdx => $opt) {
                    if (empty($opt['name'])) continue;
                    
                    $optImgPath = $opt['image_path'] ?? null;
                    $fileKey = "mod_image_{$idx}_{$oIdx}";
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                        $modUploadDir = 'uploads/food_modifiers/';
                        if (!is_dir($modUploadDir)) mkdir($modUploadDir, 0777, true);
                        $optImgPath = $modUploadDir . time() . "_mod_{$idx}_{$oIdx}_" . basename($_FILES[$fileKey]['name']);
                        move_uploaded_file($_FILES[$fileKey]['tmp_name'], $optImgPath);
                    }

                    $pdo->prepare("INSERT INTO merchant_food_modifier_options (group_id, name, extra_price, image_path, sort_order) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$groupId, $opt['name'], $opt['price'], $optImgPath, $oIdx]);
                }
            }
        }
    }

    // Handle Exclusive Offer Deductions
    $exclusiveJson = $_POST['exclusive_deductions'] ?? '[]';
    $deductions = json_decode($exclusiveJson, true);
    if ($dishId && is_array($deductions)) {
        // Clear existing to avoid complexity, though ON DUPLICATE KEY is safer if we knew IDs
        $pdo->prepare("DELETE FROM merchant_food_exclusive_offers WHERE food_id = ?")->execute([$dishId]);
        foreach ($deductions as $d) {
            if (floatval($d['percent']) <= 0) continue;
            $stmt = $pdo->prepare("INSERT INTO merchant_food_exclusive_offers (merchant_id, plan_id, food_id, deduction_percent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$merchantId, $d['plan_id'], $dishId, $d['percent']]);
        }
    }

    $pdo->commit();
    echo json_encode(['status'=>'success', 'message'=>'Dish saved', 'dish_id'=>$dishId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
