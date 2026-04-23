<?php
require 'db.php';
$tables = ['subscriber_pvoucher_ledger', 'subscriber_user'];
foreach($tables as $t) {
    echo "--- $t ---\n";
    $stmt = $pdo->query("DESC $t");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
