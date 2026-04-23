<?php
$pdo = new PDO('mysql:host=localhost;dbname=pridens_official', 'root', '');
$stmt = $pdo->query('DESCRIBE pos_products');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
