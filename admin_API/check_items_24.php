<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM merchant_order_items WHERE order_id = 24");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
