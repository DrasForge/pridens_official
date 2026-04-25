<?php
require_once __DIR__ . '/../db.php';
echo "--- merchant_services ---\n";
$stmt = $pdo->query("DESCRIBE merchant_services");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
