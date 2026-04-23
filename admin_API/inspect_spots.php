<?php
require_once 'db.php';
$stmt = $pdo->query("SHOW TABLES");
echo "TABLES:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
