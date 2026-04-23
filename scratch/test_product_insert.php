<?php
require_once 'admin_API/db.php';
try {
    $stmt = $pdo->prepare("INSERT INTO merchant_products (merchant_id, product_sku, name, description, category, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=name");
    $stmt->execute([1, 'TEST-001', 'Sample Marketplace Product', 'A test product for the marketplace.', 'Products', 1250.00, 50]);
    echo "Inserted/Updated test product TEST-001\n";
    
    $stmt = $pdo->prepare("SELECT * FROM merchant_products WHERE merchant_id = 1");
    $stmt->execute();
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
