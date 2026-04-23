<?php
require_once 'db.php';
$stmt = $pdo->query("DESCRIBE merchant_food_orders");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
