<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("DESCRIBE merchant_orders");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
