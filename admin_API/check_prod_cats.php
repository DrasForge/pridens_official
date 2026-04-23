<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT DISTINCT category FROM merchant_products");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
