<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query("SELECT * FROM pos_transaction_items LIMIT 5");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
