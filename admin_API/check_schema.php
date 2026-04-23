<?php
require 'c:/Pridens_trading_co/admin_API/db.php';
$table = 'subscribers';
$stmt = $pdo->query("DESC $table");
echo "Schema for $table:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
