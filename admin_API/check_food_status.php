<?php
require_once 'db.php';
$stmt = $pdo->prepare("SELECT id, name, status FROM merchant_foods WHERE merchant_id = 1");
$stmt->execute();
print_r($stmt->fetchAll());
?>
