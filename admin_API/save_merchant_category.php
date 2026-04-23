<?php
// admin_API/save_merchant_category.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { 
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); 
    exit; 
}

try {
    $id             = intval($_POST['id'] ?? 0);
    $merchantId     = intval($_POST['merchant_id'] ?? 0);
    $name           = trim($_POST['name'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $sortOrder      = intval($_POST['sort_order'] ?? 0);
    $generalType    = $_POST['general_type'] ?? 'Products';

    if (!$merchantId || !$name) {
        throw new Exception("Merchant ID and Category Name are required.");
    }

    // Handle Image Upload
    $imagePath = $_POST['existing_image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileName = time() . '_cat_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE merchant_custom_categories SET name = ?, description = ?, image_path = ?, sort_order = ?, general_type = ? WHERE id = ? AND merchant_id = ?");
        $stmt->execute([$name, $description, $imagePath, $sortOrder, $generalType, $id, $merchantId]);
        echo json_encode(['status'=>'success', 'message'=>'Category updated successfully']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO merchant_custom_categories (merchant_id, name, description, image_path, sort_order, general_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$merchantId, $name, $description, $imagePath, $sortOrder, $generalType]);
        echo json_encode(['status'=>'success', 'message'=>'Category added successfully']);
    }

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
?>
