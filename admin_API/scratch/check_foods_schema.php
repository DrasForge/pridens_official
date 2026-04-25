<?php
require_once __DIR__ . '/../db.php';
echo "--- merchant_foods ---\n";
$stmt = $pdo->query("DESCRIBE merchant_foods");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
