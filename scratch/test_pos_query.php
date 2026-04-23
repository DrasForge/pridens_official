<?php
require_once 'admin_API/db.php';

try {
    echo "Testing query on pos_products...\n";
    $stmt = $pdo->query("SELECT name, price, stock_quantity, category FROM pos_products LIMIT 1");
    $row = $stmt->fetch();
    print_r($row);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
