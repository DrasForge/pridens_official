<?php
require 'db.php';
$stmt = $pdo->query("DESCRIBE merchant_custom_categories");
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
