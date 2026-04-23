<?php
// admin_API/save_pos_product.php
require_once 'db.php';
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No input provided']);
    exit;
}

$id = isset($input['id']) ? $input['id'] : null;
$sku = $input['sku'];
$name = $input['name'];
$price = $input['price'];
$base_price = isset($input['base_price']) ? $input['base_price'] : 0.00;
$category = isset($input['category']) ? $input['category'] : 'All Items';
$status = isset($input['status']) ? $input['status'] : 'Active';

try {
    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE `pos_products` SET `sku` = ?, `name` = ?, `price` = ?, `base_price` = ?, `category` = ?, `status` = ? WHERE `id` = ?");
        $stmt->execute([$sku, $name, $price, $base_price, $category, $status, $id]);
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        // Create
        // First check if SKU already exists
        $check = $pdo->prepare("SELECT id FROM `pos_products` WHERE `sku` = ?");
        $check->execute([$sku]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'error' => 'SKU already exists']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO `pos_products` (`sku`, `name`, `price`, `base_price`, `category`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $name, $price, $base_price, $category, $status]);
        echo json_encode(['success' => true, 'message' => 'Product created successfully', 'id' => $pdo->lastInsertId()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
