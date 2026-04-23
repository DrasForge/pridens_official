<?php
require 'db.php';
$stmt = $pdo->query('DESCRIBE agents');
foreach ($stmt as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
?>
