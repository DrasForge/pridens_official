<?php
require_once 'c:/Pridens_trading_co/admin_API/db.php';
$stmt = $pdo->query("SHOW TABLES");
echo implode("\n", $stmt->fetchAll(PDO::FETCH_COLUMN));
?>
