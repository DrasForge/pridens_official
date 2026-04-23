<?php
require 'db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM admins");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);
