<?php
require_once __DIR__ . '/../db.php';
function check($table) {
    global $pdo;
    echo "--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if(strpos($row['Field'], 'point') !== false || strpos($row['Field'], 'subscriber') !== false) {
            print_r($row);
        }
    }
}
check('merchant_orders');
check('merchant_food_orders');
check('merchant_service_orders');
check('subscribers');
