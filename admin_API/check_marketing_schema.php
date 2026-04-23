<?php
require_once 'db.php';
function check($table) {
    global $pdo;
    echo "--- $table ---\n";
    $stmt = $pdo->query("DESC $table");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
check('merchant_vouchers');
check('merchant_promotions');
check('merchant_promotion_items');
?>
