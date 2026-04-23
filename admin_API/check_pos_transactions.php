<?php
require 'db.php';
$stmt = $pdo->query('DESCRIBE pos_transactions');
foreach ($stmt as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
?>
