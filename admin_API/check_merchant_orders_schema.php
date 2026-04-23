<?php
require_once 'db.php';
$stmt = $pdo->query("DESC merchant_orders");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
