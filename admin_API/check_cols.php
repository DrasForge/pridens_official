<?php
require_once 'db.php';
function desc($t) {
    global $pdo;
    echo "--- $t ---\n";
    try {
        $st = $pdo->query("DESC $t");
        while($r = $st->fetch(PDO::FETCH_ASSOC)) echo $r['Field'].", ";
        echo "\n";
    } catch(Exception $e){ echo $e->getMessage()."\n"; }
}
desc('merchant_products');
desc('merchant_foods');
desc('merchant_services');
desc('merchant_spots');
?>
