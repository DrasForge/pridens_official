<?php
require_once 'db.php';
$mId = 1;
$types = ['Products', 'Foods', 'Services', 'Spots'];
foreach($types as $t) {
    echo "Type: $t\n";
    $_GET['merchant_id'] = $mId;
    $_GET['type'] = $t;
    $_SESSION['admin_id'] = 1;
    include 'get_merchant_eligible_items.php';
    echo "\n\n";
}
?>
