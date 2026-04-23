<?php
require_once 'db.php';
$s=$pdo->query('DESC product_models'); 
while($r=$s->fetch(PDO::FETCH_ASSOC)) echo $r['Field'].' ';
?>
