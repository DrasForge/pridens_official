<?php
require_once 'db.php';
$s=$pdo->query('DESC merchants'); 
while($r=$s->fetch(PDO::FETCH_ASSOC)) echo $r['Field'].' ';
?>
