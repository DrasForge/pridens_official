<?php
require_once __DIR__ . '/../db.php';
function check($table) {
    global $pdo;
    echo "--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if(strpos($row['Field'], 'priden') !== false || strpos($row['Field'], 'profit') !== false || strpos($row['Field'], 'system_fee') !== false) {
            print_r($row);
        }
    }
}
check('merchant_orders');
check('merchant_food_orders');
$table = 'merchant_service_orders';
echo "--- $table ---\n";
$stmt = $pdo->query("DESCRIBE $table");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

