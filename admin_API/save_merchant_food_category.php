<?php
// admin_API/save_merchant_food_category.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }

try {
    $id = intval($_POST['id'] ?? 0);
    $merchantId = intval($_POST['merchant_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$merchantId || !$name) throw new Exception("Required fields missing");

    $imagePath = $_POST['existing_image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/food_categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $imagePath = $uploadDir . time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE merchant_food_categories SET name = ?, description = ?, image_path = ? WHERE id = ? AND merchant_id = ?");
        $stmt->execute([$name, $description, $imagePath, $id, $merchantId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO merchant_food_categories (merchant_id, name, description, image_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$merchantId, $name, $description, $imagePath]);
    }
    echo json_encode(['status'=>'success', 'message'=>'Food category saved']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
