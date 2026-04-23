<?php
require_once 'db.php';
$st = $pdo->query("DESC merchant_spots");
while($r = $st->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . " ";
?>
