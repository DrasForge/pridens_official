<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query("DESCRIBE merchant_products");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
